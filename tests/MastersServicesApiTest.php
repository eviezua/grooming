<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\MastersServices;
use App\Enum\Status;
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
    use ResetDatabase;
    use Factories;
    use LoginJWTTrait;

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

    public function testGetCollectionWhenMasterAuthorized(): void
    {
        $master = MastersFactory::createOne(['status' => Status::Approved]);
        $otherMaster = MastersFactory::createOne(['status' => Status::Approved]);

        MastersServicesFactory::createMany(90, ['master' => $otherMaster]);
        MastersServicesFactory::createMany(10, ['master' => $master]);

        $client = $this->createAuthenticatedClient($master);

        $client->request('GET', 'api/v1/masters_services');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/MastersServices',
            '@id' => '/api/v1/masters_services',
            '@type' => 'Collection',
            'totalItems' => 10
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
        $master = MastersFactory::createOne(['status' => Status::Approved]);
        $masterId = $master->getId();
        $otherMaster = MastersFactory::createOne(['status' => Status::Approved]);

        MastersServicesFactory::createMany(10, ['master' => $master]);
        MastersServicesFactory::createMany(10, ['master' => $otherMaster]);

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
        $otherService = ServicesFactory::createOne();

        MastersServicesFactory::createMany(10, ['service' => $service]);
        MastersServicesFactory::createMany(10, ['service' => $otherService]);

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

    public function testPostMasterServiceByAdmin(): void
    {
        $master = MastersFactory::createOne(['status' => Status::Approved]);
        $masterId = $master->getId();
        $service = ServicesFactory::createOne();
        $serviceId = $service->getId();

        $client = $this->createAuthenticatedClient('admin@example.com', true);

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

    public function testMasterCanCreateOnlyHisOwnService(): void
    {
        $masterA = MastersFactory::createOne(['status' => Status::Approved]);
        $masterB = MastersFactory::createOne(['status' => Status::Approved]);
        $service = ServicesFactory::createOne();

        $client = $this->createAuthenticatedClient($masterA);

        $client->request('POST', '/api/v1/masters_services', [
            'json' => [
                'masterId' => $masterA->getId(),
                'serviceId' => $service->getId(),
                'price' => 500.0,
            ]
        ]);
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/api/v1/masters_services', [
            'json' => [
                'masterId' => $masterB->getId(),
                'serviceId' => $service->getId(),
                'price' => 1000.0,
            ]
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPatchMasterService(): void
    {
        $master= MastersFactory::createOne(['status' => Status::Approved]);
        $ms = MastersServicesFactory::createOne(['master' => $master]);
        $msId = $ms->getId();

        $client = $this->createAuthenticatedClient($master);

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
        $master= MastersFactory::createOne(['status' => Status::Approved]);
        $ms = MastersServicesFactory::createOne(['master' => $master]);
        $msId = $ms->getId();

        $client = $this->createAuthenticatedClient($master);

        $client->request('DELETE', '/api/v1/masters_services/' . $msId);

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(MastersServices::class)->findOneBy(
                ['id' => $msId]
            )
        );
    }
}
