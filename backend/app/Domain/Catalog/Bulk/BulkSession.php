<?php

namespace App\Domain\Catalog\Bulk;

use Illuminate\Support\Facades\Storage;

/** A previewed file waiting for confirmation: private to its uploader and gone after 30 minutes. */
final class BulkSession
{
    private const DIR = 'bulk-previews';

    private const TTL = 1800;

    /** @param array{rows: list<array<string, string>>, baseline: array<int, array<string, string>>} $read */
    public static function put(array $read, int $userId): string
    {
        self::prune();
        $token = bin2hex(random_bytes(24));
        Storage::disk('local')->put(self::path($token), json_encode($read + ['user' => $userId, 'at' => time()], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $token;
    }

    /** @return array{rows: list<array<string, string>>, baseline: array<int, array<string, string>>}|null */
    public static function get(?string $token, int $userId): ?array
    {
        if ($token === null || ! ctype_xdigit($token) || ! Storage::disk('local')->exists(self::path($token))) {
            return null;
        }
        $data = json_decode((string) Storage::disk('local')->get(self::path($token)), true);

        return is_array($data) && (int) $data['user'] === $userId && time() - (int) $data['at'] <= self::TTL ? $data : null;
    }

    /** @param array<string, mixed> $plan */
    public static function putPlan(string $token, array $plan): void
    {
        Storage::disk('local')->put(self::DIR.'/'.$token.'.plan', json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed>|null */
    public static function plan(?string $token): ?array
    {
        if ($token === null || ! ctype_xdigit($token) || ! Storage::disk('local')->exists(self::DIR.'/'.$token.'.plan')) {
            return null;
        }

        return json_decode((string) Storage::disk('local')->get(self::DIR.'/'.$token.'.plan'), true);
    }

    public static function forget(?string $token): void
    {
        if ($token !== null && ctype_xdigit($token)) {
            Storage::disk('local')->delete([self::path($token), self::DIR.'/'.$token.'.plan']);
        }
    }

    private static function prune(): void
    {
        foreach (Storage::disk('local')->files(self::DIR) as $file) {
            if (Storage::disk('local')->lastModified($file) < time() - self::TTL) {
                Storage::disk('local')->delete($file);
            }
        }
    }

    private static function path(string $token): string
    {
        return self::DIR.'/'.$token.'.json';
    }
}
