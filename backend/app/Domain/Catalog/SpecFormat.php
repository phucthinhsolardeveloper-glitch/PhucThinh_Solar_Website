<?php

namespace App\Domain\Catalog;

/**
 * One way of writing the measured specs, so the same thing reads the same on every product and the compare
 * table can tell "same" from "different":
 *   time "4-6H", "4–6 tiếng", "6-8 hours"  → "4–6 giờ"
 *   length "3-5m", "8-10 m"                → "3–5 m", "8–10 m"
 *   area "250 m2", "200m²"                 → "250 m²", "200 m²"
 *   size "585 x 260 x 85mm", "610*250mm"   → "585 × 260 × 85 mm", "610 × 250 mm"
 * plus warranty "05 năm" → "5 năm", battery/panel "3.2v/100ah" → "3.2V/100Ah", colour temperature
 * "3000k - 6500k" → "3000–6500K", protection "ip 65" → "IP65", carton "4pcs/ctn" → "4 cái/thùng".
 * A product name writes its brand the way the brand is written in Admin ("JinDian" → "JINDIAN") when brand_name is given.
 * Spec names are never renamed: the product page hides or merges rows by name, so a rename would change what shows
 * (checked: every alias tried added or hid rows on some products).
 * Only these kinds of spec are touched, never names or model codes ("EPV230-4T2.2G" keeps its dash),
 * and free text around the figures stays as written.
 */
final class SpecFormat
{
    /** Product columns and the kind of figure they hold. */
    public const COLUMNS = [
        'charging_time' => 'time', 'lighting_time' => 'time', 'installation_height' => 'length', 'lighting_area' => 'area',
        'lamp_dimensions' => 'size', 'panel_dimensions' => 'size', 'carton_dimensions' => 'size',
        'warranty' => 'duration', 'battery_capacity' => 'electric', 'panel_power' => 'electric', 'color_temperature' => 'cct',
        'waterproof_rating' => 'ip', 'carton_quantity' => 'carton',
    ];

    /** What each kind looks like once written, shown as the empty field's hint in Admin. */
    public const EXAMPLES = [
        'time' => 'Ví dụ: 4–6 giờ', 'length' => 'Ví dụ: 3–5 m', 'area' => 'Ví dụ: 200 m²', 'size' => 'Ví dụ: 585 × 260 × 85 mm',
        'mass' => 'Ví dụ: 26 kg', 'duration' => 'Ví dụ: 2 năm', 'electric' => 'Ví dụ: 3.2V/30Ah LiFePO4', 'cct' => 'Ví dụ: 3000–6500K', 'ip' => 'Ví dụ: IP66', 'carton' => 'Ví dụ: 4 cái/thùng',
    ];

    private const NUMBER = '\d+(?:[.,]\d+)?';

    /** Kind of an extra spec row, from its label; null leaves the row alone. */
    public static function kindOfLabel(string $label): ?string
    {
        $label = mb_strtolower($label);

        return match (true) {
            str_contains($label, 'bảo hành') => 'duration',
            str_contains($label, 'nhiệt độ màu') => 'cct',
            str_contains($label, 'dung lượng pin'), str_contains($label, 'công suất tấm pin'), str_contains($label, 'điện áp') => 'electric',
            str_contains($label, 'cấp bảo vệ'), str_contains($label, 'chống nước') => 'ip',
            str_contains($label, 'số lượng') && str_contains($label, 'thùng') => 'carton',
            str_contains($label, 'thời gian') => 'time',
            str_contains($label, 'diện tích') => 'area',
            str_contains($label, 'kích thước') => 'size',
            str_contains($label, 'chiều cao'), str_contains($label, 'độ cao'), str_contains($label, 'khoảng cách'), str_contains($label, 'độ dài') => 'length',
            str_contains($label, 'khối lượng'), str_contains($label, 'trọng lượng') => 'mass',
            default => null,
        };
    }

    public static function apply(string $value, string $kind): string
    {
        $text = trim((string) preg_replace('/[ \t]+/u', ' ', $value));
        if ($text === '') {
            return $value;
        }
        $n = self::NUMBER;
        $other = self::other($text, $kind);
        if ($other !== null) {
            return $other;
        }
        if ($kind === 'size') {
            // Separators between figures (also before a height written "H240").
            $text = (string) preg_replace('/(?<=\d)\s*[x×*]\s*(?=H?\d)/iu', ' × ', $text);
            $text = (string) preg_replace('/(\d)\s*(mm|cm)\b/u', '$1 $2', $text);

            return $text;
        }
        // Ranges of plain figures: "4-6", "4 - 6", "4~6" → "4–6". A figure glued to letters (a model code) is skipped.
        $text = (string) preg_replace("/(?<![\\p{L}\\d.,])({$n})\\s*[-–—~]\\s*({$n})/u", '$1–$2', $text);

        return match ($kind) {
            'time' => (string) preg_replace('/(\d)\s*(?:giờ|tiếng|hours?|h)(?![\p{L}\d])/iu', '$1 giờ', $text),
            'area' => (string) preg_replace('/(\d)\s*m(?:2|²)(?![\p{L}\d])/u', '$1 m²', $text),
            'length' => (string) preg_replace('/(\d)\s*m(?![\p{L}\d²])/u', '$1 m', $text),
            'mass' => (string) preg_replace('/(\d)\s*(kg|g)(?![\p{L}\d])/iu', '$1 $2', $text),
            default => $text,
        };
    }

