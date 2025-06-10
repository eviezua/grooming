<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Pets;
use App\Enum\Species;
use App\Factory\PetsFactory;
use Elastic\Elasticsearch\Client;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group pet
 * @group api
 */
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

    public function testGetBySearchBreedFilterFull(): void
    {
        $client = static::createClient();

        $this->indexPet('Savannah');

        $client->request('GET', 'api/v1/pets?search=SAVANNAH');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Pet',
            '@id' => '/api/v1/pets',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchFilterStart(): void
    {
        $client = static::createClient();

        $this->indexPet('Savannah');
        $this->indexPet('Domestic Shorthair');

        $client->request('GET', 'api/v1/pets?search=sava');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Pet',
            '@id' => '/api/v1/pets',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchBreedFilterMiddle(): void
    {
        $client = static::createClient();

        $this->indexPet('Savannah');
        $this->indexPet('Domestic Shorthair');

        $client->request('GET', 'api/v1/pets?search=VANNA');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Pet',
            '@id' => '/api/v1/pets',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchBreedFilterEnd(): void
    {
        $client = static::createClient();

        $this->indexPet('Savannah');
        $this->indexPet('Domestic Shorthair');

        $client->request('GET', 'api/v1/pets?search=NAH');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Pet',
            '@id' => '/api/v1/pets',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchSpiceFilter(): void
    {
        $client = static::createClient();

        $allowedSpices = array_filter(Species::cases(), fn(Species $s) => $s !== Species::Cat);

        PetsFactory::createOne(['spice' => Species::Cat]);
        PetsFactory::createMany(10, function() use ($allowedSpices) {
            return [
                'spice' => $allowedSpices[array_rand($allowedSpices)],
            ];
        });

        $client->request('GET', 'api/v1/pets?spice[]=Cat');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Pet',
            '@id' => '/api/v1/pets',
            '@type' => 'Collection',
            'totalItems' => 1,
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
        static::createClient()->request(
            'POST',
            'api/v1/pets',
            [
                'json' => [
                    'spice' => 'Cat',
                    'hair' => 'Long',
                    'breed' => 'Norwegian forest cat',
                    'size' => 'Big'
                ],
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ]
            ]
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $pet = static::getContainer()->get('doctrine')->getRepository(Pets::class)->findOneBy(
            ['breed' => 'Norwegian forest cat']
        );
        $this->assertNotNull($pet);
    }

    public function testPostInvalidPet(): void
    {
        static::createClient()->request(
            'POST',
            'api/v1/pets',
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
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'spice', 'message' => 'The value you selected is not a valid choice.'],
                ['propertyPath' => 'hair', 'message' => 'The value you selected is not a valid choice.'],
                ['propertyPath' => 'breed', 'message' => 'Breed of pets must not be empty.'],
                ['propertyPath' => 'size', 'message' => 'The value you selected is not a valid choice.'],
            ],
        ]);
    }

    private function indexPet(string $name): void
    {
        PetsFactory::createOne(['breed' => $name]);
        $pet = static::getContainer()->get('doctrine')->getRepository(Pets::class)->findOneBy(['breed' => $name]);

        $elasticsearchClient = static::getContainer()->get(Client::class);
        $elasticsearchClient->index([
            'index' => 'pets',
            'id' => $pet->getId(),
            'body' => ['breed' => $pet->getBreed()],
        ]);
        $elasticsearchClient->indices()->refresh(['index' => 'pets']);
    }
}
