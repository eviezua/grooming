<?php

namespace App\MessageHandler;

use App\Message\AuthMessage;
use App\Repository\MastersRepository;
use App\Service\NotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AuthMessageHandler
{
    public function __construct(
        private MastersRepository $repository,
        private NotificationService $notifications,
        private string $frontendUrl
    ) {}

    public function __invoke(AuthMessage $message): void
    {
        $master = $this->repository->find($message->masterId);

        if (!$master) {
            return;
        }

        $confirmUrl = $this->frontendUrl . "/confirm-email?token=" . $message->token;

        $this->notifications->sendAuthNotification(
            $master->getEmail(),
            $confirmUrl,
            $master->getName(),
        );
    }
}
