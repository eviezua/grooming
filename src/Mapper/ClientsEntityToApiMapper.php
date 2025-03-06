<?php

namespace App\Mapper;

use App\ApiResource\ClientsApi;
use App\Entity\Clients;
use App\Entity\Pets;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: Clients::class, to: ClientsApi::class)]
class ClientsEntityToApiMapper implements MapperInterface
{
    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof Clients);

        $dto = new ClientsApi();
        $dto->id = $from->getId();
        $dto->name = $from->getName();
        $dto->surname = $from->getSurname();
        $dto->email = $from->getEmail();
        $dto->phone = $from->getPhone();
        $pets = $from->getPets();
        if (!$pets->isEmpty()) {
            $dto->pets = array_map(fn(Pets $pet) => $pet->getId(), $pets->toArray());
        } else {
            $dto->pets = [];
        }

        return $dto;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof Clients);
        assert($to instanceof ClientsApi);

        $to->id = $from->getId();
        $to->name = $from->getName();
        $to->surname = $from->getSurname();
        $to->email = $from->getEmail();
        $to->phone = $from->getPhone();

        $pets = $from->getPets();
        if (!$pets->isEmpty()) {
            $to->pets = array_map(fn(Pets $pet) => $pet->getId(), $pets->toArray());
        } else {
            $to->pets = [];
        }

        return $to;
    }
}