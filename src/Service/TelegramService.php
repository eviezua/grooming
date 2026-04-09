<?php

namespace App\Service;

use App\Repository\ClientsRepository;
use App\Repository\MastersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelegramService
{
    public function __construct(
        private ClientsRepository $clientsRepository,
        private MastersRepository $mastersRepository,
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $params
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

    /**
     * @TODO: needs refactoring! It's working only for Webhook now!
     **/

    public function sendMessage(string $chatId, string $text): void
    {
        $token = $this->params->get('telegram_token');

        $this->httpClient->request('POST', "https://api.telegram.org/bot$token/sendMessage", [
            'json' => [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ]
        ]);
    }

}