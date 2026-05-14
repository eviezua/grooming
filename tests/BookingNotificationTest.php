<?php

namespace App\Tests;

use App\Entity\Clients;
use App\Entity\Masters;
use App\Enum\Status;
use App\Factory\BookingsFactory;
use App\Factory\CitiesFactory;
use App\Factory\ClientsFactory;
use App\Factory\DistrictsFactory;
use App\Factory\MastersFactory;
use App\Factory\PetsFactory;
use App\Factory\ServicesFactory;
use App\Message\DelayedReminder;
use App\MessageHandler\DelayedReminderHandler;
use App\Service\TelegramService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group booking
 * @group functional
 */

class BookingNotificationTest extends WebTestCase
{
    use ResetDatabase, Factories;

    public function testPostBookingSendsNotifications(): void
    {
        $client = static::createClient();

        $telegramMock = $this->createMock(TelegramService::class);
        $telegramMock->expects($this->atLeast(2))->method('sendMessage');

        static::getContainer()->set(TelegramService::class, $telegramMock);

        $master = MastersFactory::createOne(['telegramChatId' => '123456789', 'status' => Status::Approved])->_real();
        $data = $this->prepareData($master);

        $clientEntity = static::getContainer()->get('doctrine')->getRepository(Clients::class)->find($data['clientId']);
        $clientEntity->setTelegramChatId('987654321');
        static::getContainer()->get('doctrine')->getManager()->flush();

        $clientEmail = $clientEntity->getEmail();

        $client->request(
            'POST',
            '/api/v1/bookings',
            [], [],
            ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'],
            json_encode([
                "masterId"  => $data["masterId"],
                "services"  => $data['servicesIds'],
                "date"      => (new DateTime('+2 day'))->format('Y-m-d'),
                "timeStart" => "10:00:00",
                "timeStop"  => "13:00:00",
                "petId"     => $data['petId'],
                "clientId"  => $data['clientId']
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertEmailCount(2);

        $messages = $this->getMailerMessages();
        $this->assertEmailAddressContains($messages[2], 'To', $clientEmail);
    }

    public function testPatchBookingSendsNotifications(): void
    {
        $client = static::createClient();

        $telegramMock = $this->createMock(TelegramService::class);
        $telegramMock->expects($this->once())->method('sendMessage');
        static::getContainer()->set(TelegramService::class, $telegramMock);

        $master = MastersFactory::createOne([
            'telegramChatId' => '123456789',
            'email' => 'auth-master@test.com',
            'status' => Status::Approved
        ]);

        $myClient = ClientsFactory::createOne(['telegramChatId' => '987654321']);

        $booking = BookingsFactory::createOne([
            'idMaster' => $master,
            'idClient' => $myClient,
            'status' => Status::Awaiting,
            'date' => (new DateTime('+1 day'))
        ])->_real();

        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $token = $jwtManager->create($master->_real());
        $client->getCookieJar()->set(new Cookie('jwt', $token));

        $client->request(
            'PATCH',
            '/api/v1/bookings/' . $booking->getId(),
            [], [],
            [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            json_encode([
                "timeStart" => "14:00:00",
            ])
        );

        $this->assertResponseStatusCodeSame(200);

        $this->assertEmailCount(3);

        $messages = $this->getMailerMessages();
        $this->assertEmailHeaderSame($messages[4], 'Subject', 'Оновлення — Groomify');
    }

    public function testPostMasterAndConfirmEmail(): void
    {
        $client = static::createClient();

        $telegramMock = $this->createMock(TelegramService::class);
        $telegramMock->method('sendMessage');
        static::getContainer()->set(TelegramService::class, $telegramMock);

        $city = CitiesFactory::createOne();
        $district = DistrictsFactory::createOne(['city' => $city]);
        $pet = PetsFactory::createOne();

        $client->request('POST', '/api/v1/masters',
            [], [],
            ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'],
            json_encode([
                "name" => "Post",
                "surname" => "Test",
                "cityId" => $city->getId(),
                "districtId" => $district->getId(),
                "address" => 'Test address',
                "petsId" => [$pet->getId()],
                "password" => "password",
                "email" => "new-master@test.com",
                "phone" => "+12523957776",
                "photo" => "photo.jpg",
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $this->assertEmailCount(1);
        $messages = $this->getMailerMessages();
        $emailBody = $messages[0]->getHtmlBody();

        preg_match('/token=([^"& \n]+)/', $emailBody, $matches);
        $token = $matches[1] ?? null;

        $this->assertNotNull($token, 'Токен не знайдено в тексті листа!');

        $client->request('GET', '/confirm-email?token=' . $token);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseHeaderSame('Location', '/en/join?action=login&success=1');
    }

    public function testDelayedReminderSendsTelegram(): void
    {
        static::createClient();

        $telegramMock = $this->createMock(TelegramService::class);
        $telegramMock->expects($this->once())->method('sendMessage');
        static::getContainer()->set(TelegramService::class, $telegramMock);

        $booking = BookingsFactory::createOne([
            'status' => Status::Approved,
            'idClient' => ClientsFactory::createOne(['telegramChatId' => '987654321']),
            'idMaster' => MastersFactory::createOne(['telegramChatId' => '123456789', 'status' => Status::Approved]),
        ])->_real();

        $handler = static::getContainer()->get(DelayedReminderHandler::class);

        $message = new DelayedReminder(
            $booking->getId(),
            'client'
        );

        $handler($message);
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
}
