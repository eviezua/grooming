<?php

namespace App\Mapper;

use App\ApiResource\ClientsApi;
use App\Entity\Bookings;
use App\Entity\Clients;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: Clients::class, to: ClientsApi::class)]
class ClientsEntityToApiMapper implements MapperInterface
{
    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof Clients);

        $to = new ClientsApi();
        $to->id = $from->getId();
        $to->name = $from->getName();
        $to->surname = $from->getSurname();
        $to->email = $from->getEmail();
        $to->phone = $from->getPhone();
        $petIds = [];
        foreach ($from->getPets() as $pet) {
            $petIds[] = $pet->getId();
        }
        $to->pets = $petIds;

        return $to;
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

        $petIds = [];
        foreach ($from->getPets() as $pet) {
            $petIds[] = $pet->getId();
        }
        $to->pets = $petIds;

        $bookings = $from->getBookings();
        if (!$bookings->isEmpty()) {
            $to->bookings = array_map(fn(Bookings $booking) => $booking->getId(), $bookings->toArray());
        } else {
            $to->bookings = [];
        }

        return $to;
    }
}