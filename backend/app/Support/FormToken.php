<?php

namespace App\Support;

/**
 * Stateless anti-forgery token for public forms (replaces the PHP session token formCsrfToken()).
 * Nuxt renders pages server-side and has no PHP session to share, so the token is signed with the
 * app key and carries its issue time; write endpoints accept it for a limited time.
 */
final class FormToken
{
    public const LIFETIME_SECONDS = 7200;

    public static function issue(?int $now = null): string
    {
        $issued = (string) ($now ?? time());

        return $issued.'.'.self::sign($issued);
    }

    public static function valid(mixed $token, ?int $now = null): bool
    {
        if (! is_string($token) || ! preg_match('/^(\d{10})\.([a-f0-9]{64})$/', $token, $match)) {
            return false;
        }
        $age = ($now ?? time()) - (int) $match[1];

        return $age >= 0 && $age <= self::LIFETIME_SECONDS && hash_equals(self::sign($match[1]), $match[2]);
    }

    private static function sign(string $issued): string
    {
        return hash_hmac('sha256', 'pts-form|'.$issued, (string) config('app.key'));
    }
}
