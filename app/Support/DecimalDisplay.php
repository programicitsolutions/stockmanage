<?php

namespace App\Support;

class DecimalDisplay
{
    public static function quantity(string|int|float|null $value): string
    {
        $normalized = bcadd((string) ($value ?? '0'), '0', 3);
        $trimmed = rtrim(rtrim($normalized, '0'), '.');

        return $trimmed === '' ? '0' : $trimmed;
    }

    public static function money(string|int|float|null $value): string
    {
        return number_format((float) bcadd((string) ($value ?? '0'), '0', 2), 2, '.', ',');
    }
}
