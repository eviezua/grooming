<?php

namespace App\Mapper;

use App\ApiResource\MastersServicesApi;
use App\Entity\MastersServices;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: MastersServices::class, to: MastersServicesApi::class)]
class MastersServicesEntityToApiMapper implements MapperInterface
{
    public function __construct()
    {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof MastersServices);

        $to = new MastersServicesApi();
        $to->id = $from->getId();
        $to->masterId = $from->getMaster()->getId();
        $to->serviceId = $from->getService()->getId();
        $to->price = $from->getPrice();

        return $to;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof MastersServices);
        assert($to instanceof MastersServicesApi);

        $to->masterId = $from->getMaster()->getId();
        $to->serviceId = $from->getService()->getId();
        $to->price = $from->getPrice();

        return $to;
    }
}