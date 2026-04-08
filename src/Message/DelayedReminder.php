<?php

namespace App\Message;

final class DelayedReminder
{
    public function __construct(
        private int $bookingId,
        private string $recipientType
    ) {}

    public function getBookingId(): int { return $this->bookingId; }
    public function getRecipientType(): string { return $this->recipientType; }
}
