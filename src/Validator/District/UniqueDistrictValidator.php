<?php

namespace App\Validator\District;

use App\Entity\Districts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueDistrictValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueDistrict) {
            throw new UnexpectedTypeException($constraint, UniqueDistrict::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $object = $this->context->getObject();
        $cityId = $object->cityId ?? null;

        if (!$cityId) {
            return;
        }

        $districtName = $this->normalizeString($value);

        $existing = $this->entityManager->getRepository(Districts::class)
            ->createQueryBuilder('d')
            ->where('LOWER(d.name) = :name')
            ->andWhere('d.city = :city')
            ->setParameter('name', $districtName)
            ->setParameter('city', $cityId)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }

    private function normalizeString(string $value): string
    {
        $value = trim($value);
        $value = mb_strtolower($value);
        $value = preg_replace('/[^\p{L}\d]+/u', '', $value);
        return $value;
    }
}
