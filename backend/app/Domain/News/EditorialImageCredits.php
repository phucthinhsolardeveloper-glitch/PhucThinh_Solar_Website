<?php

namespace App\Domain\News;

/**
 * Short cover attributions for packaged editorial assets.
 * These do not grant any rights; see source registry for permitted uses.
 */
final class EditorialImageCredits
{
    /** @return array{credit: string, source: string} */
    public static function forFile(?string $filename): array
    {
        $file = basename((string) $filename);
        if (preg_match('/^pts-plan-\d{3}\.webp$/', $file)) {
            return ['credit' => 'Hình minh họa: Phúc Thịnh Solar', 'source' => ''];
        }

        static $registry = null;
        if ($registry === null) {
            $path = base_path('resources/editorial/2026-11-12/image-credits.json');
            $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
            $registry = is_array($data) ? $data : [];
        }

        $record = $registry[$file] ?? [];
        if (! is_array($record)) {
            return ['credit' => '', 'source' => ''];
        }

        $credit = trim((string) ($record['credit'] ?? ''));
        $source = trim((string) ($record['source'] ?? ''));

        return [
            'credit' => mb_substr(strip_tags($credit), 0, 150),
            'source' => str_starts_with($source, 'https://www.pexels.com/photo/') ? $source : '',
        ];
    }
}
