<?php

namespace App\MessageHandler;

use App\Message\CheckUpcomingBookingsTrigger;
use App\Message\DelayedReminder;
use App\Repository\BookingsRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class CheckUpcomingBookingsTriggerHandler
{
    public function __construct(
        private BookingsRepository $repository,
        private MessageBusInterface $bus
    ) {}

    public function __invoke(CheckUpcomingBookingsTrigger $message): void
    {
        $targetTime = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Kyiv')))
            ->modify('+10 minutes');

        $bookings = $this->repository->findForNotification(
            $targetTime->format('Y-m-d'),
            $targetTime->format('H:i')
        );

        foreach ($bookings as $booking) {
            $this->bus->dispatch(new DelayedReminder($booking->getId(), 'master'), [
                new AmqpStamp('scheduled')
            ]);

            $this->bus->dispatch(new DelayedReminder($booking->getId(), 'client'), [
                new AmqpStamp('scheduled')
            ]);
        }
    }
}
