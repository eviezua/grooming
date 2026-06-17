<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Districts;
use App\Enum\Status;
use App\Factory\CitiesFactory;
use App\Factory\DistrictsFactory;
use Elastic\Elasticsearch\Client;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group district
 * @group api
 */
class DistrictsApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        DistrictsFactory::createMany(100, ['status' => Status::Approved]);

        static::createClient()->request('GET', 'api/v1/districts');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/District',
            '@id' => '/api/v1/districts',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testGetDistrictsByMultipleIds(): void
    {
        $districts = DistrictsFactory::createMany(5, ['status' => Status::Approved]);
        $targetIds = [$districts[1]->getId(), $districts[3]->getId()];

        $client = static::createClient();
        $client->request('GET', '/api/v1/districts?id[]=' . $targetIds[0] . '&id[]=' . $targetIds[1]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 2]);
    }

    public function testGetBySearchNameFilterFull(): void
    {
        $client = static::createClient();

        $this->indexDistrict('Shevchenkovskiy');

        $client->request('GET', 'api/v1/districts?search=shevchenkovskiy');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/District',
            '@id' => '/api/v1/districts',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchNameFilterStart(): void
    {
        $client = static::createClient();

        $this->indexDistrict('Shevchenkovskiy');

        $client->request('GET', 'api/v1/districts?search=shev');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/District',
            '@id' => '/api/v1/districts',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchNameFilterMiddle(): void
    {
        $client = static::createClient();

        $this->indexDistrict('Shevchenkovskiy');

        $client->request('GET', 'api/v1/districts?search=chen');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/District',
            '@id' => '/api/v1/districts',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchNameFilterEnd(): void
    {
        $client = static::createClient();

        $this->indexDistrict('Shevchenkovskiy');

        $client->request('GET', 'api/v1/districts?search=vskiy');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/District',
            '@id' => '/api/v1/districts',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetByCityId(): void
    {
        $city = CitiesFactory::createOne(['status' => Status::Approved]);
        $cityId = $city->getId();

        DistrictsFactory::createMany(10, ['city' => $city, 'status' => Status::Approved]);
        DistrictsFactory::createMany(10, ['status' => Status::Approved]);

        $client = static::createClient();
        $client->request('GET', 'api/v1/districts?city.id[]=' . $cityId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/District',
            '@id' => '/api/v1/districts',
            '@type' => 'Collection',
            'totalItems' => 10,
        ]);
    }

    public function testGetDistrict(): void
    {
        $district = DistrictsFactory::createOne(['status' => Status::Approved]);
        $districtId = $district->getId();

        static::createClient()->request('GET', 'api/v1/districts/' . $districtId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/District',
            '@id' => "/api/v1/districts/$districtId",
            '@type' => 'District',
        ]);
    }

    public function testPostDistrict(): void
    {
        $city = CitiesFactory::createOne(['status' => Status::Approved]);
        $cityId = $city->getId();

        static::createClient()->request(
            'POST',
            'api/v1/districts',
            [
                'json' => [
                    'name' => 'Shevchenkovskiy',
                    'cityId' => $cityId
                ],
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ]
            ]
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $district = static::getContainer()->get('doctrine')->getRepository(Districts::class)->findOneBy(
            ['name' => 'Shevchenkovskiy']
        );
        $this->assertNotNull($district);
    }

    public function testPostDublicateDistrict(): void
    {
        $city = CitiesFactory::createOne(['status' => Status::Approved]);
        $cityId = $city->getId();

        DistrictsFactory::createOne(['name' => 'Shevchenkovskiy', 'city' => $city]);

        static::createClient()->request(
            'POST',
            'api/v1/districts',
            [
                'json' => [
                    'name' => ' shevchenkovskiy+ ',
                    'cityId' => $cityId
                ],
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ]
            ]
        );
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'name', 'message' => "District \" shevchenkovskiy+ \" in this city already exists."],
            ],
        ]);
    }

    private function indexDistrict(string $name): void
    {
        DistrictsFactory::createOne(['name' => $name, 'status' => Status::Approved]);
        $district = static::getContainer()->get('doctrine')->getRepository(Districts::class)->findOneBy(['name' => $name]);

        $elasticsearchClient = static::getContainer()->get(Client::class);
        $elasticsearchClient->index([
            'index' => 'districts',
            'id' => $district->getId(),
            'body' => ['name' => $district->getName()],
        ]);
        $elasticsearchClient->indices()->refresh(['index' => 'districts']);
    }
}
