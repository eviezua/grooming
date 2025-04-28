<?php

namespace App\Mapper;

use App\ApiResource\ClientsApi;
use App\Entity\Clients;
use App\Entity\Pets;
use App\Service\EntityLoaderHelper;
use Psr\Log\LoggerInterface;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;
use Throwable;

#[AsMapper(from: ClientsApi::class, to: Clients::class)]
class ClientsApiToEntityMapper implements MapperInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityLoaderHelper $loader,
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof ClientsApi);

        return $context['target_object'] ??
            ($from->id ? $this->loader->load(Clients::class, $from->id, 'Clients') : new Clients());
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof ClientsApi);
        assert($to instanceof Clients);

        $to->setName($from->name);
        $to->setSurname($from->surname);
        $to->setEmail($from->email);
        $to->setPhone($from->phone);

        $to->clearPets();

        if (!empty($from->pets)) {
            $validIds = array_filter($from->pets);
            try {
                $pets = $this->loader->loadMultiple(Pets::class, $validIds, 'Pets');
                foreach ($pets as $pet) {
                    $to->addPet($pet);
                }
            } catch (Throwable $e) {
                $this->logger->warning('Some pets could not be loaded: ' . $e->getMessage());
            }
        }

        return $to;
    }
}