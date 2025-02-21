<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Bookings;
use App\Entity\Masters;
use App\Factory\BookingsFactory;
use App\Factory\ClientsFactory;
use App\Factory\MastersFactory;
use App\Factory\PetsFactory;
use App\Factory\ServicesFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

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
    public function testGetBooking(): void
    {
        $booking = BookingsFactory::createOne();
        $bookingId = $booking->getId();

        static::createClient()->request('GET', "/api/v1/bookings/$bookingId");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Booking',
            '@id' => "/api/v1/bookings/$bookingId",
            '@type' => 'Booking',
        ]);
    }
    public function testPostBooking(): void
    {
        $tomorrow = new \DateTime('+1 day', new \DateTimeZone('UTC'));

        $data = $this->prepareData();

        $masterId = $data["masterId"];
        $servicesIds = $data['servicesIds'];
        $petId = $data['petId'];
        $clientId = $data['clientId'];

        static::createClient()->request('POST', '/api/v1/bookings', [
            'json' => [
                "masterId" => $masterId,
                "services" => $servicesIds,
                "date" => $tomorrow->format('Y-m-d\TH:i:s+00:00'),
                "timeStart" => "10:00:00",
                "timeStop" => "13:00:00",
                "petId" => $petId,
                "clientId" => $clientId
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            "masterId" => $masterId,
            "services" => $servicesIds,
            'date' => $tomorrow->format('Y-m-d\TH:i:s+00:00'),
            'timeStart' => $tomorrow->setTime(10, 0)->format('Y-m-d\TH:i:sP'),
            'timeStop' => $tomorrow->setTime(13, 0)->format('Y-m-d\TH:i:sP'),
            "petId" => $petId,
            "clientId" => $clientId
        ]);
    }
    public function testPostPastDateBooking(): void
    {
        $yesterday = new \DateTime('-1 day', new \DateTimeZone('UTC'));

        $data = $this->prepareData();

        $masterId = $data["masterId"];
        $servicesIds = $data['servicesIds'];
        $petId = $data['petId'];
        $clientId = $data['clientId'];

        static::createClient()->request('POST', '/api/v1/bookings', [
            'json' => [
                "masterId" => $masterId,
                "services" => $servicesIds,
                "date" => $yesterday->format('Y-m-d\TH:i:s+00:00'),
                "timeStart" => "10:00:00",
                "timeStop" => "13:00:00",
                "petId" => $petId,
                "clientId" => $clientId
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);
    }
    public function testPostUnaviliableBooking(): void
    {
        $data = $this->prepareData();

        $masterId = $data["masterId"];
        $servicesIds = $data['servicesIds'];
        $petId = $data['petId'];
        $clientId = $data['clientId'];

        $master = static::getContainer()->get('doctrine')->getRepository(Masters::class)->findOneBy(
            ['id' => $masterId]
        );

        BookingsFactory::createOne(['id_master' => $master, 'date' => new \DateTime(), 'timeStart' => new \DateTime('10:00:00'), 'timeStop' => new \DateTime('13:00:00')]);

        static::createClient()->request('POST', '/api/v1/bookings', [
            'json' => [
                "masterId" => $masterId,
                "services" => $servicesIds,
                "date" => "2025-01-01",
                "timeStart" => "11:00:00",
                "timeStop" => "13:00:00",
                "petId" => $petId,
                "clientId" => $clientId
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);
    }
    public function testPutBooking(): void
    {
        $tomorrow = new \DateTime('+1 day', new \DateTimeZone('UTC'));

        $booking = BookingsFactory::createOne();
        $bookingId = $booking->getId();

        $data = $this->prepareData();

        $masterId = $data["masterId"];
        $servicesIds = $data['servicesIds'];
        $petId = $data['petId'];
        $clientId = $data['clientId'];

        static::createClient()->request('PUT', '/api/v1/bookings/' . $bookingId, [
            'json' => [
                "masterId" => $masterId,
                "services" => $servicesIds,
                "date" => $tomorrow->format('Y-m-d\TH:i:s+00:00'),
                "timeStart" => "10:00:00",
                "timeStop" => "13:00:00",
                "petId" => $petId,
                "clientId" => $clientId
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            "masterId" => $masterId,
            "services" => $servicesIds,
            'date' => $tomorrow->format('Y-m-d\TH:i:s+00:00'),
            'timeStart' => $tomorrow->setTime(10, 0)->format('Y-m-d\TH:i:sP'),
            'timeStop' => $tomorrow->setTime(13, 0)->format('Y-m-d\TH:i:sP'),
            "petId" => $petId,
            "clientId" => $clientId
        ]);
    }
    public function testPatchBooking(): void
    {
        $tomorrow = new \DateTime('+1 day', new \DateTimeZone('UTC'));

        $booking = BookingsFactory::createOne();
        $bookingId = $booking->getId();

        static::createClient()->request('PATCH', '/api/v1/bookings/' . $bookingId, [
            'json' => [
                "date" => $tomorrow->format('Y-m-d\TH:i:s+00:00'),
                "timeStart" => "10:00:00",
                "timeStop" => "13:00:00",
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            'date' => $tomorrow->format('Y-m-d\TH:i:s+00:00'),
            'timeStart' => $tomorrow->setTime(10, 0)->format('Y-m-d\TH:i:sP'),
            'timeStop' => $tomorrow->setTime(13, 0)->format('Y-m-d\TH:i:sP')
        ]);
    }
    private function prepareData(): array
    {
        $master = MastersFactory::createOne();
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
    public function testDeleteBooking(): void
    {
        $booking = BookingsFactory::createOne();
        $bookingId = $booking->getId();

        static::createClient()->request('DELETE', '/api/v1/bookings/' . $bookingId);

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Bookings::class)->findOneBy(
                ['id' => $bookingId]
            )
        );
    }
}
