<?php

namespace App\Mapper;

use App\ApiResource\CitiesApi;
use App\ApiResource\MastersApi;
use App\Entity\Cities;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;
use Symfonycasts\MicroMapper\MicroMapperInterface;

#[AsMapper(from: Cities::class, to: CitiesApi::class)]
class CitiesEntityToApiMapper implements MapperInterface
{
    public function __construct(private MicroMapperInterface $microMapper)
    {
    }
    public function load(object $from, string $toClass, array $context): object
    {
        $entity = $from;

        assert($entity instanceof Cities);

        $dto = new CitiesApi();
        $dto->id = $entity->getId();
        $dto->city = $entity->getCity();

        return $dto;
    }

    public function populate(object $from, object $to, array $context): object
    {
        $entity = $from;
        $dto = $to;

        assert($entity instanceof Cities);
        assert($dto instanceof CitiesApi);

        $dto->city = $entity->getCity();

        $masters = $entity->getMasters();

        if ($masters !== null) {
            $dto->masters = [];
            foreach ($masters as $master) {
                $dto->masters[] = $this->microMapper->map($master, MastersApi::class, [
                    MicroMapperInterface::MAX_DEPTH => 0,
                ]);
            }
        }

        return $dto;
    }
}