<?php

namespace App\Filament\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * "Mới" labels for features added to Admin. A label shows the first time an account sees it and goes away once that
 * account has opened the page the feature lives on (or after SHOW_DAYS, so an unvisited label does not stay forever).
 *
 * To announce a new feature: add a line to FEATURES (the date it shipped and the Admin path whose visit counts as
 * "seen"), then put the label where people will look: a resource's getNavigationBadge(), or a form/table label.
 */
final class NewFeatures
{
    /** Days a label is shown to an account that never opens the page. */
    public const SHOW_DAYS = 45;

    /** key => shipped date, and a pattern for the Admin paths whose visit marks it seen. */
    public const FEATURES = [
        'kho-chi-nhanh' => ['since' => '2026-11-19', 'visit' => '#^admin/store-locations(/|$)#'],
        'kho-san-pham' => ['since' => '2026-11-19', 'visit' => '#^admin/products/\d+/edit#'],
        'kho-don-hang' => ['since' => '2026-11-19', 'visit' => '#^admin/orders/\d+/edit#'],
        'mau-email' => ['since' => '2026-11-19', 'visit' => '#^admin/mau-email(/|$)#'],
    ];

    /** True while this account should still see the label. */
    public static function isNew(string $key, ?User $user = null): bool
    {
        $feature = self::FEATURES[$key] ?? null;
        $user ??= auth('admin')->user();
        if ($feature === null || ! $user instanceof User) {
            return false;
        }
        if (now()->greaterThan(now()->parse($feature['since'])->addDays(self::SHOW_DAYS))) {
            return false;
        }

        return ! in_array($key, self::seen($user), true);
    }

    /** Text for a navigation badge, or null when there is nothing to show. */
    public static function badge(string $key): ?string
    {
        return self::isNew($key) ? 'Mới' : null;
    }

    /** A form or table label followed by a small "Mới" chip while the feature is new for this account. */
    public static function label(string $text, string $key): string|HtmlString
    {
        if (! self::isNew($key)) {
            return $text;
        }

        return new HtmlString(e($text).'<span style="display:inline-block;margin-left:8px;padding:1px 8px;border-radius:999px;background:#fdf1cf;color:#7a5605;border:1px solid #ecd08a;font-size:11px;font-weight:700;line-height:16px;vertical-align:1px">Mới</span>');
    }

    /** Records every feature whose page this request opened. Called after the page is built, so the label shows once. */
    public static function markVisited(Request $request, ?User $user): void
    {
        if (! $user instanceof User || ! $request->isMethod('GET')) {
            return;
        }
        $path = ltrim($request->path(), '/');
        $seen = self::seen($user);
        $now = $seen;
        foreach (self::FEATURES as $key => $feature) {
            if (! in_array($key, $now, true) && preg_match($feature['visit'], $path) === 1) {
                $now[] = $key;
            }
        }
        if ($now !== $seen) {
            DB::table($user->getTable())->where('id', $user->getKey())->update(['seen_features' => json_encode(array_values($now))]);
            $user->setAttribute('seen_features', json_encode(array_values($now)));
            $user->syncOriginal();
        }
    }

    /** @return list<string> */
    private static function seen(User $user): array
    {
        $raw = $user->getAttribute('seen_features');
        $list = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
    }
}
