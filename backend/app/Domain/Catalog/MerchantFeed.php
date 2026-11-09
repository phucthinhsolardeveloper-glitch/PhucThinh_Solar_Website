<?php

namespace App\Domain\Catalog;

use App\Filament\Support\Options;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Product feed for Google Merchant Center. A product is left out rather than sent with anything Google
 * would reject: it needs a stable code, a real price matching its page, and a clean approved picture.
 */
final class MerchantFeed
{
    public const HEADERS = ['id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'sale_price', 'brand', 'condition', 'identifier_exists', 'product_type'];

    public function __construct(private readonly SiteSettings $settings) {}

    public function pricesArePublic(): bool
    {
        return $this->settings->productPricesVisible();
    }

    /** @return array{rows: list<list<string>>, skipped: int} */
    public function build(string $origin): array
    {
        $paths = Options::categoryPaths();
        $brands = DB::table('brands')->pluck('name', 'id');
        $rows = [];
        $skipped = 0;
        foreach (DB::table('products')->where('is_visible', true)->orderBy('id')->cursor() as $product) {
            $row = $this->row($product, $origin, $paths, $brands);
            if ($row === null) {
                $skipped++;
            } else {
                $rows[] = $row;
            }
        }

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    /** @param list<list<string>> $rows */
    public function download(array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, self::HEADERS, ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, 'google-shopping-'.date('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return list<string>|null */
    private function row(object $product, string $origin, array $paths, $brands): ?array
    {
        $code = trim((string) $product->code);
        $title = self::plain((string) $product->name, 150);
        $image = trim((string) $product->feed_image);
        // The ID must not change between syncs, so an over-long code is left out instead of being cut.
        if ($code === '' || mb_strlen($code, 'UTF-8') > 50 || $title === '' || (string) $product->slug === '' || $product->quote_only) {
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

        return [$code, $title, self::plain($description, 5000), rtrim($origin, '/').'/san-pham/'.rawurlencode((string) $product->slug),
            rtrim($origin, '/').'/upload/product/'.rawurlencode($image), 'in_stock', number_format($price, 0, '.', '').' VND',
            $sale > 0 ? number_format($sale, 0, '.', '').' VND' : '', self::plain((string) ($brands[$product->brand_id] ?? '')), 'new', 'no',
            str_replace(' › ', ' > ', (string) ($paths[$product->category_id] ?? ''))];
    }

    private static function plain(string $text, int $limit = 0): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'))));

        return $limit > 0 ? mb_substr($text, 0, $limit, 'UTF-8') : $text;
    }
}
