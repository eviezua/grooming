<?php
namespace App\EventListener;
use App\Entity\Bookings;
use App\Service\BookingPriceCalculator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
class BookingPriceCalculatorListener
{
    public function __construct(
        private readonly BookingPriceCalculator $priceCalculator
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $booking = $args->getObject();

        if (!$booking instanceof Bookings) {
            return;
        }

        $finalPrice = $this->priceCalculator->calculate($booking);

        $booking->setTotalPrice($finalPrice);
    }
}