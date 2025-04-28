<?php

namespace App\Service;

use DateTime;
use DateTimeInterface;

class TimeFormatter
{
    public function formatTime(?DateTimeInterface $time): ?string
    {
        return $time ? $time->format('H:i:s') : null;
    }

    public function parseTime(?string $time): ?DateTimeInterface
    {
        return $time ? new DateTime($time) : null;
    }
}
