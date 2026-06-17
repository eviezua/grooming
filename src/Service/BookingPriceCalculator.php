<?php

namespace App\Service;

use App\Entity\Bookings;

class BookingPriceCalculator
{
    public function calculate(Bookings $booking): float
    {
        $master = $booking->getIdMaster();
        $services = $booking->getIdServices();
        $pet = $booking->getPet();

        if (!$master || $services->isEmpty()) {
            return 0.0;
        }

        $masterPrices = [];

        foreach ($master->getMastersServices() as $masterService) {
            $masterPrices[$masterService->getService()->getId()] = (float)$masterService->getPrice();
        }

        $totalSum = 0.0;

        foreach ($services as $service) {
            $serviceId = $service->getId();

            $actualCost = $masterPrices[$serviceId] ?? (float)($service->getCost() ?? 0.0);
            $totalSum += $actualCost;
        }

        $coefficient = 1.0;

        if ($pet) {
            $coefficient = (float)($pet->getCostCoficient() ?? 1.0);
        }

        return $totalSum * $coefficient;
    }
}