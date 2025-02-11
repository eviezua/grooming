<?php

namespace App\Service;

use App\Entity\Bookings;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Entity\Clients;
use App\Entity\Services;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BookingValidationService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function validateBooking(Bookings $booking): void
    {
        $master = $booking->getIdMaster();
        if (!$master instanceof Masters || !$this->entityManager->contains($master)) {
            throw new BadRequestHttpException('Master not found.');
        }

        $pet = $booking->getPet();
        if (!$pet instanceof Pets || !$this->entityManager->contains($pet)) {
            throw new BadRequestHttpException('Pet not found.');
        }

        $client = $booking->getIdClient();
        if (!$client instanceof Clients || !$this->entityManager->contains($client)) {
            throw new BadRequestHttpException('Client not found.');
        }

        $services = new ArrayCollection();
        foreach ($booking->getIdServices() as $service) {
            if (!$service instanceof Services || !$this->entityManager->contains($service)) {
                throw new BadRequestHttpException('Service not found.');
            }
            $services->add($service);
        }

        $booking->setIdServices($services);
    }
}
