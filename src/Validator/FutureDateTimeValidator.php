<?php

namespace App\Validator;

use App\Service\DateFieldsSynchronizer;
use App\Service\TimeFormatter;
use DateTimeImmutable;
use LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class FutureDateTimeValidator extends ConstraintValidator
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private DateFieldsSynchronizer $dateFieldsSynchronizer,
        private TimeFormatter $timeFormatter,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FutureDateTime) {
            throw new LogicException('Invalid constraint type.');
        }
        $date = $this->context->getObject()->date;

        $formattedValue = $this->timeFormatter->parseTime($value);
        $normalizedValue = $this->normalizer->normalize($formattedValue);
        $synchronizedValue = $this->dateFieldsSynchronizer->synchronize($normalizedValue, $date);
        $dateTime = new DateTimeImmutable(
            $synchronizedValue['date'] . ' ' . $synchronizedValue['time']
        );
        $now = new DateTimeImmutable();

        if ($synchronizedValue['time'] === '00:00:00') {
            if ($dateTime->format('Y-m-d') < $now->format('Y-m-d')) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $dateTime->format('Y-m-d'))
                    ->addViolation();
            }
        } else {
            if ($dateTime <= $now) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $dateTime->format('Y-m-d H:i:s'))
                    ->addViolation();
            }
        }
    }
}
