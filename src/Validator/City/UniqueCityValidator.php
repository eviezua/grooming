<?php

namespace App\Validator\City;

use App\Entity\Cities;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueCityValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueCity) {
            throw new UnexpectedTypeException($constraint, UniqueCity::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $normalizedValue = $this->normalizeString($value);

        $existingCity = $this->entityManager->getRepository(Cities::class)->findOneBy(['city' => $normalizedValue]);

        if ($existingCity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
    private function normalizeString(string $value): string
    {
        $value = trim($value);

        $value = mb_strtolower($value);

        $value = preg_replace('/[^\w\d]/u', '', $value);

        $value = ucfirst($value);

        return $value;
    }
}
