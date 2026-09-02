<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Decimal arithmetic used when ext-bcmath is missing. Matches bcmath’s
 * truncate-to-scale behaviour closely enough for stock quantities (3 dp)
 * and money (2 dp).
 */
final class BcMathPolyfill
{
    public static function add(string $num1, string $num2, ?int $scale = null): string
    {
        $scale = self::scale($scale);

        return self::decimal($num1)->plus(self::decimal($num2))->toScale($scale, RoundingMode::Down)->__toString();
    }

    public static function sub(string $num1, string $num2, ?int $scale = null): string
    {
        $scale = self::scale($scale);

        return self::decimal($num1)->minus(self::decimal($num2))->toScale($scale, RoundingMode::Down)->__toString();
    }

    public static function mul(string $num1, string $num2, ?int $scale = null): string
    {
        $scale = self::scale($scale);

        return self::decimal($num1)->multipliedBy(self::decimal($num2))->toScale($scale, RoundingMode::Down)->__toString();
    }

    public static function div(string $num1, string $num2, ?int $scale = null): string
    {
        $scale = self::scale($scale);

        return self::decimal($num1)->dividedBy(self::decimal($num2), $scale, RoundingMode::Down)->__toString();
    }

    public static function comp(string $num1, string $num2, ?int $scale = null): int
    {
        $scale = self::scale($scale);

        return self::decimal($num1)
            ->toScale($scale, RoundingMode::Down)
            ->compareTo(self::decimal($num2)->toScale($scale, RoundingMode::Down));
    }

    private static function scale(?int $scale): int
    {
        return $scale ?? 0;
    }

    private static function decimal(string $value): BigDecimal
    {
        $value = trim($value);

        if ($value === '' || $value === '-' || $value === '.') {
            $value = '0';
        }

        return BigDecimal::of($value);
    }
}
