<?php

namespace App\Domain\Catalog;

use App\Filament\Support\Options;
use App\Support\SiteSettings;
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

    public function __construct(private readonly SiteSettings $settings) {}

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

    /** @return list<string>|null */
    private function row(object $product, string $origin, array $paths, $brands, bool $verified): ?array
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
        // The clean Shopping picture when there is one; otherwise the main product picture (owner decision 2026-11-13).
        // Google may still reject an individual product whose picture carries text or a logo; the others are unaffected.
        $image = trim((string) $product->feed_image) !== '' ? trim((string) $product->feed_image) : trim((string) $product->image);
        // The ID must not change between syncs, so an over-long code is left out instead of being cut.
        if (($verified && $availability !== 'in_stock') || $code === '' || mb_strlen($code, 'UTF-8') > 50 || $title === '' || (string) $product->slug === '' || $product->quote_only) {
            return null;
        }
        if ($image === '' || basename($image) !== $image || ! preg_match('/\.(?:jpe?g|png|webp|gif)$/i', $image)) {
            return null;
        }
        $size = @getimagesize(Storage::disk('upload_product')->path($image));
        if (! $size || $size[0] < 500 || $size[1] < 500) {
            return null;
        }
        $price = (float) $product->regular_price;
        $sale = (float) $product->sale_price;
        if ($price <= 0 && $sale > 0) {
            [$price, $sale] = [$sale, 0.0];
        }
        if ($price <= 0 || ($sale > 0 && $sale >= $price)) {
            return null;
        }
        $description = self::plain((string) $product->summary) ?: $title;
        if ((float) $product->power_watts > 0) {
            $description .= ' Công suất: '.rtrim(rtrim(number_format((float) $product->power_watts, 2, '.', ''), '0'), '.').' W.';
        }

        $row = [$code, $title, self::plain($description, 5000), rtrim($origin, '/').'/san-pham/'.rawurlencode((string) $product->slug),
            rtrim($origin, '/').'/upload/product/'.rawurlencode($image), 'in_stock', number_format($price, 0, '.', '').' VND',
            $sale > 0 ? number_format($sale, 0, '.', '').' VND' : '', $brand, 'new', 'no',
            str_replace(' › ', ' > ', (string) ($paths[$product->category_id] ?? '')), $this->extraImages((int) $product->id, $image, trim((string) $product->image_secondary), $origin)];

        if (! $verified) {
            return $row;
        }

        return [$code, $title, self::plain($description, 5000), rtrim($origin, '/').'/san-pham/'.rawurlencode((string) $product->slug),
            rtrim($origin, '/').'/upload/product/'.rawurlencode($image), $availability, number_format($price, 0, '.', '').' VND',
            $sale > 0 ? number_format($sale, 0, '.', '').' VND' : '', $brand, 'new', $identifierExists, $gtin, $mpn,
            str_replace(' › ', ' > ', (string) ($paths[$product->category_id] ?? '')), $this->extraImages((int) $product->id, $image, trim((string) $product->image_secondary), $origin)];
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
