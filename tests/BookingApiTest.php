<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Bookings;
use App\Entity\Masters;
use App\Enum\Status;
use App\Factory\BookingsFactory;
use App\Factory\ClientsFactory;
use App\Factory\MastersFactory;
use App\Factory\PetsFactory;
use App\Factory\ServicesFactory;
use DateInterval;
use DateTime;
use DateTimeZone;
use Symfony\Component\BrowserKit\Cookie;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group booking
 * @group api
 */
class BookingApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        BookingsFactory::createMany(100);

        static::createClient()->request('GET', 'api/v1/bookings');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Booking',
            '@id' => '/api/v1/bookings',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testMasterGetCollection(): void
    {
        $email = 'master@test.com';
        $client = $this->createAuthenticatedClient($email);
        $master = MastersFactory::find(['email' => $email]);

        BookingsFactory::createMany(50, ['id_master' => $master]);
        BookingsFactory::createMany(50);

        $client->request('GET', '/api/v1/bookings');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 50]);
    }

    public function testGetCollectionWithDateFilter(): void
    {
        BookingsFactory::CreateMany(5, ['date' => new  DateTime('+1 day')]);
        BookingsFactory::CreateMany(5, ['date' => new  DateTime('+7 days')]);
        BookingsFactory::CreateMany(5, ['date' => new  DateTime('+14 days')]);

        static::createClient()->request(
            'GET',
            'api/v1/bookings?date[after]=' . (new DateTime('+2 days'))->format(
                'Y-m-d'
            ) . '&date[before]=' . (new DateTime('+10 days'))->format('Y-m-d')
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Booking',
            '@id' => '/api/v1/bookings',
            '@type' => 'Collection',
            'totalItems' => 5
        ]);
    }

    public function testGetCollectionWithTimeBetweenFilter(): void
    {
        BookingsFactory::CreateMany(10, function () {
            $hour = random_int(9, 13);
            $minute = random_int(0, 59);
            $second = random_int(0, 59);

            $timeStart = (new DateTime())->setTime($hour, $minute, $second);
            $interval = new DateInterval('PT' . random_int(30, 240) . 'M');
            $timeStop = (clone $timeStart)->add($interval);

            return [
                'time_start' => $timeStart,
                'time_stop' => $timeStop,
            ];
        });
        BookingsFactory::CreateMany(10, function () {
            $hour = random_int(18, 20);
            $minute = random_int(0, 59);
            $second = random_int(0, 59);

            $timeStart = (new DateTime())->setTime($hour, $minute, $second);
            $interval = new DateInterval('PT' . random_int(30, 180) . 'M');
            $timeStop = (clone $timeStart)->add($interval);

            return [
                'time_start' => $timeStart,
                'time_stop' => $timeStop,
            ];
        });

        static::createClient()->request('GET', 'api/v1/bookings?time_from=' . '09:00:00' . '&time_to=' . '18:00:00');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Booking',
            '@id' => '/api/v1/bookings',
            '@type' => 'Collection',
            'totalItems' => 10
        ]);
    }

    public function testGetCollectionWithClientIdFilter(): void
    {
        $client = ClientsFactory::createOne();
        $clientId = $client->getId();

        BookingsFactory::CreateMany(10, ['id_client' => $client]);
        BookingsFactory::createMany(10);

        static::createClient()->request('GET', 'api/v1/bookings?id_client.id[]=' . $clientId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Booking',
            '@id' => '/api/v1/bookings',
            '@type' => 'Collection',
            'totalItems' => 10
        ]);
    }

    public function testGetCollectionWithMasterIdFilter(): void
    {
        $master = MastersFactory::createOne(['status' => Status::Approved]);
        $masterId = $master->getId();

        BookingsFactory::CreateMany(10, ['id_master' => $master]);
        BookingsFactory::createMany(10);

        static::createClient()->request('GET', 'api/v1/bookings?id_master.id[]=' . $masterId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Booking',
            '@id' => '/api/v1/bookings',
            '@type' => 'Collection',
            'totalItems' => 10
        ]);
    }

    public function testGetBooking(): void
    {
        $email = 'viewer@test.com';
        $client = $this->createAuthenticatedClient($email);
        $master = MastersFactory::find(['email' => $email]);

        $booking = BookingsFactory::createOne(['id_master' => $master]);
        $bookingId = $booking->getId();

        $client->request('GET', "/api/v1/bookings/$bookingId");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Booking',
            '@id' => "/api/v1/bookings/$bookingId",
            '@type' => 'Booking',
        ]);
    }

    public function testPostBooking(): void
    {
        $tomorrow = new DateTime('+2 day', new DateTimeZone('UTC'));

        $data = $this->prepareData();

        $masterId = $data["masterId"];
        $servicesIds = $data['servicesIds'];
        $petId = $data['petId'];
        $clientId = $data['clientId'];

        $client = static::createClient();
        $client->request('POST', '/api/v1/bookings', [
            'json' => [
                "masterId" => $masterId,
                "services" => $servicesIds,
                "date" => $tomorrow->format('Y-m-d'),
                "timeStart" => "10:00:00",
                "timeStop" => "13:00:00",
                "petId" => $petId,
                "clientId" => $clientId
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            "masterId" => $masterId,
            "services" => $servicesIds,
            "petId" => $petId,
            "clientId" => $clientId
        ]);

        $this->assertEquals($tomorrow->format('Y-m-d'), $responseData['date']['date']);
        $this->assertEquals('10:00:00', $responseData['timeStart']['time']);
        $this->assertEquals('13:00:00', $responseData['timeStop']['time']);
    }

    public function testPostPastDateBooking(): void
    {
        $yesterday = new DateTime('-1 day', new DateTimeZone('UTC'));
        $yesterday = $yesterday->format('Y-m-d');

        $data = $this->prepareData();

        $masterId = $data["masterId"];
        $servicesIds = $data['servicesIds'];
        $petId = $data['petId'];
        $clientId = $data['clientId'];

        static::createClient()->request('POST', '/api/v1/bookings', [
            'json' => [
                "masterId" => $masterId,
                "services" => $servicesIds,
                "date" => $yesterday,
                "timeStart" => "10:00:00",
                "timeStop" => "13:00:00",
                "petId" => $petId,
                "clientId" => $clientId
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'date', 'message' => "Your \"$yesterday\" must be in future"],
                ['propertyPath' => 'timeStart', 'message' => "Your \"$yesterday 10:00:00\" must be in future"],
                ['propertyPath' => 'timeStop', 'message' => "Your \"$yesterday 13:00:00\" must be in future"],
            ],
        ]);
    }

    public function testPostUnaviliableBooking(): void
    {
        $tomorrow = new DateTime('+2 day', new DateTimeZone('UTC'));

        $data = $this->prepareData();

        $masterId = $data["masterId"];
        $servicesIds = $data['servicesIds'];
        $petId = $data['petId'];
        $clientId = $data['clientId'];

        $master = static::getContainer()->get('doctrine')->getRepository(Masters::class)->findOneBy(
            ['id' => $masterId]
        );

        BookingsFactory::createOne(
            [
                'id_master' => $master,
                'date' => $tomorrow,
                'timeStart' => new DateTime('10:00:00'),
                'timeStop' => new DateTime('13:00:00')
            ]
        );

        static::createClient()->request('POST', '/api/v1/bookings', [
            'json' => [
                "masterId" => $masterId,
                "services" => $servicesIds,
                "date" => $tomorrow->format('Y-m-d'),
                "timeStart" => "12:00:00",
                "timeStop" => "13:30:00",
                "petId" => $petId,
                "clientId" => $clientId
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'date', 'message' => "This time slot is already booked for the field: date."],
                [
                    'propertyPath' => 'timeStart',
                    'message' => "This time slot is already booked for the field: timeStart."
                ],
                ['propertyPath' => 'timeStop', 'message' => "This time slot is already booked for the field: timeStop."]
            ],
        ]);
    }

    public function testPutBooking(): void
    {
        $email = 'master_edit@test.com';
        $client = $this->createAuthenticatedClient($email);
        $master = MastersFactory::find(['email' => $email]);

        $tomorrow = new DateTime('+2 day', new DateTimeZone('UTC'));

        $booking = BookingsFactory::createOne(['id_master' => $master]);
        $bookingId = $booking->getId();

        $data = $this->prepareData($master);

        $masterId = $data["masterId"];
        $servicesIds = $data['servicesIds'];
        $petId = $data['petId'];
        $clientId = $data['clientId'];

        $client->request('PUT', '/api/v1/bookings/' . $bookingId, [
            'json' => [
                "masterId" => $masterId,
                "services" => $servicesIds,
                "date" => $tomorrow->format('Y-m-d'),
                "timeStart" => "10:00:00",
                "timeStop" => "13:00:00",
                "petId" => $petId,
                "clientId" => $clientId
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            "masterId" => $masterId,
            "services" => $servicesIds,
            "petId" => $petId,
            "clientId" => $clientId
        ]);

        $this->assertEquals($tomorrow->format('Y-m-d'), $responseData['date']['date']);
        $this->assertEquals('10:00:00', $responseData['timeStart']['time']);
        $this->assertEquals('13:00:00', $responseData['timeStop']['time']);
    }

    public function testPatchBooking(): void
    {
        $email = 'master_edit@test.com';
        $client = $this->createAuthenticatedClient($email);
        $master = MastersFactory::find(['email' => $email]);

        $tomorrow = new DateTime('+2 day', new DateTimeZone('UTC'));

        $booking = BookingsFactory::createOne(['id_master' => $master]);
        $bookingId = $booking->getId();

        $client->request('PATCH', '/api/v1/bookings/' . $bookingId, [
            'json' => [
                "date" => $tomorrow->format('Y-m-d'),
                "timeStart" => "10:00:00",
                "timeStop" => "13:00:00",
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();

        $this->assertEquals($tomorrow->format('Y-m-d'), $responseData['date']['date']);
        $this->assertEquals('10:00:00', $responseData['timeStart']['time']);
        $this->assertEquals('13:00:00', $responseData['timeStop']['time']);
    }

    public function testDeleteBooking(): void
    {
        $email = 'master_edit@test.com';
        $client = $this->createAuthenticatedClient($email);
        $master = MastersFactory::find(['email' => $email]);

        $booking = BookingsFactory::createOne(['id_master' => $master]);
        $bookingId = $booking->getId();

        $client->request('DELETE', '/api/v1/bookings/' . $bookingId);

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Bookings::class)->findOneBy(
                ['id' => $bookingId]
            )
        );
    }

    private function prepareData(?Masters $existingMaster = null): array
    {
        $master = $existingMaster ? (method_exists($existingMaster, '_real') ? $existingMaster->_real() : $existingMaster) : MastersFactory::createOne(['status' => Status::Approved])->_real();
        $masterId = $master->getId();

        $service = ServicesFactory::createMany(3);
        $servicesIds = array_map(fn($services) => $services->getId(), $service);

        $pet = PetsFactory::createOne();
        $petId = $pet->getId();

        $client = ClientsFactory::createOne();
        $clientId = $client->getId();

        return [
            'masterId' => $masterId,
            'servicesIds' => $servicesIds,
            'petId' => $petId,
            'clientId' => $clientId,
        ];
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
