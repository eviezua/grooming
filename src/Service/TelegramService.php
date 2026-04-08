<?php

namespace App\Service;

use App\Repository\ClientsRepository;
use App\Repository\MastersRepository;
use Doctrine\ORM\EntityManagerInterface;

class TelegramService
{
    public function __construct(
        private ClientsRepository $clientsRepository,
        private MastersRepository $mastersRepository,
        private EntityManagerInterface $em
    ) {}

    public function syncContact(array $contactData, int $chatId): bool
    {
        $phone = preg_replace('/[^0-9]/', '', $contactData['phone_number']);
        $phoneWithPlus = '+' . $phone;

        $user = $this->clientsRepository->findOneBy(['phone' => $phoneWithPlus])
            ?? $this->mastersRepository->findOneBy(['phone' => $phoneWithPlus]);

        if ($user) {
            $user->setTelegramChatId((string)$chatId);
            $this->em->flush();
            return true;
        }

        return false;
    }
}