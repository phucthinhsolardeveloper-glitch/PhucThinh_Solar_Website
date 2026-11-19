<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\MerchantFeed;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * The verified feed is an opt-in release. Until product availability has been reviewed, the public route keeps its
 * legacy response and cache so adding nullable attributes cannot remove existing Merchant Center items.
 */
class MerchantFeedController extends Controller
{
    public function __invoke(MerchantFeed $feed): Response
    {
        if (! $feed->pricesArePublic()) {
            return response('Feed is off while product prices are hidden.', 404, ['Content-Type' => 'text/plain; charset=utf-8', 'X-Robots-Tag' => 'noindex']);
        }
        $origin = (string) config('site.canonical_origin');
        $verified = (bool) config('services.merchant_feed.verified_enabled', false);
        $cacheKey = $verified ? 'merchant-feed-xml-verified-v1' : 'merchant-feed-xml';
        $feedMode = $verified ? 'verified' : 'legacy';
        $xml = Cache::get($cacheKey);
        if (! is_string($xml)) {
            $minimum = max(0, (int) config('services.merchant_feed.verified_minimum_items', 286));
            $belowKey = 'merchant-feed-xml-verified-v1-below';
            $belowCount = $verified ? Cache::get($belowKey) : null;
            $cachedBelow = $verified && is_int($belowCount) && ($belowCount === 0 || $belowCount < $minimum);
            // An unchanged failed release gate avoids a full image-size scan on each fetch.
            $rows = $cachedBelow ? [] : $feed->build($origin, $verified)['rows'];
            if ($verified && ($cachedBelow || $rows === [] || count($rows) < $minimum)) {
                if (! $cachedBelow) {
                    Cache::put($belowKey, count($rows), 3600);
                }
                // Preserve the currently published product set if availability has not been fully confirmed.
                $feedMode = 'legacy-fallback';
                $xml = Cache::get('merchant-feed-xml');
                if (! is_string($xml)) {
                    $legacyRows = $feed->build($origin)['rows'];
                    if ($legacyRows === []) {
                        return response('Verified Merchant feed is below its release threshold and no legacy feed is available.', 503, [
                            'Content-Type' => 'text/plain; charset=utf-8',
                            'Cache-Control' => 'no-store',
                            'Retry-After' => '3600',
                            'X-Robots-Tag' => 'noindex',
                            'X-Feed-Mode' => $feedMode,
                        ]);
                    }
                    $xml = $feed->xml($legacyRows, $origin);
                    Cache::put('merchant-feed-xml', $xml, 3600);
                }
            } else {
                $xml = $feed->xml($rows, $origin, $verified);
                Cache::put($cacheKey, $xml, 3600);
                if ($verified) {
                    Cache::forget($belowKey);
                }
            }
        }

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex',
            'X-Feed-Mode' => $feedMode,
        ]);
    }
}
