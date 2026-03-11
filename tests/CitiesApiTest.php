<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Cities;
use App\Factory\CitiesFactory;
use Elastic\Elasticsearch\Client;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group city
 * @group api
 */
class CitiesApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        CitiesFactory::createMany(100);

        static::createClient()->request('GET', 'api/v1/cities');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/City',
            '@id' => '/api/v1/cities',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testGetCitiesByMultipleIds(): void
    {
        $cities = CitiesFactory::createMany(5);
        $targetIds = [$cities[0]->getId(), $cities[2]->getId()];

        $client = static::createClient();
        $client->request('GET', '/api/v1/cities?id[]=' . $targetIds[0] . '&id[]=' . $targetIds[1]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'totalItems' => 2,
        ]);

        $data = $client->getResponse()->toArray();
        $returnedIds = array_map(fn($item) => $item['id'], $data['member']);

        $this->assertContains($targetIds[0], $returnedIds);
        $this->assertContains($targetIds[1], $returnedIds);
    }

    public function testGetBySearchNameFilterFull(): void
    {
        $client = static::createClient();

        $this->indexCity('Zaporizhzhya');

        $client->request('GET', 'api/v1/cities?search=zaporizhzhya');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/City',
            '@id' => '/api/v1/cities',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchNameFilterStart(): void
    {
        $client = static::createClient();

        $this->indexCity('Zaporizhzhya');

        $client->request('GET', 'api/v1/cities?search=zapo');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/City',
            '@id' => '/api/v1/cities',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchNameFilterMiddle(): void
    {
        $client = static::createClient();

        $this->indexCity('Zaporizhzhya');

        $client->request('GET', 'api/v1/cities?search=rizh');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/City',
            '@id' => '/api/v1/cities',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchNameFilterEnd(): void
    {
        $client = static::createClient();

        $this->indexCity('Zaporizhzhya');

        $client->request('GET', 'api/v1/cities?search=zhya');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/City',
            '@id' => '/api/v1/cities',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetCity(): void
    {
        $city = CitiesFactory::createOne();
        $cityId = $city->getId();

        static::createClient()->request('GET', 'api/v1/cities/' . $cityId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/City',
            '@id' => "/api/v1/cities/$cityId",
            '@type' => 'City'
        ]);
    }

    public function testPostCity(): void
    {
        static::createClient()->request(
            'POST',
            'api/v1/cities',
            [
                'json' => [
                    'city' => 'Kyiv',
                ],
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ]
            ]
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $city = static::getContainer()->get('doctrine')->getRepository(Cities::class)->findOneBy(
            ['city' => 'Kyiv']
        );
        $this->assertNotNull($city);
    }

    public function testPostDublicateCity(): void
    {
        CitiesFactory::createOne(['city' => 'Kyiv']);

        static::createClient()->request(
            'POST',
            'api/v1/cities',
            [
                'json' => [
                    'city' => ' kyiv* ',
                ],
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ]
            ]
        );
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'city', 'message' => "City \" kyiv* \" already exists."],
            ],
        ]);
    }

    private function indexCity(string $name): void
    {
        CitiesFactory::createOne(['city' => $name]);
        $city = static::getContainer()->get('doctrine')->getRepository(Cities::class)->findOneBy(['city' => $name]);

        $elasticsearchClient = static::getContainer()->get(Client::class);
        $elasticsearchClient->index([
            'index' => 'cities',
            'id' => $city->getId(),
            'body' => ['city' => $city->getCity()],
        ]);
        $elasticsearchClient->indices()->refresh(['index' => 'cities']);
    }
}
