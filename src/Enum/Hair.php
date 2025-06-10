<?php

namespace App\Enum;

enum Hair: string
{
    case Long = 'Long';
    case Middle = 'Middle';
    case Short = 'Short';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
