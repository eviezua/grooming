<?php

namespace App\Mapper;

use App\ApiResource\CitiesApi;
use App\Entity\Cities;
use InvalidArgumentException;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: CitiesApi::class, to: Cities::class)]
class CitiesApiToEntityMapper implements MapperInterface
{
    public function __construct()
    {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof CitiesApi);

        return new Cities();
    }

    public function populate(object $from, object $to, array $context): object
    {
        if (!$from instanceof CitiesApi) {
            throw new InvalidArgumentException('Expected CitiesApi object');
        }
        if (!$to instanceof Cities) {
            throw new InvalidArgumentException('Expected Cities object');
        }

        $to->setCity($from->city);

        return $to;
    }
}