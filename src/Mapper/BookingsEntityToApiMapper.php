<?php

namespace App\Mapper;

use App\ApiResource\BookingsApi;
use App\Entity\Bookings;
use App\Entity\Services;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;
use Symfonycasts\MicroMapper\MicroMapperInterface;

#[AsMapper(from: Bookings::class, to: BookingsApi::class)]
class BookingsEntityToApiMapper implements MapperInterface
{
    public function __construct(private MicroMapperInterface $microMapper)
    {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        if (!$from instanceof Bookings) {
            throw new \InvalidArgumentException('Expected Bookings object');
        }

        return new BookingsApi();
    }

    public function populate(object $from, object $to, array $context): object
    {
        if (!$to instanceof BookingsApi) {
            throw new \InvalidArgumentException('Expected BookingsApi object');
        }
        if (!$from instanceof Bookings) {
            throw new \InvalidArgumentException('Expected Bookings object');
        }

        $to->id = $from->getId();
        $to->date = $from->getDate() ?: null;

        $to->timeStart = $this->convertToDateTime($from->getDate(), $from->getTimeStart());
        $to->timeStop = $this->convertToDateTime($from->getDate(), $from->getTimeStop());

        $to->services = $this->mapServices($from->getIdServices());
        $to->masterId = $from->getIdMaster()?->getId() ?? null;
        $to->clientId = $from->getIdClient()?->getId();
        $to->petId = $from->getPet()?->getId();

        return $to;
    }

    private function convertToDateTime(?\DateTime $date, ?\DateTime $time): ?\DateTime
    {
        if ($date && $time) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $time->format('H:i:s'));
        }
        return null;
    }

    private function mapServices($services): array
    {
        return array_map(fn(Services $service) => $service->getId(), $services->toArray());
    }
}