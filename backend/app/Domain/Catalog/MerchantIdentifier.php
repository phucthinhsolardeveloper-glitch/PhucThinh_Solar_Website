<?php

namespace App\Domain\Catalog;

/** Validates manufacturer identifiers before they enter a Merchant Center data source. */
final class MerchantIdentifier
{
    public static function gtin(string $value): string
    {
        $value = trim($value);
        if (str_starts_with($value, "'")) {
            $value = substr($value, 1);
        }
        $value = (string) preg_replace('/[\s-]+/u', '', $value);

        return self::isValidGtin($value) ? $value : '';
    }

    public static function mpn(string $value): string
    {
        $value = trim($value);

        return $value !== '' && mb_strlen($value, 'UTF-8') <= 70 && preg_match('/[\p{C}]/u', $value) !== 1 ? $value : '';
    }

    public static function exists(string $gtin, string $mpn, bool $hasBrand, bool $confirmedAbsent, bool $hasUnverifiedInput): string
    {
        if ($gtin !== '' || ($mpn !== '' && $hasBrand)) {
            return 'yes';
        }
        if ($confirmedAbsent && ! $hasUnverifiedInput) {
            return 'no';
        }

        return '';
    }

    private static function isValidGtin(string $value): bool
    {
        $length = strlen($value);
        if (! preg_match('/^\d+$/D', $value) || ! in_array($length, [8, 12, 13, 14], true) || preg_match('/^0+$/D', $value)) {
            return false;
        }

        // GTIN-8 has its own prefix table: 0xx and 2xx are restricted circulation (RCN-8), 977-999 are reserved.
        if ($length === 8) {
            if (preg_match('/^(?:0|2|97|98|99)/', $value)) {
                return false;
            }
        } else {
            // Check the GS1 prefix in GTIN-13 form, not UPC-A's leading zero (042100005264 has GS1 prefix 004;
            // UPC-A number systems 2, 4 and 5 become 02, 04 and 05).
            $prefixSource = match ($length) {
                12 => '0'.$value,
                14 => substr($value, 1),
                default => $value,
            };
            if (preg_match('/^(?:02|04|2)/', $prefixSource) || preg_match('/^(?:05|98|99)/', $prefixSource)) {
                return false;
            }
        }

        // Keep GTINs as strings: UPC-A values can start with zero.
        $sum = 0;
        for ($i = $length - 2, $weight = 3; $i >= 0; $i--, $weight = $weight === 3 ? 1 : 3) {
            $sum += ((int) $value[$i]) * $weight;
        }

        return (10 - ($sum % 10)) % 10 === (int) $value[$length - 1];
    }
}
