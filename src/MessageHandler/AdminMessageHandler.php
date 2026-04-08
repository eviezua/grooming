<?php

namespace App\MessageHandler;

use App\Message\AdminMessage;
use App\Service\NotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AdminMessageHandler
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function __invoke(AdminMessage $message): void
    {
        $this->notificationService->sendAdminNewMasterNotification($message->getData());
    }
}
