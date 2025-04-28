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

        $to = new PetsApi();
        $to->id = $from->getId();
        $to->spice = $from->getSpice();
        $to->hair = $from->getHair();
        $to->breed = $from->getBreed();
        $to->size = $from->getSize();
        $to->mastersId = array_map(fn(Masters $m) => $m->getId(), $from->getMasters()->toArray());

        return $to;
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
        $to->cost_coficient = $from->getCostCoficient();
        $to->mastersId = $from->getMastersId();

        return $to;
    }
}