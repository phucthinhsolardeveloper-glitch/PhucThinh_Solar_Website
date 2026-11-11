<?php

namespace App\Domain\Content;

use Closure;

/**
 * One rule for "this post is on the website": switched on AND its publish date has come.
 * A post switched on with a future "Ngày đăng" is scheduled: it appears by itself when the date passes,
 * no cron needed (the Nuxt API cache adds at most a minute).
 */
final class PostVisibility
{
    /** @return Closure for ->where(...) on a posts query; $prefix is the table alias with its dot, e.g. 'p.' */
    public static function live(string $prefix = ''): Closure
    {
        return fn ($q) => $q->where($prefix.'is_visible', true)
            ->where(fn ($q) => $q->whereNull($prefix.'published_at')->orWhere($prefix.'published_at', '<=', now()));
    }

    /** Same rule as a SQL expression, for selects that report visibility instead of filtering on it. */
    public static function liveSql(string $prefix = ''): string
    {
        return "({$prefix}is_visible = 1 and ({$prefix}published_at is null or {$prefix}published_at <= ?))";
    }

    public static function isScheduled(object $post): bool
    {
        return (bool) ($post->is_visible ?? false) && ! empty($post->published_at) && strtotime((string) $post->published_at) > now()->getTimestamp();
    }

    public static function isLive(object $post): bool
    {
        return (bool) ($post->is_visible ?? false) && ! self::isScheduled($post);
    }
}
