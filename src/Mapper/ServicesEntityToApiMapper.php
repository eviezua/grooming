<?php

namespace App\Mapper;

use App\ApiResource\ServicesApi;
use App\Entity\Services;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: Services::class, to: ServicesApi::class)]
class ServicesEntityToApiMapper implements MapperInterface
{
    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof Services);

        $to = new ServicesApi();
        $to->id = $from->getId();
        $to->name = $from->getName();
        $to->cost = $from->getCost();
        $to->default_time = $from->getDefaultTime()?->format('H:i:s');
        $to->mastersId = $from->getMastersId();
        $to->status = $from->getStatus()->value;

        return $to;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof Services);
        assert($to instanceof ServicesApi);

        $to->id = $from->getId();
        $to->name = $from->getName();
        $to->cost = $from->getCost();
        $to->default_time = $from->getDefaultTime()?->format('H:i:s');
        $to->mastersId = $from->getMastersId();
        $to->status = $from->getStatus()->value;

        return $to;
    }
}