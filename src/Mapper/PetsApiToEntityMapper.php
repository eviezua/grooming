<?php

namespace App\Mapper;

use App\ApiResource\PetsApi;
use App\Entity\Masters;
use App\Entity\Pets;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: PetsApi::class, to: Pets::class)]
class PetsApiToEntityMapper implements MapperInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof PetsApi);

        $pet = new Pets();
        if ($from->id) {
            $pet = $this->entityManager->find(Pets::class, $from->id) ?? new Pets();
        }

        return $pet;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof PetsApi);
        assert($to instanceof Pets);

        $to->setSpice($from->spice);
        $to->setHair($from->hair);
        $to->setBreed($from->breed);
        $to->setSize($from->size);

        foreach ($to->getMasters() as $existingMaster) {
            $to->removeMaster($existingMaster);
        }

        $this->logger->info('Processing masters for pet ' . ($from->id ?? 'new'));

        if (!empty($from->mastersId)) {
            $mastersCollection = new ArrayCollection();
            foreach ($from->mastersId as $masterId) {
                if (!$masterId) {
                    continue;
                }

                $this->logger->info('Loading master with ID: ' . $masterId);

                $master = $this->entityManager->find(Masters::class, $masterId);

                if ($master) {
                    $mastersCollection->add($master);
                    $this->logger->info('Master found and added: ' . $master->getId());
                } else {
                    $this->logger->info('Master not found for ID: ' . $masterId);
                }
            }
            foreach ($mastersCollection as $master) {
                $to->addMaster($master);
            }
        }

        return $to;
    }
}