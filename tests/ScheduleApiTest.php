<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Masters;
use App\Entity\Schedule;
use App\Enum\Weekdays;
use App\Factory\MastersFactory;
use App\Factory\ScheduleFactory;
use DateTime;
use DateTimeZone;
use Symfony\Component\BrowserKit\Cookie;
use Zenstruck\Foundry\Persistence\Proxy;
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

    public function testGetCollectionWithMasterIdFilter(): void
    {
        $master = MastersFactory::createOne();
        $masterId = $master->getId();

        ScheduleFactory::CreateMany(10, ['master' => $master]);
        ScheduleFactory::CreateMany(10);

        static::createClient()->request('GET', 'api/v1/schedules?master.id[]=' . $masterId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Schedule',
            '@id' => '/api/v1/schedules',
            '@type' => 'Collection',
            'totalItems' => 10
        ]);
    }

    public function testGetBySearchDayOfWeekFilter(): void
    {
        $client = static::createClient();

        $allowedDays = array_filter(Weekdays::cases(), fn(Weekdays $s) => $s !== Weekdays::Monday);

        ScheduleFactory::createMany(10, ['dayOfweek' => Weekdays::Monday]);
        ScheduleFactory::createMany(10, function() use ($allowedDays) {
            return [
                'dayOfweek' => $allowedDays[array_rand($allowedDays)],
            ];
        });

        $client->request('GET', 'api/v1/schedules?dayOfweek[]=Monday');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/v1/contexts/Schedule',
            '@id' => '/api/v1/schedules',
            '@type' => 'Collection',
            'totalItems' => 10,
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
        $dayOfWeek = Weekdays::from($today->format('l'));
        $master = MastersFactory::createOne();
        $masterId = $master->getId();

        $client = $this->createAuthenticatedClient($master);

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
            'dayOfweek' => $dayOfWeek->value,
            'masterId' => $masterId
        ]);
        $this->assertEquals('15:00:00', $responseData['start_time']['time']);
        $this->assertEquals('18:00:00', $responseData['stop_time']['time']);
    }

    public function testPostInvalidSchedule(): void
    {
        $master = MastersFactory::createOne();
        $masterId = $master->getId();
        $client = $this->createAuthenticatedClient($master);

        $client->request('POST', '/api/v1/schedules', [
            'json' => [
                'dayOfweek' => 'blabla',
                'start_time' => '00.50',
                'stop_time' => '20 00',
                'masterId' => $masterId,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'dayOfweek', 'message' => 'The value you selected is not a valid choice.'],
                ['propertyPath' => 'start_time', 'message' => 'The start time must be in the format HH:MM:SS.'],
                ['propertyPath' => 'stop_time', 'message' => 'The stop time must be in the format HH:MM:SS.'],
            ],
        ]);
    }

    public function testPutSchedule(): void
    {
        $today = new DateTime('now', new DateTimeZone('UTC'));
        $dayOfWeek = $today->format('l');
        $master = MastersFactory::createOne();
        $masterId = $master->getId();
        $schedule = ScheduleFactory::createOne(['master' => $master]);
        $scheduleId = $schedule->getId();

        $client = $this->createAuthenticatedClient($master);

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
        $master = MastersFactory::createOne();
        $schedule = ScheduleFactory::createOne(['master' => $master]);
        $scheduleId = $schedule->getId();

        $client = $this->createAuthenticatedClient($master);

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
        $master = MastersFactory::createOne();
        $schedule = ScheduleFactory::createOne(['master' => $master]);
        $scheduleId = $schedule->getId();

        $client = $this->createAuthenticatedClient($master);

        $client->request('DELETE', '/api/v1/schedules/' . $scheduleId);

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Schedule::class)->findOneBy(
                ['id' => $scheduleId]
            )
        );
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
                    'roles' => $isAdmin ? ['ROLE_ADMIN'] : ['ROLE_MASTER']
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
