<?php

namespace App\Mapper;

use App\ApiResource\CitiesApi;
use App\Entity\Cities;
use App\Entity\Masters;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: Cities::class, to: CitiesApi::class)]
class CitiesEntityToApiMapper implements MapperInterface
{
    public function __construct()
    {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof Cities);

        $to = new CitiesApi();
        $to->id = $from->getId();
        $to->city = $from->getCity();
        $to->masters = array_map(fn(Masters $m) => $m->getId(), $from->getMasters()->toArray());
        $to->status = $from->getStatus()->value;

        return $to;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof Cities);
        assert($to instanceof CitiesApi);

        $to->city = $from->getCity();
        $to->masters = array_map(fn(Masters $m) => $m->getId(), $from->getMasters()->toArray());
        $to->status = $from->getStatus()->value;

        return $to;
    }
}