    /** Warranty, battery and panel, colour temperature, protection and carton count; null for the range-based kinds. */
    private static function other(string $text, string $kind): ?string
    {
        return match ($kind) {
            // "01 năm", "05 năm" → "1 năm", "5 năm".
            'duration' => (string) preg_replace('/(?<![\d.,])0+(?=\d+\s*(?:năm|tháng))/u', '', (string) preg_replace('/(\d)(năm|tháng)/u', '$1 $2', $text)),
            // "3.2v/100ah", "4400mah", "LifePO4", "Lithium-Ion" → "3.2V/100Ah", "4400mAh", "LiFePO4", "Li-ion".
            'electric' => (string) preg_replace(
                ['/(\d)\s*v(?![\p{L}\d])/iu', '/(\d)\s*mah(?![\p{L}\d])/iu', '/(\d)\s*ah(?![\p{L}\d])/iu', '/(\d)\s*w(?![\p{L}\d])/iu', '/life\s*po\s*[4₄]/iu', '/\b(?:lithium|li)[\s-]*ion\b/iu'],
                ['$1V', '$1mAh', '$1Ah', '$1W', 'LiFePO4', 'Li-ion'], $text),
            // "3000k - 6500k", "3000K-6500K" → "3000–6500K".
            'cct' => (string) preg_replace('/(\d+)K?\s*[-–~]\s*(\d+)K/u', '$1–$2K', (string) preg_replace('/(\d)\s*k(?![\p{L}\d])/iu', '$1K', $text)),
            'ip' => (string) preg_replace(['/\bip\s*(\d{2})\b/iu', '/\bik\s*(\d{2})\b/iu'], ['IP$1', 'IK$1'], $text),
            // "4pcs/ctn", "01pcs/cnt", "36" → "4 cái/thùng".
            'carton' => (string) preg_replace('/^0*(\d+)\s*(?:pcs?|cái|chiếc)?\s*(?:\/\s*(?:ctn|cnt|carton|thùng))?(?=\s|\(|$)/iu', '$1 cái/thùng', $text),
            default => null,
        };
    }

    /**
     * One cell of the bulk file: a spec column, or the "Mã | Tên | Giá trị" lines of extra specs (value after the last bar,
     * name before it). Anything else comes back as it was.
     */
    public static function cell(string $field, string $kind, string $value): string
    {
        if (isset(self::COLUMNS[$field])) {
            return self::apply($value, self::COLUMNS[$field]);
        }
        if ($kind !== 'specs') {
            return $value;
        }
        $lines = preg_split('/\R/u', $value) ?: [];
        foreach ($lines as $i => $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 2 && ($specKind = self::kindOfLabel(trim($parts[count($parts) - 2]))) !== null) {
                $parts[count($parts) - 1] = ' '.self::apply(trim($parts[count($parts) - 1]), $specKind);
                $lines[$i] = implode('|', $parts);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * The product's spec columns and extra spec rows rewritten; only keys that change are returned.
     *
     * @param  array<string, mixed>  $row  product attributes (additional_specs as an array or JSON text)
     * @return array<string, mixed>
     */
    public static function changes(array $row): array
    {
        $changes = [];
        foreach (self::COLUMNS as $column => $kind) {
            $value = $row[$column] ?? null;
            if (is_string($value) && $value !== '' && ($new = self::apply($value, $kind)) !== $value) {
                $changes[$column] = $new;
            }
        }
        if (is_string($row['name'] ?? null) && $row['name'] !== '') {
            // One line, single spaces; the brand as written in Admin, once ("Hober HOBER X" → "Hober X").
            $name = trim((string) preg_replace('/\s+/u', ' ', $row['name']));
            $brand = trim((string) ($row['brand_name'] ?? ''));
            if ($brand !== '') {
                $quoted = preg_quote($brand, '/');
                $name = (string) preg_replace('/(?<![\p{L}\d])'.$quoted.'(?![\p{L}\d])/iu', $brand, $name);
                $name = (string) preg_replace('/(?<![\p{L}\d])'.$quoted.'(?:\s+'.$quoted.')+(?![\p{L}\d])/u', $brand, $name);
            }
            if ($name !== $row['name']) {
                $changes['name'] = $name;
            }
        }
        $extra = $row['additional_specs'] ?? null;
        $extra = is_string($extra) ? json_decode($extra, true) : $extra;
        if (is_array($extra)) {
            $changed = false;
            foreach ($extra as $i => $spec) {
                $kind = is_array($spec) ? self::kindOfLabel((string) ($spec['label'] ?? '')) : null;
                if ($kind !== null && is_string($spec['value'] ?? null) && ($new = self::apply($spec['value'], $kind)) !== $spec['value']) {
                    $extra[$i]['value'] = $new;
                    $changed = true;
                }
            }
            if ($changed) {
                $changes['additional_specs'] = $extra;
            }
        }

        return $changes;
    }
}
