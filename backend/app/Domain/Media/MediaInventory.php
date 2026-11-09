<?php

namespace App\Domain\Media;

use App\Models\MediaFile;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Keeps media_files in step with the uploads folder and with the pages that use each file.
 * It never moves, renames or deletes a published file, so old /upload/... links keep working;
 * the only thing it removes is abandoned review uploads in the hidden staging folder.
 */
final class MediaInventory
{
    /** Hidden folder where review uploads wait for their database row (see ReviewSubmission). */
    public const STAGING = '.review-staging';

    private readonly Filesystem $uploads;

    public function __construct(?Filesystem $uploads = null)
    {
        $this->uploads = $uploads ?? Storage::disk('uploads');
    }

    /**
     * Records size, type, dimensions and hash of every file (re-reading only changed ones), how many
     * places use it, and which referenced files are missing.
     *
     * @return array{files: int, new: int, changed: int, unused: int, broken: int, gone: int}
     */
    public function scan(): array
    {
        $now = now();
        $references = (new MediaReferences($this->uploads))->collect();
        $variant = fn (string $path): array => str_ends_with($path, '.webp') && isset($references[substr($path, 0, -5)])
            ? ['WebP của '.substr($path, 0, -5)] : [];
        $known = MediaFile::query()->get()->keyBy('path');
        $stats = ['files' => 0, 'new' => 0, 'changed' => 0, 'unused' => 0, 'broken' => 0, 'gone' => 0];
        $seen = [];

        foreach ($this->uploads->allFiles() as $path) {
            if ($this->hidden($path)) {
                continue;
            }
            $seen[$path] = true;
            $stats['files']++;
            $places = $references[$path] ?? $variant($path);
            $stats['unused'] += $places === [] ? 1 : 0;
            $size = (int) $this->uploads->size($path);
            $modified = (int) $this->uploads->lastModified($path);
            $row = $known->get($path);
            $unchanged = $row && $row->on_disk && $row->size === $size && $row->modified_at?->getTimestamp() === $modified && $row->sha256 !== null;
            $values = ['folder' => self::folder($path), 'on_disk' => true, 'missing_since' => null, 'scanned_at' => $now,
                'usage_count' => count($places), 'usages' => array_slice($places, 0, 20)];
            if (! $unchanged) {
                $values += $this->describe($path) + ['size' => $size, 'modified_at' => date('Y-m-d H:i:s', $modified)];
                $stats[$row ? 'changed' : 'new']++;
            }
            $this->save($path, $values, $row === null, $now);
        }

        foreach ($references as $path => $places) {
            if (isset($seen[$path])) {
                continue;
            }
            $stats['broken']++;
            $row = $known->get($path);
            $this->save($path, ['folder' => self::folder($path), 'on_disk' => false, 'usage_count' => count($places), 'usages' => array_slice($places, 0, 20),
                'missing_since' => $row->missing_since ?? $now, 'scanned_at' => $now], $row === null, $now);
            $seen[$path] = true;
        }

        // Files that disappeared and nothing points to any more: the row has nothing left to say.
        $stale = $known->keys()->reject(fn ($path) => isset($seen[$path]))->values();
        $stats['gone'] = $stale->count();
        $stale->chunk(500)->each(fn ($chunk) => MediaFile::query()->whereIn('path', $chunk->all())->delete());

        return $stats;
    }

    /** Removes review upload batches older than $hours (a crash between upload and save leaves them). */
    public function pruneStaging(int $hours = 24): int
    {
        $limit = now()->subHours(max(1, $hours))->getTimestamp();
        $removed = 0;
        foreach ($this->uploads->directories(self::STAGING) as $batch) {
            $files = $this->uploads->allFiles($batch);
            $newest = $files === [] ? 0 : max(array_map(fn ($file) => (int) $this->uploads->lastModified($file), $files));
            if ($newest < $limit && $this->uploads->deleteDirectory($batch)) {
                $removed++;
            }
        }

        return $removed;
    }

    /** @return array{files: int, bytes: int, unused: int, unused_bytes: int, broken: int, duplicates: int, scanned_at: ?string} */
    public static function health(): array
    {
        $files = MediaFile::query()->where('on_disk', true);

        return [
            'files' => (clone $files)->count(),
            'bytes' => (int) (clone $files)->sum('size'),
            'unused' => (clone $files)->where('usage_count', 0)->count(),
            'unused_bytes' => (int) (clone $files)->where('usage_count', 0)->sum('size'),
            'broken' => MediaFile::query()->where('on_disk', false)->count(),
            'duplicates' => (int) DB::query()->fromSub(MediaFile::query()->select('sha256')->where('on_disk', true)->whereNotNull('sha256')
                ->groupBy('sha256')->havingRaw('count(*) > 1'), 'd')->count(),
            'scanned_at' => MediaFile::query()->max('scanned_at'),
        ];
    }

    /** @return array{mime: ?string, width: ?int, height: ?int, sha256: ?string} */
    private function describe(string $path): array
    {
        $absolute = $this->uploads->path($path);
        $mime = @mime_content_type($absolute) ?: null;
        $info = $mime !== null && str_starts_with($mime, 'image/') ? @getimagesize($absolute) : false;

        return [
            'mime' => $mime,
            'width' => is_array($info) ? (int) $info[0] : null,
            'height' => is_array($info) ? (int) $info[1] : null,
            'sha256' => @hash_file('sha256', $absolute) ?: null,
        ];
    }

    private function save(string $path, array $values, bool $new, mixed $now): void
    {
        if ($new) {
            MediaFile::query()->insert(['path' => $path, 'first_seen_at' => $now] + array_map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v, $values));

            return;
        }
        MediaFile::query()->where('path', $path)->update(array_map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v, $values));
    }

    private function hidden(string $path): bool
    {
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment[0] === '.') {
                return true;
            }
        }

        return false;
    }

    private static function folder(string $path): string
    {
        return str_contains($path, '/') ? strstr($path, '/', true) : '';
    }
}
