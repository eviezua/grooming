<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Schedule;
use App\Factory\MastersFactory;
use App\Factory\ScheduleFactory;
use DateTime;
use DateTimeZone;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group schedule
 * @group api
 */
class ScheduleApiTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        ScheduleFactory::createMany(100);

        static::createClient()->request('GET', 'api/v1/schedules');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Schedule',
            '@id' => '/api/v1/schedules',
            '@type' => 'Collection',
            'totalItems' => 100
        ]);
    }

    public function testGetSchedule(): void
    {
        $schedule = ScheduleFactory::createOne();
        $scheduleId = $schedule->getId();

        static::createClient()->request('GET', "/api/v1/schedules/$scheduleId");

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Schedule',
            '@id' => "/api/v1/schedules/$scheduleId",
            '@type' => 'Schedule',
        ]);
    }

    public function testPostSchedule(): void
    {
        $today = new DateTime('now', new DateTimeZone('UTC'));
        $dayOfWeek = $today->format('l');
        $master = MastersFactory::createOne();
        $masterId = $master->getId();

        $client = static::createClient();

        $client->request('POST', '/api/v1/schedules', [
            'json' => [
                'dayOfweek' => $dayOfWeek,
                'start_time' => '15:00:00',
                'stop_time' => '18:00:00',
                'masterId' => $masterId,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            'dayOfweek' => $dayOfWeek,
            'masterId' => $masterId
        ]);
        $this->assertEquals('15:00:00', $responseData['start_time']['time']);
        $this->assertEquals('18:00:00', $responseData['stop_time']['time']);
    }

    public function testPostInvalidSchedule(): void
    {
        static::createClient()->request('POST', '/api/v1/schedules', [
            'json' => [
                'dayOfweek' => 'blabla',
                'start_time' => '00.50',
                'stop_time' => '20 00',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'dayOfweek', 'message' => 'Invalid day of the week.'],
                ['propertyPath' => 'start_time', 'message' => 'The start time must be in the format HH:MM:SS.'],
                ['propertyPath' => 'stop_time', 'message' => 'The stop time must be in the format HH:MM:SS.'],
                ['propertyPath' => 'masterId', 'message' => 'Master ID cannot be empty.']
            ],
        ]);
    }

    public function testPutSchedule(): void
    {
        $schedule = ScheduleFactory::createOne();
        $scheduleId = $schedule->getId();
        $today = new DateTime('now', new DateTimeZone('UTC'));
        $dayOfWeek = $today->format('l');
        $master = MastersFactory::createOne();
        $masterId = $master->getId();

        $client = static::createClient();

        $client->request('PUT', '/api/v1/schedules/' . $scheduleId, [
            'json' => [
                'dayOfweek' => $dayOfWeek,
                'start_time' => '15:00:00',
                'stop_time' => '18:00:00',
                'masterId' => $masterId,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            'dayOfweek' => $dayOfWeek,
            'masterId' => $masterId
        ]);

        $this->assertEquals('15:00:00', $responseData['start_time']['time']);
        $this->assertEquals('18:00:00', $responseData['stop_time']['time']);
    }

    public function testPatchSchedule(): void
    {
        $schedule = ScheduleFactory::createOne();
        $scheduleId = $schedule->getId();

        $client = static::createClient();

        $client->request('PATCH', '/api/v1/schedules/' . $scheduleId, [
            'json' => [
                'start_time' => '15:00:00',
                'stop_time' => '18:00:00'
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();

        $this->assertEquals('15:00:00', $responseData['start_time']['time']);
        $this->assertEquals('18:00:00', $responseData['stop_time']['time']);
    }

    public function testDeleteSchedule(): void
    {
        $schedule = ScheduleFactory::createOne();
        $scheduleId = $schedule->getId();

        static::createClient()->request('DELETE', '/api/v1/schedules/' . $scheduleId);

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Schedule::class)->findOneBy(
                ['id' => $scheduleId]
            )
        );
    }
}
