<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Bookings;
use App\Service\BookingValidationService;
use Doctrine\ORM\EntityManagerInterface;

class BookingStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingValidationService $validationService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Bookings) {
            return $data;
        }
        $this->validationService->validateBooking($data);

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
