<?php

namespace App\Service;

use App\Repository\ClientsRepository;
use App\Repository\MastersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Notifier\Bridge\Telegram\Reply\Markup\Button\InlineKeyboardButton;
use Symfony\Component\Notifier\Bridge\Telegram\Reply\Markup\InlineKeyboardMarkup;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

class TelegramService
{
    public function __construct(
        private ClientsRepository $clientsRepository,
        private MastersRepository $mastersRepository,
        private EntityManagerInterface $em,
        private ChatterInterface $chatter
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

    public function sendMessage(string $chatId, string $text, ?array $keyboard = null): void
    {
        $message = new ChatMessage($text);
        $options = (new TelegramOptions())
            ->chatId($chatId)
            ->parseMode('HTML');

        if ($keyboard) {
            $markup = new InlineKeyboardMarkup();
            foreach ($keyboard['inline_keyboard'] as $row) {
                $buttonRow = [];
                foreach ($row as $btn) {
                    $buttonRow[] = (new InlineKeyboardButton($btn['text']))
                        ->callbackData($btn['callback_data']);
                }
                $markup->inlineKeyboard(...[$buttonRow]);
            }
            $options->replyMarkup($markup);
        }

        $message->options($options);
        $this->chatter->send($message);
    }

}