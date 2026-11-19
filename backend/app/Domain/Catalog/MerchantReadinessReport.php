<?php

namespace App\Domain\Catalog;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV of MerchantFeed::readiness() for staff: every blocker per product and the field to fill from a real source. */
final class MerchantReadinessReport
{
    public const COLUMNS = ['id', 'code', 'name', 'slug', 'legacy_feed', 'verified_feed', 'brand', 'availability', 'gtin', 'mpn', 'identifier_exists', 'needs', 'can_bo_sung'];

    /** What each blocker asks staff to do, in Admin terms. */
    public const NEEDS = [
        'code_missing' => 'Nhập mã sản phẩm (ID feed)',
        'code_too_long' => 'Rút mã sản phẩm còn tối đa 50 ký tự',
        'title' => 'Nhập tên sản phẩm',
        'slug' => 'Nhập đường dẫn',
        'quote_only' => 'Đang ở chế độ báo giá: chỉ tắt khi có giá bán công khai',
        'image_missing' => 'Thêm ảnh Shopping hoặc ảnh chính',
        'image_name' => 'Tải lại ảnh (tên tệp không hợp lệ)',
        'image_file' => 'Tải lại ảnh (không đọc được tệp; ảnh Shopping được ưu tiên)',
        'image_too_small' => 'Ảnh nhỏ hơn 500 px (ảnh Shopping được ưu tiên)',
        'price_missing' => 'Nhập giá bán',
        'sale_not_below_price' => 'Giá khuyến mãi phải thấp hơn giá bán',
        'brand' => 'Gán thương hiệu theo nhãn/tài liệu hãng',
        'availability' => 'Xác minh sẵn sàng giao ngay (tab Google Merchant)',
        'gtin_invalid' => 'GTIN đã nhập sai: kiểm tra lại nhãn hãng',
        'mpn_invalid' => 'MPN đã nhập sai: kiểm tra lại tài liệu hãng',
        'identifiers' => 'Nhập GTIN/MPN của hãng, hoặc xác nhận sản phẩm không có mã',
    ];

    /** @param list<array<string, mixed>> $rows */
    public static function write($handle, array $rows): void
    {
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, self::COLUMNS, ',', '"', '');
        foreach ($rows as $r) {
            fputcsv($handle, array_map(self::cell(...), [(string) $r['id'], $r['code'], $r['name'], $r['slug'], $r['legacy'] ?? 'in', $r['verified'] ?? 'in', $r['brand'],
                $r['availability'], self::text((string) $r['gtin']), $r['mpn'], $r['identifier_exists'], implode('|', $r['needs']),
                implode('; ', array_map(fn (string $n) => self::NEEDS[$n] ?? $n, $r['needs']))]), ',', '"', '');
        }
    }

    /**
     * Admin text is untrusted in a spreadsheet: a cell starting with = + - @ or a tab/CR is prefixed with an apostrophe
     * so Excel shows it as text instead of running it as a formula (OWASP CSV injection).
     */
    public static function cell(string $value): string
    {
        return $value !== '' && str_contains("=+-@\t\r", $value[0]) ? "'".$value : $value;
    }

    /** A GTIN keeps its leading zeros in Excel as apostrophe-prefixed text, which the workbook importer strips again. */
    private static function text(string $value): string
    {
        return $value === '' ? '' : "'".$value;
    }

    /** @param list<array<string, mixed>> $rows */
    public static function download(array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'wb');
            self::write($out, $rows);
            fclose($out);
        }, 'merchant-readiness-'.date('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
