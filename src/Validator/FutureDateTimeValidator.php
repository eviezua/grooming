<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class FutureDateTimeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FutureDateTime) {
            throw new \LogicException('Invalid constraint type.');
        }

        if (!$value instanceof \DateTimeInterface) {
            return;
        }

        $object = $this->context->getObject();

        if (!method_exists($object, 'getDate')) {
            return;
        }

        $date = $object->getDate();
        if (!$date instanceof \DateTimeInterface) {
            return;
        }

        $dateTime = new \DateTimeImmutable(
            $date->format('Y-m-d') . ' ' . $value->format('H:i:s')
        );

        $now = new \DateTimeImmutable();

        if ($dateTime < $now) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $dateTime->format('Y-m-d H:i'))
                ->addViolation();
        }
    }
}
