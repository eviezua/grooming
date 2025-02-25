<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Cities;
use App\Factory\CitiesFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CitiesApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        CitiesFactory::createMany(100);

        static::createClient()->request('GET', 'api/v1/cities');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/City',
            '@id' => '/api/v1/cities',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testGetCity(): void
    {
        $city = CitiesFactory::createOne();
        $cityId = $city->getId();

        static::createClient()->request('GET', 'api/v1/cities/' . $cityId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/City',
            '@id' => "/api/v1/cities/$cityId",
            '@type' => 'City'
        ]);
    }

    public function testPostCity(): void
    {
        static::createClient()->request('POST', 'api/v1/cities',
            [
                'json' => [
                    'city' => 'Kyiv',
                ],
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ]
            ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $city = static::getContainer()->get('doctrine')->getRepository(Cities::class)->findOneBy(
            ['city' => 'Kyiv']
        );
        $this->assertNotNull($city);
    }

    public function testPostDublicateCity(): void
    {
        CitiesFactory::createOne(['city' => 'Kyiv']);

        static::createClient()->request('POST', 'api/v1/cities',
            [
                'json' => [
                    'city' => ' kyiv* ',
                ],
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ]
            ]);
        $this->assertResponseStatusCodeSame(422);
    }
}
