<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\CitiesFactory;
use App\Factory\MastersFactory;
use App\Factory\PetsFactory;
use App\Factory\ServicesFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group master
 * @group api
 */
class MastersApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        MastersFactory::createMany(100);

        static::createClient()->request('GET', 'api/v1/masters');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testGetMaster(): void
    {
        $master = MastersFactory::createOne();
        $masterId = $master->getId();

        static::createClient()->request('GET', "/api/v1/masters/$masterId");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => "/api/v1/masters/$masterId",
            '@type' => 'Master',
        ]);
    }

    public function testPostMaster(): void
    {
        $city = CitiesFactory::createOne();
        $cityId = $city->getId();
        $service = ServicesFactory::createOne();
        $serviceId = $service->getId();
        $pet = PetsFactory::createOne();
        $petId = $pet->getId();

        static::createClient()->request('POST', '/api/v1/masters', [
            'json' => [
                "name" => "Post",
                "surname" => "Test",
                "servicesId" => [
                    $serviceId
                ],
                "cityId" => $cityId,
                "petsId" => [
                    $petId
                ],
                "password" => "password",
                "email" => "test@test.com",
                "phone" => "+12523957776",
                "photo" => "photo.jpg",
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

    public function testPostInvalidMaster(): void
    {
        $city = CitiesFactory::createOne();
        $cityId = $city->getId();
        $service = ServicesFactory::createOne();
        $serviceId = $service->getId();
        $pet = PetsFactory::createOne();
        $petId = $pet->getId();

        static::createClient()->request('POST', '/api/v1/masters', [
            'json' => [
                "name" => "",
                "surname" => "",
                "servicesId" => [
                    $serviceId
                ],
                "cityId" => $cityId,
                "petsId" => [
                    $petId
                ],
                "password" => "pas",
                "email" => "test.com",
                "phone" => "776",
                "photo" => "photo.jpg",
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'name', 'message' => 'Name can\'t be empty'],
                ['propertyPath' => 'surname', 'message' => 'Surname can\'t be empty'],
                ['propertyPath' => 'password', 'message' => 'Password must be at least 6 characters.'],
                ['propertyPath' => 'email', 'message' => 'Not a valid email address.'],
                ['propertyPath' => 'phone', 'message' => 'Invalid phone number.'],
            ],
        ]);
    }

    public function testPutMaster(): void
    {
        $master = MastersFactory::createOne();
        $masterId = $master->getId();
        $city = CitiesFactory::createOne();
        $cityId = $city->getId();
        $service = ServicesFactory::createOne();
        $serviceId = $service->getId();
        $pet = PetsFactory::createOne();
        $petId = $pet->getId();

        static::createClient()->request('PUT', '/api/v1/masters/' . $masterId, [
            'json' => [
                "name" => "Put",
                "surname" => "Test",
                "servicesId" => [
                    $serviceId
                ],
                "cityId" => $cityId,
                "petsId" => [
                    $petId
                ],
                "password" => "password",
                "email" => "test@test.com",
                "phone" => "+12523957776",
                "photo" => "photo.jpg",
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            "name" => "Put",
            "surname" => "Test",
            "servicesId" => [
                $serviceId
            ],
            "cityId" => $cityId,
            "petsId" => [
                $petId
            ],
            "email" => "test@test.com",
            "phone" => "+12523957776",
            "photo" => "photo.jpg",
        ]);
    }

    public function testPatchMaster(): void
    {
        $master = MastersFactory::createOne();
        $masterId = $master->getId();


        static::createClient()->request('PATCH', '/api/v1/masters/' . $masterId, [
            'json' => [
                "name" => "Patch",
                "surname" => "Test",
                "email" => "test@test.com",
                "phone" => "+12523957776",
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
