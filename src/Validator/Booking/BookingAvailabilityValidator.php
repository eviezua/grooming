<?php

namespace App\Validator\Booking;

use App\ApiResource\BookingsApi;
use App\Entity\Bookings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class BookingAvailabilityValidator extends ConstraintValidator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BookingAvailability) {
            throw new \LogicException('Invalid constraint type.');
        }

        if (!$value instanceof \DateTimeImmutable) {
            return;
        }

        $object = $this->context->getObject();

        if (!$object instanceof BookingsApi) {
            return;
        }

        if (!$object->masterId || !$object->timeStart || !$object->timeStop || !$object->date) {
            return;
        }

        $existingBooking = $this->entityManager->getRepository(Bookings::class)
            ->createQueryBuilder('b')
            ->where('b.id_master = :master')
            ->andWhere('b.date = :date')
            ->andWhere('b.time_start < :stopTime AND b.time_stop > :startTime')
            ->setParameter('master', $object->masterId)
            ->setParameter('date', $object->date)
            ->setParameter('startTime', $object->timeStart)
            ->setParameter('stopTime', $object->timeStop);

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
