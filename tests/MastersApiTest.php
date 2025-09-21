<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Masters;
use App\Factory\CitiesFactory;
use App\Factory\DistrictsFactory;
use App\Factory\MastersFactory;
use App\Factory\MastersServicesFactory;
use App\Factory\PetsFactory;
use App\Factory\ScheduleFactory;
use App\Factory\ServicesFactory;
use DateTime;
use Elastic\Elasticsearch\Client;
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

    public function testGetCollectionWithCityIdFilter(): void
    {
        $city = CitiesFactory::createOne();
        $cityId = $city->getId();

        MastersFactory::CreateMany(10, ['id_city' => $city]);
        MastersFactory::createMany(10);

        static::createClient()->request('GET', 'api/v1/masters?id_city.id[]=' . $cityId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 10
        ]);
    }

    public function testGetCollectionWithDistrictIdFilter(): void
    {
        $district = DistrictsFactory::createOne();
        $districtId = $district->getId();

        MastersFactory::CreateMany(10, ['district' => $district]);
        MastersFactory::createMany(10);

        static::createClient()->request('GET', 'api/v1/masters?district.id[]=' . $districtId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 10
        ]);
    }

    public function testGetCollectionWithServiceIdFilter(): void
    {
        $service = ServicesFactory::createOne();
        $serviceId = $service->getId();

        $masters = MastersFactory::new(['services_count' => 0])->createMany(10);

        foreach ($masters as $master) {
            MastersServicesFactory::createOne([
                'master' => $master,
                'service' => $service,
            ]);
        }

        MastersFactory::createMany(10);

        static::createClient()->request('GET', 'api/v1/masters?id_services[]=' . $serviceId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 10
        ]);
    }

    public function testGetCollectionWithPetsIdFilter(): void
    {
        $pet = PetsFactory::createOne();
        $petId = $pet->getId();

        MastersFactory::CreateMany(10, ['id_pets' => [$pet]]);
        MastersFactory::createMany(10);

        static::createClient()->request('GET', 'api/v1/masters?id_pets.id[]=' . $petId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 10
        ]);
    }

    public function testGetBySearchFilterFullName(): void
    {
        $client = static::createClient();

        $this->indexMaster('Chris', 'Phelps');

        $client->request('GET', 'api/v1/masters?search=Chris phelps');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchFilterByName(): void
    {
        $client = static::createClient();

        $this->indexMaster('Chris', 'Phelps');

        $client->request('GET', 'api/v1/masters?search=Ris');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetBySearchFilterBySurname(): void
    {
        $client = static::createClient();

        $this->indexMaster('Chris', 'Phelps');

        $client->request('GET', 'api/v1/masters?search=PHE');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
    }

    public function testGetCollectionWithTimeAvailableFilter(): void
    {
        ScheduleFactory::createOne([
            'start_time' => new DateTime('08:00:00'),
            'stop_time' => new DateTime('10:00:00'),
        ]);
        ScheduleFactory::createOne([
            'start_time' => new DateTime('12:00:00'),
            'stop_time' => new DateTime('14:00:00'),
        ]);

        ScheduleFactory::createOne([
            'start_time' => new DateTime('06:00:00'),
            'stop_time' => new DateTime('08:00:00'),
        ]);
        ScheduleFactory::createOne([
            'start_time' => new DateTime('20:00:00'),
            'stop_time' => new DateTime('23:30:00'),
        ]);

        static::createClient()->request('GET', '/api/v1/masters?start_time=07:00:00&stop_time=23:00:00');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 2
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
        $district = DistrictsFactory::createOne(['city' => $city]);
        $districtId = $district->getId();
        $pet = PetsFactory::createOne();
        $petId = $pet->getId();

        static::createClient()->request('POST', '/api/v1/masters', [
            'json' => [
                "name" => "Post",
                "surname" => "Test",
                "cityId" => $cityId,
                "districtId" => $districtId,
                "address" => 'Test address',
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
            "cityId" => $cityId,
            "districtId" => $districtId,
            "address" => 'Test address',
            "petsId" => [
                $petId
            ],
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
                "address" => 'Test address@/#',
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
                ['propertyPath' => 'address', 'message' => 'Address contains invalid characters'],
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
        $district = DistrictsFactory::createOne(['city' => $city]);
        $districtId = $district->getId();
        $pet = PetsFactory::createOne();
        $petId = $pet->getId();

        static::createClient()->request('PUT', '/api/v1/masters/' . $masterId, [
            'json' => [
                "name" => "Put",
                "surname" => "Test",
                "cityId" => $cityId,
                "districtId" => $districtId,
                "address" => 'Test address',
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
            "avgRating" => 0,
            "cityId" => $cityId,
            "districtId" => $districtId,
            "address" => 'Test address',
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

    private function indexMaster(string $name, string $surname): void
    {
        MastersFactory::createOne(['name' => $name, 'surname' => $surname]);
        $client = static::getContainer()->get('doctrine')->getRepository(Masters::class)->findOneBy(
            ['name' => $name, 'surname' => $surname]
        );

        $elasticsearchClient = static::getContainer()->get(Client::class);
        $elasticsearchClient->index([
            'index' => 'masters',
            'id' => $client->getId(),
            'body' => ['name' => $client->getName(), 'surname' => $client->getSurname()],
        ]);
        $elasticsearchClient->indices()->refresh(['index' => 'masters']);
    }
}
