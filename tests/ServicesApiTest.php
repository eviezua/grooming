<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\ServicesFactory;
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
        ServicesFactory::createMany(100);

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

    public function testGetService(): void
    {
        $service = ServicesFactory::createOne();
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
        $service = ServicesFactory::createOne();
        $serviceId = $service->getId();

        static::createClient()->request('PATCH', '/api/v1/services/' . $serviceId, [
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
}
