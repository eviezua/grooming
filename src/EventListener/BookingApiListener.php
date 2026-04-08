<?php

namespace App\EventListener;

use App\Entity\Bookings;
use App\Message\BookingNotification;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

final class BookingApiListener
{
    public function __construct(private readonly MessageBusInterface $bus) {}

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->getResponse()->isSuccessful() || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $bookingId = $this->extractBookingId($request);

        if ($bookingId) {
            $this->bus->dispatch(new BookingNotification(
                $bookingId,
                $request->getMethod()
            ),
                [ new AmqpStamp('default')]
            );
        }
    }

    private function extractBookingId(Request $request): ?int
    {
        $entities = array_filter(
            $request->attributes->all(),
            fn($key) => str_ends_with((string)$key, '_entity'),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($entities)) {
            return null;
        }

        $data = current($entities);

        if ($data instanceof Bookings) {
            return $data->getId();
        }

        return is_numeric($data) ? (int) $data : null;
    }
}
