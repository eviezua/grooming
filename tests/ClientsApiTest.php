<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Clients;
use App\Entity\Masters;
use App\Factory\BookingsFactory;
use App\Factory\ClientsFactory;
use App\Factory\MastersFactory;
use App\Factory\PetsFactory;
use Elastic\Elasticsearch\Client;
use Symfony\Component\BrowserKit\Cookie;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group client
 * @group api
 */
class ClientsApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        $email = 'boss@test.com';
        $client = $this->createAuthenticatedClient($email);

        $master = MastersFactory::find(['email' => $email]);
        $clients = ClientsFactory::createMany(100);
        foreach ($clients as $c) {
            BookingsFactory::createOne(['id_master' => $master, 'id_client' => $c]);
        }

        $client->request('GET', '/api/v1/clients');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Client',
            '@id' => '/api/v1/clients',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testGetCollectionFilteredByIds(): void
    {
        $email = 'filter_ids@test.com';
        $client = $this->createAuthenticatedClient($email);
        $master = MastersFactory::find(['email' => $email]);

        $clients = ClientsFactory::createMany(5);
        foreach ($clients as $c) {
            BookingsFactory::createOne(['id_master' => $master, 'id_client' => $c]);
        }

        $id1 = $clients[0]->getId();
        $id2 = $clients[2]->getId();

        $client->request('GET', '/api/v1/clients', [
            'query' => [
                'id' => [$id1, $id2]
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'totalItems' => 2,
        ]);

        $json = $client->getResponse()->toArray();
        $returnedIds = array_column($json['member'], 'id');

        $this->assertContains($id1, $returnedIds);
        $this->assertContains($id2, $returnedIds);
        $this->assertNotContains($clients[1]->getId(), $returnedIds);
    }

    public function testGetBySearchFilterFullName(): void
    {
        $email = 'searcher@test.com';
        $client = $this->createAuthenticatedClient($email);
        $master = MastersFactory::find(['email' => $email]);

        $this->indexClient('Rayden', 'Crawford', $master);

        $client->request('GET', 'api/v1/clients?search=rayden crawford');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Client',
            '@id' => '/api/v1/clients',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchFilterByName(): void
    {
        $email = 'searcher_name@test.com';
        $client = $this->createAuthenticatedClient($email);
        $master = MastersFactory::find(['email' => $email]);

        $this->indexClient('Rayden', 'Crawford', $master);

        $client->request('GET', 'api/v1/clients?search=den');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Client',
            '@id' => '/api/v1/clients',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchFilterBySurname(): void
    {
        $email = 'searcher_surname@test.com';
        $client = $this->createAuthenticatedClient($email);
        $master = MastersFactory::find(['email' => $email]);

        $this->indexClient('Rayden', 'Crawford', $master);

        $client->request('GET', 'api/v1/clients?search=craw');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Client',
            '@id' => '/api/v1/clients',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetClient(): void
    {
        $email = 'item@test.com';
        $client = $this->createAuthenticatedClient($email);
        $master = MastersFactory::find(['email' => $email]);

        $myClient = ClientsFactory::createOne();
        BookingsFactory::createOne(['id_master' => $master, 'id_client' => $myClient]);
        $clientId = $myClient->getId();

        $client->request('GET', "/api/v1/clients/$clientId");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Client',
            '@id' => "/api/v1/clients/$clientId",
            '@type' => 'Client',
        ]);
    }

    public function testPostClient(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/clients', [
            'json' => [
                "name" => "Post",
                "surname" => "Test",
                "email" => "test@test.com",
                "phone" => "+12523957776"
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            "name" => "Post",
            "surname" => "Test",
            "email" => "test@test.com",
            "phone" => "+12523957776"
        ]);
    }

    public function testPostInvalidClient(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/clients', [
            'json' => [
                'name' => '',
                'surname' => '',
                'email' => 'invalid-email',
                'phone' => '123',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'name', 'message' => 'Name can\'t be empty'],
                ['propertyPath' => 'surname', 'message' => 'Surname can\'t be empty'],
                ['propertyPath' => 'email', 'message' => 'Not a valid email address.'],
                ['propertyPath' => 'phone', 'message' => 'Invalid phone number.'],
            ],
        ]);
    }

    public function testPutClient(): void
    {
        $email = 'editor@test.com';
        $client = $this->createAuthenticatedClient($email, true);
        $master = MastersFactory::find(['email' => $email]);

        $myClient = ClientsFactory::createOne();
        $clientId = $myClient->getId();
        $pet = PetsFactory::createOne();
        $petId = $pet->getId();
        BookingsFactory::createOne(['id_master' => $master, 'id_client' => $myClient, 'pet' => $pet]);

        $client->request('PUT', '/api/v1/clients/' . $clientId, [
            'json' => [
                "id" => $clientId,
                "name" => "Put",
                "surname" => "Test",
                "email" => "test@test.com",
                "phone" => "+12523957776",
                "pets" => [
                    $petId
                ]
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            "name" => "Put",
            "surname" => "Test",
            "email" => "test@test.com",
            "phone" => "+12523957776",
            "pets" => []
        ]);
    }

    public function testPatchClient(): void
    {
        $email = 'patcher@test.com';
        $client = $this->createAuthenticatedClient($email, true);
        $master = MastersFactory::find(['email' => $email]);

        $myClient = ClientsFactory::createOne();
        $clientId = $myClient->getId();
        BookingsFactory::createOne(['id_master' => $master, 'id_client' => $myClient]);

        $client->request('PATCH', '/api/v1/clients/' . $clientId, [
            'json' => [
                "name" => "Patch",
                "surname" => "Test",
                "email" => "test@test.com",
                "phone" => "+12523957776"
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            "name" => "Patch",
            "surname" => "Test",
            "email" => "test@test.com",
            "phone" => "+12523957776"
        ]);
    }

    public function testFindByEmailSuccess(): void
    {
        $client = static::createClient();
        $email = 'target@example.com';

        $target = ClientsFactory::createOne(['email' => $email]);
        $targetId = $target->getId();

        $client->request('GET', '/api/v1/v1/clients/find-by-email?email=' . $email);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $targetId
        ]);
    }

    public function testFindByEmailNotFound(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/v1/clients/find-by-email?email=nonexistent@test.com');

        $this->assertResponseStatusCodeSame(404);
    }

    private function indexClient(string $name, string $surname, $master = null): void
    {
        $client = ClientsFactory::createOne(['name' => $name, 'surname' => $surname]);
        if ($master) {
            BookingsFactory::createOne(['id_master' => $master, 'id_client' => $client]);
        }

        $elasticsearchClient = static::getContainer()->get(Client::class);
        $elasticsearchClient->index([
            'index' => 'clients',
            'id' => $client->getId(),
            'body' => ['name' => $client->getName(), 'surname' => $client->getSurname()],
        ]);
        $elasticsearchClient->indices()->refresh(['index' => 'clients']);
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
