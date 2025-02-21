<?php

namespace App\Service;

use App\Entity\Bookings;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Entity\Clients;
use App\Entity\Services;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BookingValidationService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function validateBooking(Bookings $booking, array $updatedFields = []): void
    {
        if (empty($updatedFields)) {
            return;
        }

        $this->validateMaster($booking, $updatedFields);
        $this->validatePet($booking, $updatedFields);
        $this->validateClient($booking, $updatedFields);
        $this->validateServices($booking, $updatedFields);
    }

    private function validateMaster(Bookings $booking, array $updatedFields): void
    {
        if (in_array('master', $updatedFields, true)) {
            $master = $booking->getIdMaster();
            if ($master !== null && (!$master instanceof Masters || !$this->entityManager->contains($master))) {
                throw new BadRequestHttpException('Master not found or not valid.');
            }
        }
    }

    private function validatePet(Bookings $booking, array $updatedFields): void
    {
        if (in_array('pet', $updatedFields, true)) {
            $pet = $booking->getPet();
            if ($pet !== null && (!$pet instanceof Pets || !$this->entityManager->contains($pet))) {
                throw new BadRequestHttpException('Pet not found or not valid.');
            }
        }
    }

    private function validateClient(Bookings $booking, array $updatedFields): void
    {
        if (in_array('client', $updatedFields, true)) {
            $client = $booking->getIdClient();
            if ($client !== null && (!$client instanceof Clients || !$this->entityManager->contains($client))) {
                throw new BadRequestHttpException('Client not found or not valid.');
            }
        }
    }

    private function validateServices(Bookings $booking, array $updatedFields): void
    {
        if (in_array('services', $updatedFields, true)) {
            foreach ($booking->getIdServices() as $service) {
                if (!$service instanceof Services || !$this->entityManager->contains($service)) {
                    throw new BadRequestHttpException('Service not found or not valid.');
                }
            }
        }
    }
}
