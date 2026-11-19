<?php

namespace App\Domain\Catalog;

use App\Filament\Support\Options;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Product feed for Google Merchant Center. A product is left out rather than sent with anything Google
 * would reject: it needs a stable code, a real price matching its page, and a picture of at least 500 px (the clean Shopping picture, else the main one).
 */
final class MerchantFeed
{
    public const HEADERS = ['id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'sale_price', 'brand', 'condition', 'identifier_exists', 'product_type', 'additional_image_link'];

    public const VERIFIED_HEADERS = ['id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'sale_price', 'brand', 'condition', 'identifier_exists', 'gtin', 'mpn', 'product_type', 'additional_image_link'];

    public const VERIFIED_CACHE_KEY = 'merchant-feed-xml-verified-v1';

    /** Row count of the last verified build that fell below the release threshold. */
    public const VERIFIED_BELOW_CACHE_KEY = 'merchant-feed-xml-verified-v1-below';

    /** Product columns that decide whether, and how, a product appears in the verified feed. */
    public const VERIFIED_INPUT_COLUMNS = ['merchant_availability', 'merchant_gtin', 'merchant_mpn', 'merchant_identifiers_absent',
        'is_visible', 'quote_only', 'code', 'name', 'slug', 'regular_price', 'sale_price', 'image', 'feed_image', 'brand_id'];

    public function __construct(private readonly SiteSettings $settings) {}

    /**
     * A product change can lift the verified feed over its threshold or change an item's availability: drop the
     * verified caches so the next fetch rebuilds instead of serving the fallback or a stale "in stock" for an hour.
     * The legacy cache is left alone, as before.
     */
    public static function forgetVerifiedCache(): void
    {
        Cache::forget(self::VERIFIED_CACHE_KEY);
        Cache::forget(self::VERIFIED_BELOW_CACHE_KEY);
    }

    public function pricesArePublic(): bool
    {
        return $this->settings->productPricesVisible();
    }

    /** @return array{rows: list<list<string>>, skipped: int} */
    public function build(string $origin, bool $verified = false): array
    {
        $paths = Options::categoryPaths();
        $brands = DB::table('brands')->pluck('name', 'id');
        $rows = [];
        $skipped = 0;
        foreach (DB::table('products')->where('is_visible', true)->orderBy('id')->cursor() as $product) {
            $row = $this->row($product, $origin, $paths, $brands, $verified);
            if ($row === null) {
                $skipped++;
            } else {
                $rows[] = $row;
            }
        }

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    /** @param list<list<string>> $rows */
    public function download(array $rows, bool $verified = false): StreamedResponse
    {
        $headers = $verified ? self::VERIFIED_HEADERS : self::HEADERS;

        return response()->streamDownload(function () use ($rows, $headers): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, $headers, ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, 'google-shopping-'.date('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * The same rows as an RSS 2.0 feed Merchant Center can fetch on a schedule (/google-shopping.xml). Empty values
     * are left out rather than sent blank.
     *
     * @param  list<list<string>>  $rows
     */
    public function xml(array $rows, string $origin, bool $verified = false): string
    {
        $headers = $verified ? self::VERIFIED_HEADERS : self::HEADERS;
        $e = fn (string $text) => htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"><channel>'
            .'<title>'.$e($this->settings->siteName() ?: 'Phúc Thịnh Solar').'</title><link>'.$e(rtrim($origin, '/').'/').'</link>'
            .'<description>Sản phẩm Phúc Thịnh Solar</description>'."\n";
        foreach ($rows as $row) {
            $out .= '<item>';
            foreach ($headers as $i => $tag) {
                if (($row[$i] ?? '') === '') {
                    continue;
                }
                // Several pictures are kept comma-joined in one cell (the CSV form) and become one element each in XML.
                foreach ($tag === 'additional_image_link' ? explode(',', $row[$i]) : [$row[$i]] as $value) {
                    $out .= '<g:'.$tag.'>'.$e($value).'</g:'.$tag.'>';
                }
            }
            $out .= "</item>\n";
        }

        return $out.'</channel></rss>'."\n";
    }

    /**
     * Read-only readiness report: for every visible product, why it is left out of the legacy and verified feeds
     * (null when it is in) and which Merchant facts staff still have to supply. Nothing is guessed or written.
     *
     * @return list<array{id: int, code: string, name: string, slug: string, legacy: ?string, verified: ?string, brand: string, availability: string, gtin: string, mpn: string, identifier_exists: string, needs: list<string>}>
     */
    public function readiness(string $origin): array
    {
        $paths = Options::categoryPaths();
        $brands = DB::table('brands')->pluck('name', 'id');
        $report = [];
        foreach (DB::table('products')->where('is_visible', true)->orderBy('id')->cursor() as $product) {
            $legacy = $this->evaluate($product, $origin, $paths, $brands, false)['reason'];
            $verified = $this->evaluate($product, $origin, $paths, $brands, true)['reason'];
            $blockers = $this->listing($product, self::plain((string) $product->name, 150))['blockers'];
            $brand = self::plain((string) ($brands[$product->brand_id] ?? ''));
            $rawGtin = trim((string) ($product->merchant_gtin ?? ''));
            $rawMpn = trim((string) ($product->merchant_mpn ?? ''));
            $gtin = MerchantIdentifier::gtin($rawGtin);
            $mpn = MerchantIdentifier::mpn($rawMpn);
            $absent = (bool) ($product->merchant_identifiers_absent ?? false);
            $availability = trim((string) ($product->merchant_availability ?? ''));
            $needs = $blockers;
            if ($brand === '') {
                $needs[] = 'brand';
            }
            if ($availability !== 'in_stock') {
                $needs[] = 'availability';
            }
            if ($rawGtin !== '' && $gtin === '') {
                $needs[] = 'gtin_invalid';
            }
            if ($rawMpn !== '' && $mpn === '') {
                $needs[] = 'mpn_invalid';
            }
            if ($gtin === '' && $mpn === '' && ! $absent) {
                $needs[] = 'identifiers';
            }
            $report[] = [
                'id' => (int) $product->id,
                'code' => trim((string) $product->code),
                'name' => self::plain((string) $product->name),
                'slug' => (string) $product->slug,
                'legacy' => $legacy,
                'verified' => $verified,
                'brand' => $brand,
                'availability' => $availability,
                'gtin' => $gtin,
                'mpn' => $mpn,
                'identifier_exists' => MerchantIdentifier::exists($gtin, $mpn, $brand !== '', $absent, $rawGtin !== '' || $rawMpn !== ''),
                'needs' => array_values(array_unique($needs)),
            ];
        }

        return $report;
    }

    /** @return list<string>|null */
    private function row(object $product, string $origin, array $paths, $brands, bool $verified): ?array
    {
        return $this->evaluate($product, $origin, $paths, $brands, $verified)['row'];
    }

    /**
     * One product's feed row, or the first reason it is left out.
     *
     * @return array{row: list<string>|null, reason: string|null}
     */
    private function evaluate(object $product, string $origin, array $paths, $brands, bool $verified): array
    {
        $code = trim((string) $product->code);
        $title = self::plain((string) $product->name, 150);
        $availability = $verified ? trim((string) ($product->merchant_availability ?? '')) : 'in_stock';
        $legacyBrand = (string) ($brands[$product->brand_id] ?? '');
        $brand = self::plain($legacyBrand);
        $rawGtin = trim((string) ($product->merchant_gtin ?? ''));
        $rawMpn = trim((string) ($product->merchant_mpn ?? ''));
        $gtin = MerchantIdentifier::gtin($rawGtin);
        $mpn = MerchantIdentifier::mpn($rawMpn);
        $identifierExists = MerchantIdentifier::exists(
            $gtin,
            $mpn,
            $brand !== '',
            (bool) ($product->merchant_identifiers_absent ?? false),
            $rawGtin !== '' || $rawMpn !== '',
        );
        if ($verified && $availability !== 'in_stock') {
            return ['row' => null, 'reason' => 'availability'];
        }
        ['blockers' => $blockers, 'image' => $image, 'price' => $price, 'sale' => $sale] = $this->listing($product, $title);
        if ($blockers !== []) {
            return ['row' => null, 'reason' => $blockers[0]];
        }
        $description = self::plain((string) $product->summary) ?: $title;
        if ((float) $product->power_watts > 0) {
            $description .= ' Công suất: '.rtrim(rtrim(number_format((float) $product->power_watts, 2, '.', ''), '0'), '.').' W.';
        }

        $productType = str_replace(' › ', ' > ', (string) ($paths[$product->category_id] ?? ''));
        $extra = $this->extraImages((int) $product->id, $image, trim((string) $product->image_secondary), $origin);
        $common = [$code, $title, self::plain($description, 5000), rtrim($origin, '/').'/san-pham/'.rawurlencode((string) $product->slug),
            rtrim($origin, '/').'/upload/product/'.rawurlencode($image)];
        $prices = [number_format($price, 0, '.', '').' VND', $sale > 0 ? number_format($sale, 0, '.', '').' VND' : '', $brand, 'new'];

        // Legacy mode (release gate off) is kept column for column as published: HEADERS, in_stock, identifier_exists=no.
        if (! $verified) {
            return ['row' => [...$common, 'in_stock', ...$prices, 'no', $productType, $extra], 'reason' => null];
        }

        return ['row' => [...$common, $availability, ...$prices, $identifierExists, $gtin, $mpn, $productType, $extra], 'reason' => null];
    }

    /**
     * Every independent reason a product cannot be listed (in the order the feed reports the first one), plus the
     * picture and prices it would be sent with. The clean Shopping picture when there is one, otherwise the main
     * picture (owner decision 2026-11-13); a broken Shopping picture is reported, not silently swapped for the main one,
     * which may carry text or a logo.
     *
     * @return array{blockers: list<string>, image: string, price: float, sale: float}
     */
    private function listing(object $product, string $title): array
    {
        $blockers = [];
        $code = trim((string) $product->code);
        // The ID must not change between syncs, so an over-long code is left out instead of being cut.
        if ($code === '') {
            $blockers[] = 'code_missing';
        } elseif (mb_strlen($code, 'UTF-8') > 50) {
            $blockers[] = 'code_too_long';
        }
        if ($title === '') {
            $blockers[] = 'title';
        }
        if ((string) $product->slug === '') {
            $blockers[] = 'slug';
        }
        if ($product->quote_only) {
            $blockers[] = 'quote_only';
        }
        $image = trim((string) $product->feed_image) !== '' ? trim((string) $product->feed_image) : trim((string) $product->image);
        $imageProblem = self::imageProblem($image);
        if ($imageProblem !== null) {
            $blockers[] = $imageProblem;
        }
        $price = (float) $product->regular_price;
        $sale = (float) $product->sale_price;
        if ($price <= 0 && $sale > 0) {
            [$price, $sale] = [$sale, 0.0];
        }
        if ($price <= 0) {
            $blockers[] = 'price_missing';
        } elseif ($sale > 0 && $sale >= $price) {
            $blockers[] = 'sale_not_below_price';
        }

        return ['blockers' => $blockers, 'image' => $image, 'price' => $price, 'sale' => $sale];
    }

    /** Why a picture cannot be offered to Merchant Center (a plain file name, a picture format, at least 500 px), or null. */
    private static function imageProblem(string $file): ?string
    {
        if ($file === '') {
            return 'image_missing';
        }
        if (basename($file) !== $file || ! preg_match('/\.(?:jpe?g|png|webp|gif)$/i', $file)) {
            return 'image_name';
        }
        $size = @getimagesize(Storage::disk('upload_product')->path($file));
        if (! $size) {
            return 'image_file';
        }

        return $size[0] < 500 || $size[1] < 500 ? 'image_too_small' : null;
    }

    /**
     * Up to five more REAL photos of the product (its second picture, then the first slots of its gallery). The numbered concept/poster pictures
     * (sort_order 100 and above) carry text and are not offered to Google as product photos.
     */
    private function extraImages(int $productId, string $mainImage, string $secondImage, string $origin): string
    {
        $urls = [];
        $rows = DB::table('product_images')->where('product_id', $productId)->where('is_visible', true)->where('sort_order', '<', 100)->orderBy('sort_order')->orderBy('id')->limit(12)->pluck('path');
        foreach ([$secondImage, ...$rows->all()] as $path) {
            $file = trim((string) $path);
            if ($file === '' || $file === $mainImage || basename($file) !== $file || ! preg_match('/\.(?:jpe?g|png|webp|gif)$/i', $file)) {
                continue;
            }
            $size = @getimagesize(Storage::disk('upload_product')->path($file));
            if (! $size || $size[0] < 500 || $size[1] < 500) {
                continue;
            }
            $urls[] = rtrim($origin, '/').'/upload/product/'.rawurlencode($file);
            if (count($urls) === 5) {
                break;
            }
        }

        return implode(',', array_unique($urls));
    }

    private static function plain(string $text, int $limit = 0): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'))));

        return $limit > 0 ? mb_substr($text, 0, $limit, 'UTF-8') : $text;
    }
}
