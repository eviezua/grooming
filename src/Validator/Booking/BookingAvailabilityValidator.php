<?php

namespace App\Validator\Booking;

use App\ApiResource\BookingsApi;
use App\Entity\Bookings;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class BookingAvailabilityValidator extends ConstraintValidator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BookingAvailability) {
            throw new LogicException('Invalid constraint type.');
        }

        if (!is_string($value)) {
            return;
        }

        $object = $this->context->getObject();

        if (!$object instanceof BookingsApi) {
            return;
        }

        if (!$object->masterId || !$object->timeStart || !$object->timeStop || !$object->date) {
            return;
        }

        try {
            $date = new DateTimeImmutable($object->date);
            $timeStart = new DateTimeImmutable($object->timeStart);
            $timeStop = new DateTimeImmutable($object->timeStop);
        } catch (Exception) {
            return;
        }

        $existingBooking = $this->entityManager->getRepository(Bookings::class)
            ->createQueryBuilder('b')
            ->where('b.id_master = :master')
            ->andWhere('b.date = :date')
            ->andWhere('b.time_start < :stopTime AND b.time_stop > :startTime')
            ->setParameter('master', $object->masterId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('startTime', $timeStart->format('H:i:s'))
            ->setParameter('stopTime', $timeStop->format('H:i:s'));

        if ($object->id !== null) {
            $existingBooking->andWhere('b.id != :currentBookingId')
                ->setParameter('currentBookingId', $object->id);
        }

        if ($existingBooking->getQuery()->getOneOrNullResult()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ field }}', $this->context->getPropertyPath())
                ->addViolation();
        }
    }
}
