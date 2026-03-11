<?php

namespace App\Mapper;

use App\ApiResource\MastersApi;
use App\Entity\Cities;
use App\Entity\Districts;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Service\EntityLoaderHelper;
use Psr\Log\LoggerInterface;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;
use Throwable;

#[AsMapper(from: MastersApi::class, to: Masters::class)]
class MastersApiToEntityMapper implements MapperInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityLoaderHelper $loader,
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof MastersApi);

        return $context['target_object'] ??
            ($from->id ? $this->loader->load(Masters::class, $from->id, 'Masters') : new Masters());
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof MastersApi);
        assert($to instanceof Masters);

        $to->setName($from->name);
        $to->setSurname($from->surname);

        $to->setIdCity($this->loader->load(Cities::class, $from->cityId, 'Cities'));
        $district = $from->districtId ? $this->loader->load(Districts::class, $from->districtId, 'Districts') : null;
        $to->setDistrict($district);
        $to->setAddress($from->address);
        $to->clearPets();

        if (!empty($from->petsId)) {
            $validIds = array_filter($from->petsId);
            try {
                $pets = $this->loader->loadMultiple(Pets::class, $validIds, 'Pets');
                foreach ($pets as $pet) {
                    $to->addIdPet($pet);
                }
            } catch (Throwable $e) {
                $this->logger->warning('Some pets could not be loaded: ' . $e->getMessage());
            }
        }

        if ($from->password !== null) {
            $to->setPassword($from->password);
        }

        $to->setEmail($from->email);
        $to->setPhone($from->phone);
        $to->setPhoto($from->photo);

        return $to;
    }
}