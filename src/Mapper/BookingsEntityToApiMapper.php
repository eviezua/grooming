<?php

namespace App\Mapper;

use App\ApiResource\BookingsApi;
use App\Entity\Bookings;
use App\Service\TimeFormatter;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: Bookings::class, to: BookingsApi::class)]
class BookingsEntityToApiMapper implements MapperInterface
{
    public function __construct(
        private TimeFormatter $timeFormatter
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof Bookings);

        $to = new BookingsApi();
        $to->id = $from->getId();
        $to->masterId = $from->getMasterId();
        $to->clientId = $from->getClientId();
        $to->petId = $from->getPetId();
        $to->services = $from->getServices();

        $to->date = $from->getDate()?->format('Y-m-d');
        $to->timeStart = $this->timeFormatter->formatTime($from->getTimeStart());
        $to->timeStop = $this->timeFormatter->formatTime($from->getTimeStop());

        $to->status = $from->getStatus()->value;

        $to->totalPrice = $from->getTotalPrice();

        return $to;
    }

    public function populate(object $from, object $to, array $context = []): object
    {
        assert($from instanceof Bookings);
        assert($to instanceof BookingsApi);

        $to->id = $from->getId();
        $to->masterId = $from->getMasterId();
        $to->clientId = $from->getClientId();
        $to->petId = $from->getPetId();
        $to->services = $from->getServices();

        $to->date = $from->getDate()?->format('Y-m-d');
        $to->timeStart = $this->timeFormatter->formatTime($from->getTimeStart());
        $to->timeStop = $this->timeFormatter->formatTime($from->getTimeStop());

        $to->status = $from->getStatus()->value;

        $to->totalPrice = $from->getTotalPrice();

        return $to;
    }
}