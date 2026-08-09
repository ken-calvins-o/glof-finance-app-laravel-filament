<?php

namespace App\Support;

final class Money
{
    public const CURRENCY = 'KES';

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

    /**
     * The single way money is written in this app: "KES 12,500.00".
     *
     * Every table column, stat, summary and PDF goes through here so that an
     * amount never reads differently from one screen to the next.
     */
    public static function kes(int|float|string|null $value, bool $withDecimals = true): string
    {
        return self::CURRENCY . ' ' . number_format((float) ($value ?? 0), $withDecimals ? 2 : 0, '.', ',');
    }

    /**
     * A shortened form for stats and chart axes, where the exact cents are
     * noise: "KES 1.2M", "KES 45.0K", "KES 900".
     */
    public static function compact(int|float|string|null $value): string
    {
        $amount = (float) ($value ?? 0);
        $sign = $amount < 0 ? '-' : '';
        $abs = abs($amount);

        return match (true) {
            $abs >= 1_000_000 => $sign . self::CURRENCY . ' ' . number_format($abs / 1_000_000, 1) . 'M',
            $abs >= 1_000 => $sign . self::CURRENCY . ' ' . number_format($abs / 1_000, 1) . 'K',
            default => $sign . self::CURRENCY . ' ' . number_format($abs, 0),
        };
    }
}
