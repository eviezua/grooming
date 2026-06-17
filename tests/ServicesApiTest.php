<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Masters;
use App\Entity\Services;
use App\Enum\Status;
use App\Factory\MastersFactory;
use App\Factory\MastersServicesFactory;
use App\Factory\ServicesFactory;
use Elastic\Elasticsearch\Client;
use Symfony\Component\BrowserKit\Cookie;
use Zenstruck\Foundry\Persistence\Proxy;
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
        ServicesFactory::createMany(100, ['status' => Status::Approved]);

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
        ServicesFactory::createOne(['cost' => 50, 'status' => Status::Approved]);
        ServicesFactory::createOne(['cost' => 100, 'status' => Status::Approved]);
        ServicesFactory::createOne(['cost' => 150, 'status' => Status::Approved]);
        ServicesFactory::createOne(['cost' => 200, 'status' => Status::Approved]);
        ServicesFactory::createOne(['cost' => 250, 'status' => Status::Approved]);

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
        ServicesFactory::createOne(['cost' => 30, 'status' => Status::Approved]);
        ServicesFactory::createOne(['cost' => 60, 'status' => Status::Approved]);

        $client = static::createClient();
        $client->request('GET', '/api/v1/services?cost[gt]=50');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'totalItems' => 1,
        ]);
    }

    public function testFilterByOnlyMaxCost(): void
    {
        ServicesFactory::createOne(['cost' => 20, 'status' => Status::Approved]);
        ServicesFactory::createOne(['cost' => 80, 'status' => Status::Approved]);

        $client = static::createClient();
        $client->request('GET', '/api/v1/services?cost[lt]=50');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'totalItems' => 1,
        ]);
    }

    public function testGetCollectionWithMasterIdFilter(): void
    {
        $service = ServicesFactory::createOne(['status' => Status::Approved]);

        ServicesFactory::CreateMany(10, ['status' => Status::Approved]);

        $master = MastersFactory::new(['services_count' => 0])->createOne();
        MastersServicesFactory::createOne([
            'master' => $master,
            'service' => $service,
        ]);
        $masterId = $master->getId();

        static::createClient()->request('GET', 'api/v1/services?id_masters[]=' . $masterId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Service',
            '@id' => '/api/v1/services',
            '@type' => 'Collection',
            'totalItems' => 1
        ]);
    }

    public function testGetServicesByMultipleIds(): void
    {
        $s1 = ServicesFactory::createOne(['name' => 'Grooming', 'status' => Status::Approved]);
        $s2 = ServicesFactory::createOne(['name' => 'Washing', 'status' => Status::Approved]);
        $s3 = ServicesFactory::createOne(['name' => 'Nails', 'status' => Status::Approved]);

        $client = static::createClient();

        $client->request('GET', '/api/v1/services', [
            'query' => ['id' => [$s1->getId(), $s3->getId()]]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 2]);

        $data = $client->getResponse()->toArray();
        $names = array_column($data['member'], 'name');
        $this->assertContains('Grooming', $names);
        $this->assertContains('Nails', $names);
        $this->assertNotContains('Washing', $names);
    }

    public function testGetService(): void
    {
        $service = ServicesFactory::createOne(['status' => Status::Approved]);
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
        $service = ServicesFactory::createOne(['status' => Status::Approved]);
        $serviceId = $service->getId();

        $adminClient = $this->createAuthenticatedClient('admin@test.com', true);

        $adminClient->request('PATCH', '/api/v1/services/' . $serviceId, [
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

    public function testPatchServiceAsMasterReturnsForbidden(): void
    {
        $service = ServicesFactory::createOne(['status' => Status::Approved]);
        $serviceId = $service->getId();

        $masterClient = $this->createAuthenticatedClient('regular_master@test.com', false);

        $masterClient->request('PATCH', '/api/v1/services/' . $serviceId, [
            'json' => [
                "name" => "Hack Attempt",
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(403);
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
        ServicesFactory::createOne(['name' => $name, 'status' => Status::Approved]);

        $service = static::getContainer()->get('doctrine')->getRepository(Services::class)->findOneBy(['name' => $name]);

        $elasticsearchClient = static::getContainer()->get(Client::class);
        $elasticsearchClient->index([
            'index' => 'services',
            'id' => $service->getId(),
            'body' => ['name' => $service->getName()],
        ]);
        $elasticsearchClient->indices()->refresh(['index' => 'services']);
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
                    'roles' => $isAdmin ? ['ROLE_ADMIN'] : ['ROLE_MASTER'],
                    'status' => Status::Approved
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
