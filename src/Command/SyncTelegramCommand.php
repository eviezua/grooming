<?php

namespace App\Command;

use App\Service\TelegramService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:sync-telegram',
    description: 'Отримує контакти з Telegram і зберігає Chat ID в БД',
)]
class SyncTelegramCommand extends Command
{
    public function __construct(
        private TelegramService $telegramService,
        private ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $token = $this->params->get('telegram_token');
        $response = file_get_contents("https://api.telegram.org/bot$token/getUpdates");
        $data = json_decode($response, true);

        foreach ($data['result'] as $update) {
            if (isset($update['message']['contact'])) {
                $this->telegramService->syncContact(
                    $update['message']['contact'],
                    $update['message']['chat']['id']
                );
                $output->writeln('Знайдено контакт: ' . $update['message']['contact']['phone_number']);
            }
        }
        return Command::SUCCESS;
    }
}
