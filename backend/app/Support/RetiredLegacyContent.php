<?php

namespace App\Support;

/** Legacy content types with no consumer in the Laravel API or Nuxt storefront. */
final class RetiredLegacyContent
{
    public const BANNER_PLACEMENTS = ['home-hero-bg', 'slide', 'banner-top', 'doitac', 'social', 'watermark', 'watermark-news'];

    public const PAGE_SECTION_PREFIX = 'archive.';
}
