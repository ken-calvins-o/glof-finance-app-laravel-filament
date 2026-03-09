<?php

namespace App\Support;

final class Money
{
    /**
     * Round a value to the nearest 0.05 (5 cents).
     *
     * Uses integer math where possible to avoid floating point artifacts.
     */
    public static function roundToNearest05(int|float|string|null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $amount = (float) $value;

        // Convert to cents, round to nearest cent first, then to nearest 5 cents.
        $cents = (int) round($amount * 100, 0, PHP_ROUND_HALF_UP);
        $nickels = (int) round($cents / 5, 0, PHP_ROUND_HALF_UP);

        return ($nickels * 5) / 100;
    }

    /**
     * Round to nearest 0.05 and format with 2 decimal places.
     */
    public static function format05(int|float|string|null $value): string
    {
        return number_format(self::roundToNearest05($value), 2, '.', ',');
    }
}

