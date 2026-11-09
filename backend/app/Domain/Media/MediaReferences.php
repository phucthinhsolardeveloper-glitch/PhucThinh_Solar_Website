<?php

namespace App\Domain\Media;

use App\Support\RichText;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every place the site points at an uploaded file: picture columns (bare file names inside one
 * upload folder), description blocks, rich text and settings ("/upload/..." links).
 * The result maps a path relative to the uploads root to the places using it.
 */
final class MediaReferences
{
    /** table => [column => candidate folders]; a bare name is looked up in each folder in turn. */
    public const COLUMNS = [
        'products' => ['image' => ['product'], 'image_secondary' => ['product'], 'feed_image' => ['product']],
        'product_images' => ['path' => ['product']],
        'product_documents' => ['path' => ['file']],
        'albums' => ['image' => ['product']],
        'album_images' => ['path' => ['product']],
        'brands' => ['image' => ['product']],
        'categories' => ['image' => ['product']],
        'banners' => ['image' => ['photo']],
        'page_items' => ['image' => ['news']],
        'posts' => ['image' => ['news'], 'cover_image' => ['news']],
        'post_media' => ['path' => ['news']],
        'post_categories' => ['image' => ['news']],
        'seo_metas' => ['image' => ['seopage', 'product', 'news']],
        'review_media' => ['path' => ['photo', 'video'], 'poster' => ['photo']],
        'order_items' => ['image' => ['product']],
        'product_campaigns' => ['frame_image' => ['photo']],
    ];

    /** Columns holding HTML or JSON in which "/upload/..." links are looked for. */
    public const TEXT = [
        'products' => ['body', 'highlights', 'applications', 'installation', 'specs_html', 'purchase_policy'],
        'brands' => ['body'],
        'categories' => ['body'],
        'page_items' => ['body'],
        'posts' => ['body', 'extra_body'],
        'post_categories' => ['body'],
        'settings' => ['value'],
    ];

    public function __construct(private readonly Filesystem $uploads) {}

    /** @return array<string, list<string>> path => places ("products#12.image") */
    public function collect(): array
    {
        $found = [];
        $add = function (string $path, string $place) use (&$found): void {
            $found[$path][] = $place;
        };
        foreach (self::COLUMNS as $table => $columns) {
            $this->eachRow($table, array_keys($columns), function (object $row, string $id) use ($table, $columns, $add) {
                foreach ($columns as $column => $folders) {
                    $path = $this->resolve($row->{$column} ?? null, $folders);
                    if ($path !== null) {
                        $add($path, $table.'#'.$id.'.'.$column);
                    }
                }
            });
        }
        $this->eachRow('content_sections', ['blocks'], function (object $row, string $id) use ($add) {
            foreach ($this->blockPhotos($row->blocks ?? null) as $photo) {
                $path = $this->resolve($photo, ['product']);
                if ($path !== null) {
                    $add($path, 'content_sections#'.$id.'.blocks');
                }
            }
        });
        foreach (self::TEXT as $table => $columns) {
            $columns = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
            $this->eachRow($table, $columns, function (object $row, string $id) use ($table, $columns, $add) {
                foreach ($columns as $column) {
                    foreach (self::links((string) ($row->{$column} ?? '')) as $path) {
                        $add($path, $table.'#'.$id.'.'.$column);
                    }
                }
            });
        }

        foreach ((array) config('media.pinned', []) as $path) {
            $add((string) $path, 'Nuxt (config/media.php)');
        }
        foreach ($this->sourceLinks() as $path => $file) {
            $add($path, 'code: '.$file);
        }

        return array_map(fn ($places) => array_values(array_unique($places)), $found);
    }

    /** Paths after "/upload/" in a piece of HTML, JSON or text (escaped slashes included). */
    public static function links(string $text): array
    {
        if ($text === '' || ! str_contains($text, 'upload')) {
            return [];
        }
        $text = str_replace('\\/', '/', $text);
        preg_match_all('#(?:https?://(?:www\.)?phucthinhsolar\.com)?/upload/([A-Za-z0-9._~%/-]+\.[A-Za-z0-9]{2,5})#i', $text, $m);

        return array_values(array_unique(array_filter(array_map(fn ($p) => self::clean(rawurldecode($p)), $m[1]))));
    }

    /** The stored value as a path under the uploads root, or null for empty and outside links. */
    public function resolve(mixed $value, array $folders): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        if (preg_match('#^https?://#i', $value)) {
            $parts = parse_url($value);
            if (! is_array($parts) || ! RichText::isCmsHost($parts['host'] ?? '')) {
                return null;
            }
            $value = (string) ($parts['path'] ?? '');
        }
        $value = ltrim($value, '/');
        if (str_starts_with($value, 'upload/')) {
            return self::clean(rawurldecode(substr($value, 7)));
        }
        $candidates = array_map(fn ($folder) => $folder.'/'.$value, $folders);
        if (str_contains($value, '/')) {
            $candidates[] = $value;
        }
        foreach ($candidates as $candidate) {
            $candidate = self::clean($candidate);
            if ($candidate !== null && $this->uploads->exists($candidate)) {
                return $candidate;
            }
        }

        return self::clean($candidates[0]);
    }

    /** @return array<string, string> uploads written into the PHP source => first file naming it */
    private function sourceLinks(): array
    {
        $links = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            foreach (self::links((string) file_get_contents($file->getPathname())) as $path) {
                $links[$path] ??= str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        return $links;
    }

    private static function clean(string $path): ?string
    {
        $path = trim($path, '/');

        return $path === '' || str_contains($path, '..') || str_contains($path, "\0") || str_starts_with($path, '.') ? null : $path;
    }

    /** @return list<string> */
    private function blockPhotos(mixed $json): array
    {
        $blocks = is_string($json) ? json_decode($json, true) : $json;
        $photos = [];
        array_walk_recursive($blocks, function ($value, $key) use (&$photos) {
            if ($key === 'photo' && is_string($value)) {
                $photos[] = $value;
            }
        });

        return $photos;
    }

    /** @param  list<string>  $columns */
    private function eachRow(string $table, array $columns, \Closure $each): void
    {
        if ($columns === [] || ! Schema::hasTable($table)) {
            return;
        }
        $key = $table === 'settings' ? 'key' : 'id';
        $query = DB::table($table)->select(array_merge([$key], $columns));
        foreach ($key === 'id' ? $query->lazyById(500) : $query->orderBy($key)->get() as $row) {
            $each($row, (string) $row->{$key});
        }
    }
}
