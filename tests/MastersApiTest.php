<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Masters;
use App\Enum\Weekdays;
use App\Factory\BookingsFactory;
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

    public function testMastersAvailableTimeFilter(): void
    {
        $today = new \DateTime('today');
        $dayOfWeek = Weekdays::from($today->format('l'));
        $dateStr = $today->format('Y-m-d');

        $master1 = MastersFactory::createOne();
        $master2 = MastersFactory::createOne();

        ScheduleFactory::createOne([
            'master' => $master1,
            'dayOfweek' => $dayOfWeek,
            'start_time' => new \DateTime('08:00:00'),
            'stop_time' => new \DateTime('13:00:00'),
        ]);

        ScheduleFactory::createOne([
            'master' => $master2,
            'dayOfweek' => $dayOfWeek,
            'start_time' => new \DateTime('09:00:00'),
            'stop_time' => new \DateTime('14:00:00'),
        ]);

        BookingsFactory::createOne([
            'id_master' => $master1,
            'date' => $today,
            'time_start' => new \DateTime('09:00:00'),
            'time_stop' => new \DateTime('10:00:00'),
        ]);

        BookingsFactory::createOne([
            'id_master' => $master2,
            'date' => $today,
            'time_start' => new \DateTime('12:00:00'),
            'time_stop' => new \DateTime('13:00:00'),
        ]);

        $client = static::createClient();

        $client->request('GET', "/api/v1/masters?date_from={$dateStr}&start_time=11:00&end_time=12:00");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 2
        ]);

        $client->request('GET', "/api/v1/masters?date_from={$dateStr}&start_time=09:00&end_time=10:00");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 1
        ]);

        $client->request('GET', "/api/v1/masters?date_from={$dateStr}&start_time=12:00&end_time=13:00");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 1
        ]);
    }

    public function testMasterAvailableTimeFilterRange(): void
    {
        $today = new \DateTime('today');
        $tomorrow = (clone $today)->modify('+1 day');
        $plus5 = (clone $today)->modify('+5 day');
        $plus7 = (clone $today)->modify('+7 day');
        $plus8 = (clone $today)->modify('+8 day');
        $plus10 = (clone $today)->modify('+10 day');

        $client = static::createClient();

        $master1 = MastersFactory::createOne();
        $master2 = MastersFactory::createOne();

        foreach ([$today, $tomorrow, $plus5] as $date) {
            $dayOfWeek = Weekdays::from($date->format('l'));
            ScheduleFactory::createOne([
                'master' => $master1,
                'dayOfweek' => $dayOfWeek,
                'start_time' => new \DateTime('08:00:00'),
                'stop_time' => new \DateTime('17:00:00'),
            ]);
            ScheduleFactory::createOne([
                'master' => $master2,
                'dayOfweek' => $dayOfWeek,
                'start_time' => new \DateTime('09:00:00'),
                'stop_time' => new \DateTime('18:00:00'),
            ]);
        }

        BookingsFactory::createOne([
            'id_master' => $master1,
            'date' => $today,
            'time_start' => new \DateTime('10:00:00'),
            'time_stop' => new \DateTime('11:00:00'),
        ]);

        BookingsFactory::createOne([
            'id_master' => $master2,
            'date' => $plus5,
            'time_start' => new \DateTime('12:00:00'),
            'time_stop' => new \DateTime('13:00:00'),
        ]);

        BookingsFactory::createOne([
            'id_master' => $master2,
            'date' => $tomorrow,
            'time_start' => new \DateTime('10:00:00'),
            'time_stop' => new \DateTime('13:00:00'),
        ]);

        BookingsFactory::createOne([
            'id_master' => $master1,
            'date' => $plus5,
            'time_start' => new \DateTime('12:00:00'),
            'time_stop' => new \DateTime('15:00:00'),
        ]);

        BookingsFactory::createOne([
            'id_master' => $master1,
            'date' => $plus5,
            'time_start' => new \DateTime('12:00:00'),
            'time_stop' => new \DateTime('15:00:00'),
        ]);

        BookingsFactory::createOne([
            'id_master' => $master1,
            'date' => $plus7,
            'time_start' => new \DateTime('12:00:00'),
            'time_stop' => new \DateTime('13:30:00'),
        ]);

        BookingsFactory::createOne([
            'id_master' => $master1,
            'date' => $plus8,
            'time_start' => new \DateTime('13:30:00'),
            'time_stop' => new \DateTime('15:00:00'),
        ]);

        $client->request('GET', "/api/v1/masters?date_from={$today->format('Y-m-d')}&date_to={$plus5->format('Y-m-d')}&start_time=09:00&end_time=10:00");
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 2]);

        $client->request('GET', "/api/v1/masters?date_from={$plus5->format('Y-m-d')}&date_to={$today->format('Y-m-d')}&start_time=09:00&end_time=10:00");
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 2]);

        $client->request('GET', "/api/v1/masters?date_from={$today->format('Y-m-d')}&date_to={$tomorrow->format('Y-m-d')}&start_time=10:30&end_time=11:30");
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 2]);

        $client->request('GET', "/api/v1/masters?date_from={$tomorrow->format('Y-m-d')}&date_to={$plus5->format('Y-m-d')}&start_time=12:00&end_time=13:00");
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 1]);

        $dayOfWeekPlus10 = Weekdays::from($plus10->format('l'));
        ScheduleFactory::createOne([
            'master' => $master1,
            'dayOfweek' => $dayOfWeekPlus10,
            'start_time' => new \DateTime('13:00:00'),
            'stop_time' => new \DateTime('17:00:00'),
        ]);
        ScheduleFactory::createOne([
            'master' => $master2,
            'dayOfweek' => $dayOfWeekPlus10,
            'start_time' => new \DateTime('13:00:00'),
            'stop_time' => new \DateTime('17:00:00'),
        ]);

        BookingsFactory::createOne([
            'id_master' => $master1,
            'date' => $plus10,
            'time_start' => new \DateTime('13:00:00'),
            'time_stop' => new \DateTime('14:00:00'),
        ]);

        $client->request('GET', "/api/v1/masters?date_from={$plus5->format('Y-m-d')}&date_to={$plus10->format('Y-m-d')}&start_time=13:00&end_time=14:00");
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 1]);

        $client->request('GET', "/api/v1/masters?date_from={$today->format('Y-m-d')}&date_to={$plus10->format('Y-m-d')}&start_time=16:00&end_time=17:00");
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 2]);

        $client->request('GET', "/api/v1/masters?date_from={$today->format('Y-m-d')}&date_to={$plus5->format('Y-m-d')}&start_time=07:00&end_time=18:00");
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 0]);

    }

    public function testMastersRatingFilter(): void
    {
        MastersFactory::createOne(['avgRating' => 1.00]);
        MastersFactory::createOne(['avgRating' => 2.00]);
        MastersFactory::createOne(['avgRating' => 3.00]);
        MastersFactory::createOne(['avgRating' => 4.00]);
        MastersFactory::createOne(['avgRating' => 5.00]);

        $client = static::createClient();

        $client->request('GET', '/api/v1/masters?avgRating[gt]=2&avgRating[lt]=4');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Master',
            '@id' => '/api/v1/masters',
            '@type' => 'Collection',
            'totalItems' => 1
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
                "address" => 'Test address',
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
            "address" => 'Test address',
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
