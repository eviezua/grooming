<?php

namespace App\Service;

use App\Entity\Bookings;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Twig\Environment;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private ChatterInterface $chatter,
        private Environment $twig
    ) {}

    public function sendBookingUpdates(Bookings $booking, string $method, array $context): void
    {
        $client = $booking->getIdClient();
        $master = $booking->getIdMaster();

        if ($method === 'POST') {
            $this->sendEmail($client->getEmail(), 'Підтвердження — Groomify', 'new_booking.html.twig', $context);

            $this->sendTelegram($master->getTelegramChatId(), 'master_booking.html.twig', $context);
            $this->sendTelegram($client->getTelegramChatId(), 'client_booking.html.twig', $context);
        }

        if (in_array($method, ['PUT', 'PATCH'])) {
            $this->sendEmail($client->getEmail(), 'Оновлення — Groomify', 'update_booking.html.twig', $context);
            $this->sendTelegram($client->getTelegramChatId(), 'client_booking.html.twig', $context);
        }
    }

    private function sendEmail(string $to, string $subject, string $template, array $context): void
    {
        $email = (new NotificationEmail())
            ->subject($subject)
            ->to($to)
            ->htmlTemplate("notifications/emails/$template")
            ->context($context)
            ->embedFromPath('/app/public/uploads/us.png', 'logo-dog');

        $this->mailer->send($email);
    }

    private function sendTelegram(?string $chatId, string $template, array $context): void
    {
        if (!$chatId) return;

        $text = $this->twig->render("notifications/telegram/$template", $context);
        $message = new ChatMessage($text);
        $options = (new TelegramOptions())->chatId($chatId)->parseMode('');
        $message->options($options);

        $this->chatter->send($message);
    }
}