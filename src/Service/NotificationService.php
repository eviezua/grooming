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

    public function sendAuthNotification(string $email, string $link, string $name = 'Майстер'): void
    {
        $this->sendEmail(
            $email,
            'Вітаємо в команді Groomify! ✂️🐾',
            'auth_registration.html.twig',
            [
                'authLink' => $link,
                'masterName' => $name
            ]
        );
    }

    public function sendMasterReminder(Bookings $booking, array $context): void
    {
        $master = $booking->getIdMaster();

        $this->sendTelegram($master->getTelegramChatId(), 'master_reminder.html.twig', $context);
    }

    public function sendClientReminder(Bookings $booking, array $context): void
    {
        $client = $booking->getIdClient();

        $this->sendTelegram($client->getTelegramChatId(), 'client_reminder.html.twig', $context);
    }

    public function sendAdminNewMasterNotification(array $masterData): void
    {
        $adminChatId = $_ENV['TELEGRAM_ADMIN_CHAT_ID'];

        $this->sendTelegram($adminChatId, 'admin_new_master.html.twig', ['master' => $masterData]);
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