<?php

namespace App\Mapper;

use App\ApiResource\DistrictsApi;
use App\Entity\Districts;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: Districts::class, to: DistrictsApi::class)]
class DistrictsEntityToApiMapper implements MapperInterface
{
    public function __construct()
    {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof Districts);

        $to = new DistrictsApi();
        $to->id = $from->getId();
        $to->name = $from->getName();
        $to->cityId = $from->getCity()->getId();
        $to->status = $from->getStatus()->value;

        return $to;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof Districts);
        assert($to instanceof DistrictsApi);

        $to->name = $from->getName();
        $to->cityId = $from->getCity()->getId();
        $to->status = $from->getStatus()->value;

        return $to;
    }
}