<?php

namespace App\Enum;

enum Species: string
{
    case Dog = 'Dog';
    case Cat = 'Cat';
    case Horse = 'Horse';
    case Rabbit = 'Rabbit';
    case Bird = 'Bird';
    case Rodent = 'Rodent';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
