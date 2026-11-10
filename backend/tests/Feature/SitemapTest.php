<?php

use Illuminate\Support\Facades\DB;

// The sitemap lists canonical addresses only: visible rows, no duplicates, no session cookie.

it('lists visible products, brands and posts by their canonical address', function () {
    $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8');
    expect($response->headers->getCookies())->toBe([]);
    $xml = $response->getContent();
    preg_match_all('#<loc>([^<]+)</loc>#', $xml, $m);
    $locs = $m[1];

    $origin = rtrim((string) config('site.canonical_origin'), '/');
    $product = DB::table('products')->where('is_visible', true)->value('slug');
    $hidden = DB::table('products')->where('is_visible', false)->value('slug');
    $brand = DB::table('brands')->where('is_visible', true)->value('slug');
    $policy = DB::table('posts')->where('kind', 'policy')->where('is_visible', true)->value('slug');

    expect($locs)->toContain($origin.'/', $origin.'/san-pham/'.$product, $origin.'/thuong-hieu/'.$brand.'/', $origin.'/ho-tro/'.$policy.'/')
        ->and(count($locs))->toBe(count(array_unique($locs)));
    expect($xml)->toContain('<url><loc>'.$origin.'/</loc></url>')
        ->toContain('<url><loc>'.$origin.'/san-pham/'.$product.'</loc><lastmod>');
    if ($hidden) {
        expect($locs)->not->toContain($origin.'/san-pham/'.$hidden);
    }
});
