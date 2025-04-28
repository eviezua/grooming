<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\BookingsFactory;
use App\Factory\ClientsFactory;
use App\Factory\PetsFactory;
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
        ClientsFactory::createMany(100);

        static::createClient()->request('GET', 'api/v1/clients');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Client',
            '@id' => '/api/v1/clients',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testGetClient(): void
    {
        $client = ClientsFactory::createOne();
        $clientId = $client->getId();

        static::createClient()->request('GET', "/api/v1/clients/$clientId");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Client',
            '@id' => "/api/v1/clients/$clientId",
            '@type' => 'Client',
        ]);
    }

    public function testPostClient(): void
    {
        static::createClient()->request('POST', '/api/v1/clients', [
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
        static::createClient()->request('POST', '/api/v1/clients', [
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
        $client = ClientsFactory::createOne();
        $clientId = $client->getId();
        $pet = PetsFactory::createOne();
        $petId = $pet->getId();
        BookingsFactory::createOne(['id_client' => $client, 'pet' => $pet]);

        static::createClient()->request('PUT', '/api/v1/clients/' . $clientId, [
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
        $client = ClientsFactory::createOne();
        $clientId = $client->getId();

        static::createClient()->request('PATCH', '/api/v1/clients/' . $clientId, [
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
}
