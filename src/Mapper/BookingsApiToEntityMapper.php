<?php

namespace App\Mapper;

use App\ApiResource\BookingsApi;
use App\Entity\Bookings;
use App\Entity\Clients;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Entity\Services;
use App\Service\EntityLoaderHelper;
use App\Service\TimeFormatter;
use DateTime;
use InvalidArgumentException;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: BookingsApi::class, to: Bookings::class)]
class BookingsApiToEntityMapper implements MapperInterface
{
    public function __construct(
        private TimeFormatter $timeFormatter,
        private EntityLoaderHelper $loader,
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof BookingsApi);

        return $context['target_object'] ??
            ($from->id ? $this->loader->load(Bookings::class, $from->id, 'Bookings') : new Bookings());
    }

    public function populate(object $from, object $to, array $context): object
    {
        if (!$from instanceof BookingsApi) {
            throw new InvalidArgumentException('Expected BookingsApi object');
        }
        if (!$to instanceof Bookings) {
            throw new InvalidArgumentException('Expected Bookings object');
        }

        $to->setDate(new DateTime($from->date));
        $to->setTimeStart($this->timeFormatter->parseTime($from->timeStart));
        $to->setTimeStop($this->timeFormatter->parseTime($from->timeStop));

        $to->setIdMaster($this->loader->load(Masters::class, $from->masterId, 'Masters'));
        $to->setIdClient($this->loader->load(Clients::class, $from->clientId, 'Clients'));
        $to->setPet($this->loader->load(Pets::class, $from->petId, 'Pets'));

        if (!empty($from->services)) {
            $to->getIdServices()->clear();
            foreach ($this->loader->loadMultiple(Services::class, $from->services, 'Services') as $service) {
                $to->addIdService($service);
            }
        }

        return $to;
    }
}