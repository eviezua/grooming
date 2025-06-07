<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Services;
use App\Factory\MastersFactory;
use App\Factory\ServicesFactory;
use Elastic\Elasticsearch\Client;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group service
 * @group api
 */
class ServicesApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        ServicesFactory::createMany(100);

        static::createClient()->request('GET', 'api/v1/services');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Service',
            '@id' => '/api/v1/services',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testGetBySearchNameFilterFull(): void
    {
        $client = static::createClient();

        $this->indexService('Ear Cleaning');
        $this->indexService('Nail Polish');


        $client->request('GET', 'api/v1/services?search= ear cleaning ');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Service',
            '@id' => '/api/v1/services',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchFilterStart(): void
    {
        $client = static::createClient();

        $this->indexService('Ear Cleaning');
        $this->indexService('Nail Polish');

        $client->request('GET', 'api/v1/services?search= ear ');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Service',
            '@id' => '/api/v1/services',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchBreedFilterMiddle(): void
    {
        $client = static::createClient();

        $this->indexService('Ear Cleaning');
        $this->indexService('Nail Polish');

        $client->request('GET', 'api/v1/services?search= r cle');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Service',
            '@id' => '/api/v1/services',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchBreedFilterEnd(): void
    {
        $client = static::createClient();

        $this->indexService('Ear Cleaning');
        $this->indexService('Nail Polish');

        $client->request('GET', 'api/v1/services?search= n ing ');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Service',
            '@id' => '/api/v1/services',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testFilterByCostRange(): void
    {
        ServicesFactory::createOne(['cost' => 50]);
        ServicesFactory::createOne(['cost' => 100]);
        ServicesFactory::createOne(['cost' => 150]);
        ServicesFactory::createOne(['cost' => 200]);
        ServicesFactory::createOne(['cost' => 250]);

        $client = static::createClient();

        $client->request('GET', '/api/v1/services?cost[gte]=100&cost[lte]=200');

        $this->assertResponseIsSuccessful();

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Service',
            '@id' => '/api/v1/services',
            '@type' => 'Collection',
            'totalItems' => 3,
        ]);
    }

    public function testFilterByOnlyMinCost(): void
    {
        ServicesFactory::createOne(['cost' => 30]);
        ServicesFactory::createOne(['cost' => 60]);

        $client = static::createClient();
        $client->request('GET', '/api/v1/services?cost[gt]=50');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'totalItems' => 1,
        ]);
    }

    public function testFilterByOnlyMaxCost(): void
    {
        ServicesFactory::createOne(['cost' => 20]);
        ServicesFactory::createOne(['cost' => 80]);

        $client = static::createClient();
        $client->request('GET', '/api/v1/services?cost[lt]=50');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'totalItems' => 1,
        ]);
    }

    public function testGetCollectionWithMasterIdFilter(): void
    {
        $service = ServicesFactory::createOne();

        ServicesFactory::CreateMany(10);

        $master = MastersFactory::createOne(['id_services' => [$service]]);
        $masterId = $master->getId();

        static::createClient()->request('GET', 'api/v1/services?masters.id[]=' . $masterId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Service',
            '@id' => '/api/v1/services',
            '@type' => 'Collection',
            'totalItems' => 1
        ]);
    }

    public function testGetService(): void
    {
        $service = ServicesFactory::createOne();
        $serviceId = $service->getId();

        static::createClient()->request('GET', "/api/v1/services/$serviceId");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Service',
            '@id' => "/api/v1/services/$serviceId",
            '@type' => 'Service',
        ]);
    }

    public function testPostService(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/services', [
            'json' => [
                "name" => "Post",
                "cost" => 300,
                "default_time" => "00:30:00",
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            "name" => "Post",
            "cost" => 300,
        ]);

        $this->assertEquals('00:30:00', $responseData['default_time']['time']);
    }

    public function testPatchService(): void
    {
        $service = ServicesFactory::createOne();
        $serviceId = $service->getId();

        static::createClient()->request('PATCH', '/api/v1/services/' . $serviceId, [
            'json' => [
                "name" => "Patch",
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();

        $this->assertJsonContains([
            "name" => "Patch",
        ]);
    }

    public function testPostInvalidService(): void
    {
        static::createClient()->request('POST', '/api/v1/services', [
            'json' => [
                "name" => "",
                "cost" => null,
                "default_time" => "30.00",
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'name', 'message' => 'The name cannot be blank.'],
                ['propertyPath' => 'cost', 'message' => 'The cost cannot be blank.'],
                ['propertyPath' => 'default_time', 'message' => 'The default time must be in the format HH:MM:SS.']
            ],
        ]);
    }

    private function indexService(string $name): void
    {
        ServicesFactory::createOne(['name' => $name]);

        $service = static::getContainer()->get('doctrine')->getRepository(Services::class)->findOneBy(['name' => $name]
        );

        $elasticsearchClient = static::getContainer()->get(Client::class);
        $elasticsearchClient->index([
            'index' => 'services',
            'id' => $service->getId(),
            'body' => ['name' => $service->getName()],
        ]);
        $elasticsearchClient->indices()->refresh(['index' => 'services']);
    }
}
