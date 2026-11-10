<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\MerchantFeed;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Google Merchant Center fetches this on a schedule. Same rules as the CSV in Admin → Sản phẩm hàng loạt: only
 * products with a code, a real price and a 500 px Shopping picture, and nothing while prices are hidden on the site.
 */
class MerchantFeedController extends Controller
{
    public function __invoke(MerchantFeed $feed): Response
    {
        if (! $feed->pricesArePublic()) {
            return response('Feed is off while product prices are hidden.', 404, ['Content-Type' => 'text/plain; charset=utf-8', 'X-Robots-Tag' => 'noindex']);
        }
        $origin = (string) config('site.canonical_origin');
        // Built at most once an hour: a full pass reads every product picture's size.
        $xml = Cache::remember('merchant-feed-xml', 3600, fn () => $feed->xml($feed->build($origin)['rows'], $origin));

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
