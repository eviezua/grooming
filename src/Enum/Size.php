<?php

namespace App\Enum;

enum Size: string
{
    case Big = 'Big';
    case Medium = 'Medium';
    case Small = 'Small';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
