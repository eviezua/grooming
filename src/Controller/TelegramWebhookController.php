<?php

namespace App\Controller;

use App\Service\RatingService;
use App\Service\TelegramService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TelegramWebhookController extends AbstractController
{
    #[Route('/webhook/telegram', name: 'telegram_webhook', methods: ['POST'])]
    public function handle(
        Request $request,
        TelegramService $telegramService,
        RatingService $ratingService,
        LoggerInterface $logger
    ): Response {
        $data = $request->toArray();

        $logger->info('Telegram Update Received', $data);

        if (isset($data['message']['contact'])) {
            $telegramService->syncContact(
                $data['message']['contact'],
                $data['message']['chat']['id']
            );
            return new Response('Contact synced');
        }

        if (isset($data['callback_query'])) {
            $callbackData = $data['callback_query']['data'];

            if (str_starts_with($callbackData, 'rate_')) {
                $parts = explode('_', $callbackData);

                if (count($parts) === 3) {
                    $score = (int)$parts[1];
                    $bookingId = (int)$parts[2];

                    $ratingService->addReviewFromTelegram($bookingId, $score);

                }
            }
        }
        if (isset($data['message']['text'])) {
            $chatId = (string)$data['message']['chat']['id'];
            $text = $data['message']['text'];

            $lastReview = $ratingService->findLastReviewWithoutComment($chatId);

            if ($lastReview) {
                $lastReview->setComment($text);
                $ratingService->saveReview($lastReview);

                $telegramService->sendMessage($chatId, "Дякуємо! Ваш відгук збережено: \"$text\"");
                return new Response('Comment saved');
            }
        }

        return new Response('OK');
    }
}