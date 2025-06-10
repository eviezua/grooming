<?php

namespace App\Enum;

enum Weekdays: string
{
    case Monday = 'Monday';
    case Tuesday = 'Tuesday';
    case Wednesday = 'Wednesday';
    case Thursday = 'Thursday';
    case Friday = 'Friday';
    case Saturday = 'Saturday';
    case Sunday = 'Sunday';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
