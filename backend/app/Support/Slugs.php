<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Slugs: made from a name, checked against the pages that share an address space, and made unique
 * by adding "-2", "-3"... so nobody has to invent one by hand.
 */
final class Slugs
{
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Products, brands, albums, root categories, articles, projects and top editorial categories all
     * answer on "/<slug>" (or are resolved from it), so a slug must be free across all of them.
     * table => conditions of the rows that take part.
     */
    private const PUBLIC_SPACE = [
        'products' => [], 'brands' => [], 'albums' => [],
        'categories' => ['parent_id' => null],
        'posts' => ['kind' => ['article', 'project']], 'post_categories' => ['depth' => 1],
    ];

    public static function from(?string $text): string
    {
        return Str::limit(Str::slug((string) $text), 200, '');
    }

    /** First free variant of $base: "ten", "ten-2", "ten-3"... */
    public static function free(string $base, Closure $taken): string
    {
        $base = trim(self::from($base), '-') ?: 'trang';
        for ($n = 1; ; $n++) {
            $slug = $n === 1 ? $base : $base.'-'.$n;
            if (! $taken($slug)) {
                return $slug;
            }
        }
    }

    /** Taken in the shared "/<slug>" space by a row other than $table#$ignoreId. */
    public static function takenPublic(string $slug, string $table, ?int $ignoreId = null): bool
    {
        foreach (self::PUBLIC_SPACE as $other => $where) {
            $query = DB::table($other)->where('slug', $slug);
            foreach ($where as $column => $expected) {
                match (true) {
                    $expected === null => $query->whereNull($column),
                    is_array($expected) => $query->whereIn($column, $expected),
                    default => $query->where($column, $expected),
                };
            }
            if ($other === $table && $ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, list<array{table: string, id: int}>> every slug of the shared space and who holds it */
    public static function owners(): array
    {
        $owners = [];
        foreach (self::PUBLIC_SPACE as $table => $where) {
            $query = DB::table($table)->whereNotNull('slug')->where('slug', '!=', '');
            foreach ($where as $column => $expected) {
                match (true) {
                    $expected === null => $query->whereNull($column),
                    is_array($expected) => $query->whereIn($column, $expected),
                    default => $query->where($column, $expected),
                };
            }
            foreach ($query->get(['id', 'slug']) as $row) {
                $owners[strtolower((string) $row->slug)][] = ['table' => $table, 'id' => (int) $row->id];
            }
        }

        return $owners;
    }

    /** @return Closure(string): bool */
    public static function publicScope(string $table, ?int $ignoreId = null): Closure
    {
        return fn (string $slug) => self::takenPublic($slug, $table, $ignoreId);
    }

    /** @return Closure(string): bool taken among rows of $table matching $where */
    public static function scope(string $table, array $where, ?int $ignoreId = null): Closure
    {
        return fn (string $slug) => DB::table($table)->where('slug', $slug)->where($where)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
    }
}
