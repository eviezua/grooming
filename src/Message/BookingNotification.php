<?php

namespace App\Message;

final class BookingNotification
{
    public function __construct(
        public int $bookingId,
        public string $method,
        public ?array $oldData = null
    ) {}
}
