<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Pets;
use App\Factory\PetsFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class PetsApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        PetsFactory::createMany(100);

        static::createClient()->request('GET', 'api/v1/pets');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Pet',
            '@id' => '/api/v1/pets',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testGetPet(): void
    {
        $pet = PetsFactory::createOne();
        $petId = $pet->getId();

        static::createClient()->request('GET', 'api/v1/pets/' . $petId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Pet',
            '@id' => "/api/v1/pets/$petId",
            '@type' => 'Pet'
        ]);
    }

    public function testPostPet(): void
    {
        static::createClient()->request('POST', 'api/v1/pets',
            [
                'json' => [
                    'spice' => 'cat',
                    'hair' => 'long',
                    'breed' => 'Norwegian forest cat',
                    'size' => 'big'
                ],
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ]
            ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $pet = static::getContainer()->get('doctrine')->getRepository(Pets::class)->findOneBy(
            ['breed' => 'Norwegian forest cat']
        );
        $this->assertNotNull($pet);
    }
    public function testPostInvalidPet(): void
    {
        static::createClient()->request('POST', 'api/v1/pets',
            [
                'json' => [
                    'spice' => '',
                    'hair' => '',
                    'breed' => '',
                    'size' => ''
                ],
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ]
            ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'spice', 'message' => 'Spice of pets must not be empty.'],
                ['propertyPath' => 'hair', 'message' => 'Hair of pets must not be empty.'],
                ['propertyPath' => 'breed', 'message' => 'Breed of pets must not be empty.'],
                ['propertyPath' => 'size', 'message' => 'Size of pets must not be empty.'],
            ],
        ]);
    }
}
