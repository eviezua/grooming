<?php

namespace App\Validator\Booking;

use App\Entity\Bookings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class BookingAvailabilityValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BookingAvailability) {
            throw new \LogicException('Invalid constraint type.');
        }

        if (!$value instanceof \DateTimeInterface) {
            return;
        }

        $object = $this->context->getObject();
        if (!method_exists($object, 'getIdMaster') ||
            !method_exists($object, 'getTimeStart') ||
            !method_exists($object, 'getTimeStop') ||
            !method_exists($object, 'getDate')) {
            return;
        }

        $master = $object->getIdMaster();
        $startTime = $object->getTimeStart();
        $stopTime = $object->getTimeStop();
        $date = $object->getDate();

        if (!$master || !$startTime || !$stopTime || !$date) {
            return;
        }

        $existingBooking = $this->entityManager->getRepository(Bookings::class)
            ->createQueryBuilder('b')
            ->where('b.id_master = :master')
            ->andWhere('b.date = :date')
            ->andWhere('b.time_start < :stopTime AND b.time_stop > :startTime')
            ->setParameter('master', $master)
            ->setParameter('date', $date)
            ->setParameter('startTime', $startTime)
            ->setParameter('stopTime', $stopTime);

        if ($object->getId() !== null) {
            $existingBooking->andWhere('b.id != :currentBookingId')
                ->setParameter('currentBookingId', $object->getId());
        }

        if ($existingBooking->getQuery()->getOneOrNullResult()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ field }}', $this->context->getPropertyPath())
                ->addViolation();
        }
    }
}
