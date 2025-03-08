<?php

namespace App\Mapper;

use App\ApiResource\ServicesApi;
use App\Entity\Masters;
use App\Entity\Services;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: Services::class, to: ServicesApi::class)]
class ServicesEntityToApiMapper implements MapperInterface
{
    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof Services);

        $dto = new ServicesApi();
        $dto->id = $from->getId();
        $dto->name = $from->getName();
        $dto->cost = $from->getCost();
        $dto->default_time = $from->getDefaultTime()?->format('H:i:s');

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
        assert($from instanceof Services);
        assert($to instanceof ServicesApi);

        $to->id = $from->getId();
        $to->name = $from->getName();
        $to->cost = $from->getCost();
        $to->default_time = $from->getDefaultTime()?->format('H:i:s');

        $masters = $from->getMasters();
        if (!$masters->isEmpty()) {
            $to->mastersId = array_map(fn(Masters $master) => $master->getId(), $masters->toArray());
        } else {
            $to->mastersId = [];
        }

        return $to;
    }
}