<?php

namespace App\Domain\Routing;

use Illuminate\Support\Facades\DB;

/**
 * Old public addresses that now live elsewhere. A slug change records "old => new" so links already
 * shared or indexed keep working with a 301; chains are collapsed so a visitor is moved once.
 */
final class Redirects
{
    public static function record(string $from, string $to, bool $prefix = false, string $source = 'auto'): void
    {
        $from = self::normalise($from);
        $to = self::normalise($to);
        if ($from === null || $to === null || $from === $to) {
            return;
        }
        DB::transaction(function () use ($from, $to, $prefix, $source) {
            // The new address is live again: it must not redirect anywhere.
            DB::table('redirects')->where('from_path', $to)->delete();
            // Anything that pointed at the old address now goes straight to the new one.
            DB::table('redirects')->where('to_path', $from)->update(['to_path' => $to, 'updated_at' => now()]);
            DB::table('redirects')->updateOrInsert(['from_path' => $from],
                ['to_path' => $to, 'is_prefix' => $prefix, 'source' => $source, 'updated_at' => now(), 'created_at' => now()]);
        });
    }

    /** Where an address that no longer answers should go, or null. */
    public static function find(string $path): ?string
    {
        $path = self::normalise($path);
        if ($path === null) {
            return null;
        }
        $variants = array_values(array_unique([$path, rtrim($path, '/'), rtrim($path, '/').'/']));
        $row = DB::table('redirects')->whereIn('from_path', $variants)->first();
        $target = $row ? (string) $row->to_path : null;
        if ($row === null) {
            // Longest moved section containing this address, e.g. a renamed category and its children.
            $row = DB::table('redirects')->where('is_prefix', true)->get()
                ->filter(fn ($r) => str_starts_with($path, rtrim((string) $r->from_path, '/').'/'))
                ->sortByDesc(fn ($r) => strlen((string) $r->from_path))->first();
            $target = $row ? rtrim((string) $row->to_path, '/').'/'.ltrim(substr($path, strlen(rtrim((string) $row->from_path, '/').'/')), '/') : null;
        }
        if ($row === null || $target === null || $target === $path) {
            return null;
        }
        DB::table('redirects')->where('id', $row->id)->update(['hits' => DB::raw('hits + 1'), 'last_hit_at' => now()]);

        return $target;
    }

    /** "/a/b/" style internal path, or null for anything else (full URLs, traversal, query strings). */
    public static function normalise(string $path): ?string
    {
        $path = trim($path);
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || preg_match('#[\s?\#\\\\]|\.\.#', $path)) {
            return null;
        }

        return mb_strtolower($path);
    }
}
