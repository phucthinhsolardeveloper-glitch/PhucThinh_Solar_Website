<?php

namespace App\Support;

/**
 * Stateless anti-forgery token for public forms (replaces the PHP session token formCsrfToken()).
 * Nuxt renders pages server-side and shares no PHP session, so the token carries its issue time and
 * an HMAC with the app key. Format stays 64 lowercase hex characters, the shape the Nuxt contracts
 * check: 8 hex digits of issue time followed by 56 hex digits of signature.
 */
final class FormToken
{
    public const LIFETIME_SECONDS = 7200;

    public static function issue(?int $now = null): string
    {
        $issued = str_pad(dechex($now ?? time()), 8, '0', STR_PAD_LEFT);

        return $issued.self::sign($issued);
    }

    public static function valid(mixed $token, ?int $now = null): bool
    {
        if (! is_string($token) || ! preg_match('/^([a-f0-9]{8})([a-f0-9]{56})$/', $token, $match)) {
            return false;
        }
        $age = ($now ?? time()) - (int) hexdec($match[1]);

        return $age >= 0 && $age <= self::LIFETIME_SECONDS && hash_equals(self::sign($match[1]), $match[2]);
    }

    private static function sign(string $issued): string
    {
        return substr(hash_hmac('sha256', 'pts-form|'.$issued, (string) config('app.key')), 0, 56);
    }
}
