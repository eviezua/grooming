<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Masters;
use App\Entity\MastersServices;
use App\Factory\MastersFactory;
use App\Factory\MastersServicesFactory;
use App\Factory\ServicesFactory;
use Symfony\Component\BrowserKit\Cookie;
use Zenstruck\Foundry\Persistence\Proxy;
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

    public function testGetCollectionWhenMasterAuthorized(): void
    {
        $master = MastersFactory::createOne();

        MastersServicesFactory::createMany(90);
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

    public function testPostMasterServiceByAdmin(): void
    {
        $master = MastersFactory::createOne();
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
        $masterA = MastersFactory::createOne();
        $masterB = MastersFactory::createOne();
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
        $master= MastersFactory::createOne();
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
        $master= MastersFactory::createOne();
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

    private function createAuthenticatedClient($userOrEmail = 'master@test.com', bool $isAdmin = false)
    {
        $client = static::createClient();

        if ($userOrEmail instanceof Masters) {
            $master = $userOrEmail;
        } else {
            $proxy = MastersFactory::repository()->findOneBy(['email' => $userOrEmail])
                ?? MastersFactory::createOne([
                    'email' => $userOrEmail,
                    'password' => 'password',
                    'roles' => $isAdmin ? ['ROLE_ADMIN'] : ['ROLE_MASTER']
                ]);
            $master = ($proxy instanceof Proxy) ? $proxy->_real() : $proxy;
        }

        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $token = $jwtManager->create($master);

        $cookieJar = $client->getCookieJar();
        $cookie = new Cookie('jwt', $token);
        $cookieJar->set($cookie);

        return $client;
    }
}
