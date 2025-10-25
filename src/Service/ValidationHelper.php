<?php

namespace App\Service;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationHelper
{
    public function __construct(private ValidatorInterface $validator) {}

    public function validate(object $dto, array $groups = []): ConstraintViolationListInterface
    {
        return $this->validator->validate($dto, null, $groups);
    }
}
