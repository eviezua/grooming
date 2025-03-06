<?php

namespace App\Mapper;

use App\ApiResource\ClientsApi;
use App\Entity\Clients;
use App\Entity\Pets;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: ClientsApi::class, to: Clients::class)]
class ClientsApiToEntityMapper implements MapperInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof ClientsApi);

        $client = new Clients();
        if ($from->id) {
            $client = $this->entityManager->find(Clients::class, $from->id) ?? new Clients();
        }

        return $client;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof ClientsApi);
        assert($to instanceof Clients);

        $to->setName($from->name);
        $to->setSurname($from->surname);
        $to->setEmail($from->email);
        $to->setPhone($from->phone);

        foreach ($to->getPets() as $existingPet) {
            $to->removePet($existingPet);
        }

        $this->logger->info('Processing pets for client ' . ($from->id ?? 'new'));

        if (!empty($from->pets)) {
            $petsCollection = new ArrayCollection();
            foreach ($from->pets as $petId) {
                if (!$petId) {
                    continue;
                }

                $this->logger->info('Loading pet with ID: ' . $petId);

                $pet = $this->entityManager->find(Pets::class, $petId);

                if ($pet) {
                    $petsCollection->add($pet);
                    $this->logger->info('Pet found and added: ' . $pet->getId());
                } else {
                    $this->logger->info('Pet not found for ID: ' . $petId);
                }
            }
            foreach ($petsCollection as $pet) {
                $to->addPet($pet);
            }
        }

        return $to;
    }
}