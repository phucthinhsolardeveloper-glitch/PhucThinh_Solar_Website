<?php

namespace App\Http\Controllers;

use App\Domain\Seo\Sitemap;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(Sitemap $sitemap): Response
    {
        return response($sitemap->xml((string) config('site.canonical_origin')), 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
