<?php

namespace App\Mapper;

use App\ApiResource\PetsApi;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Service\EntityLoaderHelper;
use Psr\Log\LoggerInterface;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;
use Throwable;

#[AsMapper(from: PetsApi::class, to: Pets::class)]
class PetsApiToEntityMapper implements MapperInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityLoaderHelper $loader,
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof PetsApi);

        return $context['target_object'] ??
            ($from->id ? $this->loader->load(Pets::class, $from->id, 'Pets') : new Pets());
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof PetsApi);
        assert($to instanceof Pets);

        $to->setSpice($from->spice);
        $to->setHair($from->hair);
        $to->setBreed($from->breed);
        $to->setSize($from->size);

        $to->clearMasters();

        $this->logger->info('Processing masters for pet ' . ($from->id ?? 'new'));

        if (!empty($from->mastersId)) {
            $validIds = array_filter($from->mastersId);
            try {
                $masters = $this->loader->loadMultiple(Masters::class, $validIds, 'Masters');
                foreach ($masters as $master) {
                    $to->addMaster($master);
                    $this->logger->info('Master added: ' . $master->getId());
                }
            } catch (Throwable $e) {
                $this->logger->warning('Some masters could not be loaded: ' . $e->getMessage());
            }
        }

        return $to;
    }
}