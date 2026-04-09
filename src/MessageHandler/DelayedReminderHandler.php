<?php

namespace App\MessageHandler;

use App\Message\DelayedReminder;
use App\Repository\BookingsRepository;
use App\Service\NotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DelayedReminderHandler
{
    public function __construct(
        private BookingsRepository $repository,
        private NotificationService $notifications
    ) {}

    public function __invoke(DelayedReminder $message): void
    {
        $booking = $this->repository->find($message->getBookingId());

        if ((string)$booking->getStatus()->value !== 'Approved') {
            return;
        }

        $context = [
            'masterName'   => $booking->getIdMaster()->getName() . ' ' . $booking->getIdMaster()->getSurname(),
            'clientName'   => $booking->getIdClient()->getName() . ' ' . $booking->getIdClient()->getSurname(),
            'petBreed'     => $booking->getPet()->getBreed(),
            'petSpice'     => $booking->getPet()->getSpice()->value,
            'clientPhone'  => $booking->getIdClient()->getPhone(),
            'clientEmail'  => $booking->getIdClient()->getEmail(),
            'masterPhone'  => $booking->getIdMaster()->getPhone(),
            'masterEmail'  => $booking->getIdMaster()->getEmail(),
            'servicesList' => implode(', ', array_map(fn($s) => $s->getName(), $booking->getIdServices()->toArray())),
        ];

        match ($message->getRecipientType()) {
            'master' => $this->notifications->sendMasterReminder($booking, $context),
            'client' => $this->notifications->sendClientReminder($booking, $context),
            'rating' => $this->notifications->sendClientRatingRequest($booking, $context),
        };
    }
}
