<?php

namespace App\Domain\Analytics;

use Illuminate\Support\Facades\DB;

/** Records pages seen on the public site, without cookies and without keeping the IP address. */
final class PageViews
{
    /** Rows older than this are deleted nightly. */
    public const KEEP_DAYS = 400;

    // 'internal' marks the later pages of a visit; only the first page says where the visitor came from.
    public const SOURCES = ['direct' => 'Truy cập thẳng', 'google' => 'Google', 'facebook' => 'Facebook', 'zalo' => 'Zalo',
        'search' => 'Công cụ tìm kiếm khác', 'campaign' => 'Quảng cáo / chiến dịch', 'site' => 'Website khác'];

    public const DEVICES = ['mobile' => 'Điện thoại', 'desktop' => 'Máy tính', 'tablet' => 'Máy tính bảng'];

    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|lighthouse|pingdom|uptime|curl|wget|python|java\/|go-http|axios|node-fetch|phantom/i';

    /** @return bool false when the hit was not counted */
    public function record(string $path, string $referrer, string $campaign, string $ip, string $agent, string $ownHost, bool $entry = true): bool
    {
        $path = '/'.ltrim((string) parse_url($path, PHP_URL_PATH), '/');
        if ($agent === '' || preg_match(self::BOTS, $agent) || strlen($path) > 255 || preg_match('#^/(admin|api|_api|_nuxt|livewire)(/|$)#', $path)) {
            return false;
        }
        $day = now()->toDateString();
        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        $host = preg_replace('/^(www\.|m\.|l\.|lm\.)/', '', $host) ?? '';
        if ($host !== '' && $host === preg_replace('/^www\./', '', strtolower($ownHost))) {
            $host = '';
        }
        DB::table('page_views')->insert([
            'day' => $day, 'path' => mb_substr(rtrim($path, '/') ?: '/', 0, 255),
            'visitor' => substr(hash_hmac('sha256', $day.'|'.$ip.'|'.$agent, (string) config('app.key')), 0, 16),
            'source' => $entry ? self::source($host, $campaign) : 'internal', 'referrer_host' => $host !== '' ? mb_substr($host, 0, 100) : null,
            'device' => preg_match('/ipad|tablet|(android(?!.*mobile))/i', $agent) ? 'tablet' : (preg_match('/mobi|iphone|android/i', $agent) ? 'mobile' : 'desktop'),
            'created_at' => now(),
        ]);

        return true;
    }

    private static function source(string $host, string $campaign): string
    {
        return match (true) {
            $campaign !== '' => 'campaign',
            $host === '' => 'direct',
            (bool) preg_match('/(^|\.)google\./', $host) => 'google',
            (bool) preg_match('/(^|\.)(facebook\.com|fb\.com|messenger\.com|instagram\.com)$/', $host) => 'facebook',
            (bool) preg_match('/(^|\.)zalo\.(me|vn)$/', $host) => 'zalo',
            (bool) preg_match('/(^|\.)(bing\.com|coccoc\.com|yahoo\.com|duckduckgo\.com|yandex\.)/', $host) => 'search',
            default => 'site',
        };
    }

    public static function prune(): int
    {
        return DB::table('page_views')->where('day', '<', now()->subDays(self::KEEP_DAYS)->toDateString())->delete();
    }
}
