<?php

namespace App\Mapper;

use App\ApiResource\PetsApi;
use App\Entity\Masters;
use App\Entity\Pets;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: Pets::class, to: PetsApi::class)]
class PetsEntityToApiMapper implements MapperInterface
{
    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof Pets);

        $dto = new PetsApi();
        $dto->id = $from->getId();
        $dto->spice = $from->getSpice();
        $dto->hair = $from->getHair();
        $dto->breed = $from->getBreed();
        $dto->size = $from->getSize();
        $masters = $from->getMasters();
        if (!$masters->isEmpty()) {
            $dto->mastersId = array_map(fn(Masters $master) => $master->getId(), $masters->toArray());
        } else {
            $dto->mastersId = [];
        }

        return $dto;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof Pets);
        assert($to instanceof PetsApi);

        $to->id = $from->getId();
        $to->spice = $from->getSpice();
        $to->hair = $from->getHair();
        $to->breed = $from->getBreed();
        $to->size = $from->getSize();
        $masters = $from->getMasters();
        if (!$masters->isEmpty()) {
            $to->mastersId = array_map(fn(Masters $master) => $master->getId(), $masters->toArray());
        } else {
            $to->mastersId = [];
        }

        return $to;
    }
}