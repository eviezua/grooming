<?php

namespace App\MessageHandler;

use App\Message\BookingNotification;
use App\Repository\BookingsRepository;
use App\Service\NotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class BookingNotificationHandler
{
    public function __construct(
        private BookingsRepository $repository,
        private NotificationService $notifications
    ) {}

    public function __invoke(BookingNotification $message): void
    {
        if ($message->method === 'DELETE') return;

        $booking = $this->repository->find($message->bookingId);
        if (!$booking) return;

        $context = $this->buildContext($booking, $message->method);

        $this->notifications->sendBookingUpdates($booking, $message->method, $context);
    }

    private function buildContext($booking, $method): array
    {
        $master = $booking->getIdMaster();
        $client = $booking->getIdClient();
        $pet = $booking->getPet();

        $servicesNames = [];
        foreach ($booking->getIdServices() as $service) {
            $servicesNames[] = $service->getName();
        }

        return [
            'method'       => $method,
            'clientName'   => $client->getName() . ' ' . $client->getSurname(),
            'clientPhone'  => $client->getPhone(),
            'clientEmail'  => $client->getEmail(),
            'masterName'   => $master->getName() . ' ' . $master->getSurname(),
            'masterPhone'  => $master->getPhone(),
            'masterEmail'  => $master->getEmail(),
            'petBreed'     => $pet ? $pet->getBreed() : 'Ваш улюбленець',
            'petSpice'     => $pet ? $pet->getSpice()->value : '',
            'servicesList' => implode(', ', $servicesNames) ?: 'Послуги з догляду',
            'date'         => $booking->getDate()->format('d.m.Y'),
            'startTime'    => $booking->getTimeStart()->format('H:i'),
            'endTime'      => $booking->getTimeStop()->format('H:i'),
            'status'       => $booking->getStatus()->value,
        ];
    }
}
