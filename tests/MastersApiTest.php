<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Masters;
use App\Enum\Status;
use App\Enum\Weekdays;
use App\Factory\BookingsFactory;
use App\Factory\CitiesFactory;
use App\Factory\DistrictsFactory;
use App\Factory\MastersFactory;
use App\Factory\MastersServicesFactory;
use App\Factory\PetsFactory;
use App\Factory\ScheduleFactory;
use App\Factory\ServicesFactory;
use Elastic\Elasticsearch\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group master
 * @group api
 */
class MastersApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testMasterLogin(): void
    {
        $client = static::createClient();

        $password = 'password123';
        MastersFactory::createOne([
            'email' => 'master@groomify.com',
            'password' => $password,
            'status' => Status::Approved
        ]);

        $response = $client->request('POST', '/api/v1/login_check', [
            'json' => [
                'email' => 'master@groomify.com',
                'password' => $password,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('token', $data);

        $token = $data['token'];

        $client->request('GET', '/api/v1/clients', [
            'auth_bearer' => $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testMasterInvalidStatusLogin(): void
    {
        $client = static::createClient();

        $password = 'password123';
        $master = MastersFactory::createOne([
            'email' => 'master@groomify.com',
            'password' => $password,
            'status' => Status::Awaiting
        ]);

        $client->request('POST', '/api/v1/login_check', [
            'json' => [
                'email' => 'master@groomify.com',
                'password' => $password,
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);

        $master->setStatus(Status::Rejected);

        $client->request('POST', '/api/v1/login_check', [
            'json' => [
                'email' => 'master@groomify.com',
                'password' => $password,
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);

        $master->setStatus(Status::Inactive);

        $client->request('POST', '/api/v1/login_check', [
            'json' => [
                'email' => 'master@groomify.com',
                'password' => $password,
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }


    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        MastersFactory::createOne(['email' => 'real@test.com', 'password' => 'real_pass']);

        $client->request('POST', '/api/v1/login_check', [
            'json' => [
                'email' => 'real@test.com',
                'password' => 'wrong_pass',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

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

    public function testMasterPriceFilter(): void
    {
        $masterCheap = MastersFactory::createOne(['name' => 'Cheap', 'surname' => 'Master']);
        $masterExpensive = MastersFactory::createOne(['name' => 'Expensive', 'surname' => 'Master']);

        $service1 = ServicesFactory::createOne(['id' => 1]);
        $service2 = ServicesFactory::createOne(['id' => 2]);

        MastersServicesFactory::createOne(['master' => $masterExpensive, 'service' => $service1, 'price' => 125]);
        MastersServicesFactory::createOne(['master' => $masterExpensive, 'service' => $service2, 'price' => 200]);

        MastersServicesFactory::createOne(['master' => $masterCheap, 'service' => $service1, 'price' => 150]);
        MastersServicesFactory::createOne(['master' => $masterCheap, 'service' => $service2, 'price' => 75]);

        $client = static::createClient();

        $response = $client->request('GET', '/api/v1/masters?services[]=1&services[]=2&budget=300');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals(1, $data['totalItems']);
        $this->assertEquals('Cheap', $data['member'][0]['name']);

        $response = $client->request('GET', '/api/v1/masters?services[]=1&services[]=2&budget=350&order[totalPrice]=asc');
        $data = $response->toArray();

        $this->assertEquals(2, $data['totalItems']);
        $this->assertEquals('Cheap', $data['member'][0]['name']);
        $this->assertEquals('Expensive', $data['member'][1]['name']);

        $response = $client->request('GET', '/api/v1/masters?services[]=1&services[]=2&budget=350&order[totalPrice]=desc');
        $data = $response->toArray();

        $this->assertEquals('Expensive', $data['member'][0]['name']);
        $this->assertEquals('Cheap', $data['member'][1]['name']);

        ServicesFactory::createOne(['id' => 3]);
        $response = $client->request('GET', '/api/v1/masters?services[]=1&services[]=2&services[]=3&budget=1000');
        $data = $response->toArray();

        $this->assertEquals(0, $data['totalItems']);
    }

    public function testMastersOrderFilterByRating(): void
    {
        MastersFactory::createOne(['name' => 'LowRating', 'avgRating' => 1.2]);
        MastersFactory::createOne(['name' => 'MidRating', 'avgRating' => 3.5]);
        MastersFactory::createOne(['name' => 'HighRating', 'avgRating' => 4.9]);

        $client = static::createClient();

        $response = $client->request('GET', '/api/v1/masters?order[avgRating]=desc');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals(3, $data['totalItems']);
        $this->assertEquals('HighRating', $data['member'][0]['name'] );
        $this->assertEquals('LowRating', $data['member'][2]['name']);

        $response = $client->request('GET', '/api/v1/masters?order[avgRating]=asc');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals('LowRating', $data['member'][0]['name']);
        $this->assertEquals('HighRating', $data['member'][2]['name']);
    }

    public function testGetMeSuccess(): void
    {
        $email = 'me_test@groomify.com';
        $master = MastersFactory::createOne(['email' => $email, 'status' => Status::Approved]);
        $masterId = $master->getId();

        $client = $this->createAuthenticatedClient($master);

        $client->request('GET', '/api/v1/v1/master/me');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $masterId
        ]);
    }

    public function testGetMeUnauthorized(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/v1/master/me');

        $this->assertResponseStatusCodeSame(401);
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

    public function testPostInvalidInitialsMaster(): void
    {
        static::createClient()->request('POST', '/api/v1/masters', [
            'json' => [
                "name" => "Bot999",
                "surname" => "Test000",
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'name', 'message' => 'Invalid name'],
                ['propertyPath' => 'surname', 'message' => 'Invalid surname']
            ],
        ]);
    }

    public function testPostBotMaster(): void
    {
        static::createClient()->request('POST', '/api/v1/masters', [
            'json' => [
               "honeyPot" => "I am bot",
                "name" => "Post",
                "surname" => "Test",
                "address" => 'Test address',
                "password" => "password",
                "email" => "test@test.com",
                "phone" => "+12523957776",
                "photo" => "photo.jpg",
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPutMaster(): void
    {
        $master = MastersFactory::createOne(['status' => Status::Approved]);
        $masterId = $master->getId();
        $city = CitiesFactory::createOne();
        $cityId = $city->getId();
        $district = DistrictsFactory::createOne(['city' => $city]);
        $districtId = $district->getId();
        $pet = PetsFactory::createOne();
        $petId = $pet->getId();

        $client = $this->createAuthenticatedClient($master);

        $client->request('PUT', '/api/v1/masters/' . $masterId, [
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
                "email" => $master->getEmail(),
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
            "email" => $master->getEmail(),
            "phone" => "+12523957776",
            "photo" => "photo.jpg",
        ]);
    }

    public function testPatchMaster(): void
    {
        $master = MastersFactory::createOne(['status' => Status::Approved]);
        $masterId = $master->getId();

        $client = $this->createAuthenticatedClient($master);

        $client->request('PATCH', '/api/v1/masters/' . $masterId, [
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

    public function testMasterCannotEditAnotherMasterProfile(): void
    {
        $owner = MastersFactory::createOne(['status' => Status::Approved]);
        $intruder = MastersFactory::createOne(['status' => Status::Approved]);

        $client = $this->createAuthenticatedClient($intruder);

        $client->request('PATCH', '/api/v1/masters/' . $owner->getId(), [
            'json' => [
                "name" => "I am a Hacker",
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(403);

        $this->assertJsonContains([
            'detail' => 'Access Denied.',
        ]);
    }

    public function testAdminCanEditAnyMasterProfile(): void
    {
        $master = MastersFactory::createOne(['status' => Status::Approved]);
        $masterId = $master->getId();

        $adminClient = $this->createAuthenticatedClient('admin@test.com', true);

        $adminClient->request('PATCH', '/api/v1/masters/' . $masterId, [
            'json' => [
                "name" => "Adminoverwrite",
                "surname" => "Power",
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();

        $this->assertJsonContains([
            "name" => "Adminoverwrite",
            "surname" => "Power",
        ]);

        $updatedMaster = MastersFactory::repository()->find($masterId);
        $this->assertSame('Adminoverwrite', $updatedMaster->getName());
    }

    public function testUploadMasterPhoto(): void
    {
        $master = MastersFactory::createOne(['status' => Status::Approved]);
        $client = $this->createAuthenticatedClient($master);

        $tempFilePath = tempnam(sys_get_temp_dir(), 'test_photo') . '.png';
        file_put_contents($tempFilePath, 'fake image content');

        $uploadedFile = new UploadedFile(
            $tempFilePath,
            'test_photo.png',
            'image/png',
            null,
            true
        );

        $client->request('POST', '/api/v1/v1/masters/' . $master->getId() . '/photo', [
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
            'extra' => [
                'files' => [
                    'file' => $uploadedFile,
                ],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $master->getId(),
        ]);

        $data = $client->getResponse()->toArray();
        $this->assertNotNull($data['photo']);

    }

    public function testUploadPhotoAnotherMasterForbidden(): void
    {
        $owner = MastersFactory::createOne(['status' => Status::Approved]);
        $intruder = MastersFactory::createOne(['status' => Status::Approved]);
        $client = $this->createAuthenticatedClient($intruder);

        $tempFilePath = tempnam(sys_get_temp_dir(), 'test_photo') . '.png';
        file_put_contents($tempFilePath, 'fake image content');

        $uploadedFile = new UploadedFile(
            $tempFilePath,
            'test_photo.png',
            'image/png',
            null,
            true
        );

        $client->request('POST', '/api/v1/v1/masters/' . $owner->getId() . '/photo', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'file' => $uploadedFile,
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteMasterPhoto(): void
    {
        $master = MastersFactory::createOne(['photo' => 'old_photo.jpg', 'status' => Status::Approved]);
        $client = $this->createAuthenticatedClient($master);

        $client->request('DELETE', '/api/v1/v1/masters/' . $master->getId() . '/photo');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $master->getId(),
            'photo' => null,
        ]);

        $updatedMaster = MastersFactory::repository()->find($master->getId());
        $this->assertNull($updatedMaster->getPhoto());
    }

    public function testDeletePhotoUnauthorized(): void
    {
        $master = MastersFactory::createOne(['photo' => 'test.jpg', 'status' => Status::Approved]);
        $client = static::createClient();

        $client->request('DELETE', '/api/v1/v1/masters/' . $master->getId() . '/photo');

        $this->assertResponseStatusCodeSame(401);
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
