<?php

namespace App\Domain\Catalog;

/**
 * Whole Vietnamese dong. Prices are stored as DOUBLE in the PHP-era tables; this is the one place that
 * turns them into an amount, so every page agrees on what "has a price" means.
 */
final class Money
{
    private function __construct(public readonly int $dong) {}

    /** A positive, finite amount rounded to the dong; anything else is "no price" (ptsPdpMoney()). */
    public static function fromLegacy(mixed $value): ?self
    {
        if (! is_scalar($value) || ! is_numeric($value)) {
            return null;
        }
        $number = (float) $value;
        if (! is_finite($number) || $number <= 0 || $number > PHP_INT_MAX) {
            return null;
        }

        return new self((int) round($number));
    }
}
