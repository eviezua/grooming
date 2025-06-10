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
        $to->spice = $from->getSpice()->value;
        $to->hair = $from->getHair()->value;
        $to->breed = $from->getBreed();
        $to->size = $from->getSize()->value;
        $to->mastersId = array_map(fn(Masters $m) => $m->getId(), $from->getMasters()->toArray());
        $to->status = $from->getStatus()->value;

        return $to;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof Pets);
        assert($to instanceof PetsApi);

        $to->id = $from->getId();
        $to->spice = $from->getSpice()->value;
        $to->hair = $from->getHair()->value;
        $to->breed = $from->getBreed();
        $to->size = $from->getSize()->value;
        $to->cost_coficient = $from->getCostCoficient();
        $to->mastersId = $from->getMastersId();
        $to->status = $from->getStatus()->value;

        return $to;
    }
}