<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\MastersServices;
use App\Factory\MastersFactory;
use App\Factory\MastersServicesFactory;
use App\Factory\ServicesFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group masterservice
 * @group api
 */
class MastersServicesApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        MastersServicesFactory::createMany(100);

        static::createClient()->request('GET', 'api/v1/masters_services');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/MastersServices',
            '@id' => '/api/v1/masters_services',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testGetMasterService(): void
    {
        $ms = MastersServicesFactory::createOne();
        $msId = $ms->getId();

        static::createClient()->request('GET', "/api/v1/masters_services/$msId");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/MastersServices',
            '@id' => "/api/v1/masters_services/$msId",
            '@type' => 'MastersServices',
        ]);
    }

    public function testGetByMasterId(): void
    {
        $master = MastersFactory::createOne();
        $masterId = $master->getId();

        MastersServicesFactory::createMany(10, ['master' => $master]);
        MastersServicesFactory::createMany(10);

        static::createClient()->request('GET', "/api/v1/masters_services?master.id[]=" . $masterId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/MastersServices',
            '@id' => "/api/v1/masters_services",
            '@type' => 'Collection',
            'totalItems' => 10
        ]);
    }

    public function testGetByServiceId(): void
    {
        $service = ServicesFactory::createOne();
        $serviceId = $service->getId();

        MastersServicesFactory::createMany(10, ['service' => $service]);
        MastersServicesFactory::createMany(10);

        static::createClient()->request('GET', "/api/v1/masters_services?service.id[]=" . $serviceId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/MastersServices',
            '@id' => "/api/v1/masters_services",
            '@type' => 'Collection',
            'totalItems' => 10
        ]);
    }

    public function testPostMasterService(): void
    {
        $master = MastersFactory::createOne();
        $masterId = $master->getId();
        $service = ServicesFactory::createOne();
        $serviceId = $service->getId();

        $client = static::createClient();

        $client->request('POST', '/api/v1/masters_services', [
            'json' => [
                'masterId' => $masterId,
                'serviceId' => $serviceId,
                'price' => 500
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            'masterId' => $masterId,
            'serviceId' => $serviceId,
            'price' => 500
        ]);
    }

    public function testPatchMasterService(): void
    {
        $ms = MastersServicesFactory::createOne();
        $msId = $ms->getId();

        $client = static::createClient();

        $client->request('PATCH', '/api/v1/masters_services/' . $msId, [
            'json' => [
                'price' => 550
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();

        $this->assertJsonContains([
            'price' => 550
        ]);
    }

    public function testDeleteMasterService(): void
    {
        $ms = MastersServicesFactory::createOne();
        $msId = $ms->getId();

        static::createClient()->request('DELETE', '/api/v1/masters_services/' . $msId);

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(MastersServices::class)->findOneBy(
                ['id' => $msId]
            )
        );
    }
}
