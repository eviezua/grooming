<?php

namespace App\Mapper;

use App\ApiResource\BookingsApi;
use App\Entity\Bookings;
use App\Entity\Clients;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Entity\Services;
use Doctrine\ORM\EntityManagerInterface;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: BookingsApi::class, to: Bookings::class)]
class BookingsApiToEntityMapper implements MapperInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof BookingsApi);

        return new Bookings();
    }

    public function populate(object $from, object $to, array $context): object
    {
        if (!$from instanceof BookingsApi) {
            throw new \InvalidArgumentException('Expected BookingsApi object');
        }
        if (!$to instanceof Bookings) {
            throw new \InvalidArgumentException('Expected Bookings object');
        }

        if ($from->date && $from->timeStart) {
            $date = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $from->date);
            $timeValue = \DateTime::createFromFormat('H:i:s', $from->timeStart);
            if ($date && $timeValue) {
                $to->setTimeStart($date->setTime($timeValue->format('H'), $timeValue->format('i'), $timeValue->format('s')));
            }
        }

        if ($from->date && $from->timeStop) {
            $date = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $from->date);
            $timeValue = \DateTime::createFromFormat('H:i:s', $from->timeStop);
            if ($date && $timeValue) {
                $to->setTimeStop($date->setTime($timeValue->format('H'), $timeValue->format('i'), $timeValue->format('s')));
            }
        }
        $to->setDate($from->date ? \DateTime::createFromFormat('Y-m-d\TH:i:sP', $from->date) : null);

        $to->setIdMaster($this->entityManager->getRepository(Masters::class)->find($from->masterId));
        $to->setIdClient($this->entityManager->getRepository(Clients::class)->find($from->clientId));
        $to->setPet($this->entityManager->getRepository(Pets::class)->find($from->petId));

        $services = $this->entityManager->getRepository(Services::class)->findBy(['id' => $from->services]);
        foreach ($services as $service) {
            $to->addIdService($service);
        }

        return $to;
    }
}