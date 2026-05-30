<?php

namespace App\Command;

use App\Entity\Clients;
use App\Entity\Districts;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Entity\Services;
use App\Repository\CitiesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:init-elastic-indices'
)]
class InitElasticCommand extends Command
{
    public function __construct(
        private readonly CitiesRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Client $client
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->recreateCitiesIndex($output);
        $this->recreateDistrictsIndex($output);
        $this->recreateClientsIndex($output);
        $this->recreateMastersIndex($output);
        $this->recreatePetsIndex($output);
        $this->recreateServicesIndex($output);

        return Command::SUCCESS;
    }

    private function recreateCitiesIndex(OutputInterface $output): void
    {
        $indexName = 'cities';
        $this->createIndex($indexName, [
            'city' => [
                'type' => 'text',
                'analyzer' => 'ngram_analyzer',
                'search_analyzer' => 'ngram_search',
            ],
        ], $output);

        $cities = $this->repository->findAll();
        foreach ($cities as $city) {
            $this->client->index([
                'index' => $indexName,
                'id' => $city->getId(),
                'body' => [
                    'city' => $city->getCity(),
                ],
            ]);
        }

        $output->writeln("✅ Indexed " . count($cities) . " cities.");
    }

    private function recreateDistrictsIndex(OutputInterface $output): void
    {
        $indexName = 'districts';
        $this->createIndex($indexName, [
            'name' => [
                'type' => 'text',
                'analyzer' => 'ngram_analyzer',
                'search_analyzer' => 'ngram_search',
            ],
        ], $output);

        $districts = $this->entityManager->getRepository(Districts::class)->findAll();

        foreach ($districts as $district) {
            $this->client->index([
                'index' => $indexName,
                'id' => $district->getId(),
                'body' => [
                    'name' => $district->getName(),
                ],
            ]);
        }

        $output->writeln("✅ Indexed " . count($districts) . " districts.");
    }


    private function recreateClientsIndex(OutputInterface $output): void
    {
        $indexName = 'clients';
        $this->createIndex($indexName, [
            'name' => [
                'type' => 'text',
                'analyzer' => 'ngram_analyzer',
                'search_analyzer' => 'ngram_search',
            ],
            'surname' => [
                'type' => 'text',
                'analyzer' => 'ngram_analyzer',
                'search_analyzer' => 'ngram_search',
            ],
        ], $output);

        $clients = $this->entityManager->getRepository(Clients::class)->findAll();

        foreach ($clients as $client) {
            $this->client->index([
                'index' => $indexName,
                'id' => $client->getId(),
                'body' => [
                    'name' => $client->getName(),
                    'surname' => $client->getSurname(),
                ],
            ]);
        }

        $output->writeln("✅ Indexed " . count($clients) . " clients.");
    }

    private function recreateMastersIndex(OutputInterface $output): void
    {
        $indexName = 'masters';
        $this->createIndex($indexName, [
            'name' => [
                'type' => 'text',
                'analyzer' => 'ngram_analyzer',
                'search_analyzer' => 'ngram_search',
            ],
            'surname' => [
                'type' => 'text',
                'analyzer' => 'ngram_analyzer',
                'search_analyzer' => 'ngram_search',
            ],
        ], $output);

        $masters = $this->entityManager->getRepository(Masters::class)->findAll();

        foreach ($masters as $master) {
            $this->client->index([
                'index' => $indexName,
                'id' => $master->getId(),
                'body' => [
                    'name' => $master->getName(),
                    'surname' => $master->getSurname(),
                ],
            ]);
        }

        $output->writeln("✅ Indexed " . count($masters) . " masters.");
    }

    private function recreatePetsIndex(OutputInterface $output): void
    {
        $indexName = 'pets';
        $this->createIndex($indexName, [
            'breed' => [
                'type' => 'text',
                'analyzer' => 'ngram_analyzer',
                'search_analyzer' => 'ngram_search',
            ],
        ], $output);

        $pets = $this->entityManager->getRepository(Pets::class)->findAll();

        foreach ($pets as $pet) {
            $this->client->index([
                'index' => $indexName,
                'id' => $pet->getId(),
                'body' => [
                    'breed' => $pet->getBreed(),
                ],
            ]);
        }

        $output->writeln("✅ Indexed " . count($pets) . " pets.");
    }

    private function recreateServicesIndex(OutputInterface $output): void
    {
        $indexName = 'services';
        $this->createIndex($indexName, [
            'name' => [
                'type' => 'text',
                'analyzer' => 'ngram_analyzer',
                'search_analyzer' => 'ngram_search',
            ],
        ], $output);

        $services = $this->entityManager->getRepository(Services::class)->findAll();

        foreach ($services as $service) {
            $this->client->index([
                'index' => $indexName,
                'id' => $service->getId(),
                'body' => [
                    'name' => $service->getName(),
                ],
            ]);
        }

        $output->writeln("✅ Indexed " . count($services) . " services.");
    }

    private function createIndex(string $indexName, array $properties, OutputInterface $output): void
    {
        if ($this->client->indices()->exists(['index' => $indexName])->asBool()) {
            $this->client->indices()->delete(['index' => $indexName]);
            $output->writeln("ℹ️ Deleted old index: $indexName");
        }

        $this->client->indices()->create([
            'index' => $indexName,
            'body' => [
                'settings' => [
                    'index' => [
                        'max_ngram_diff' => 17,
                    ],
                    'analysis' => [
                        'analyzer' => [
                            'ngram_analyzer' => [
                                'tokenizer' => 'ngram_tokenizer',
                                'filter' => ['lowercase'],
                            ],
                            'ngram_search' => [
                                'tokenizer' => 'lowercase',
                            ],
                        ],
                        'tokenizer' => [
                            'ngram_tokenizer' => [
                                'type' => 'ngram',
                                'min_gram' => 3,
                                'max_gram' => 20,
                                'token_chars' => ['letter'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'properties' => $properties,
                ],
            ],
        ]);

        $output->writeln("✅ Created index: $indexName");
    }
}
