<?php

namespace App\Domain\Catalog\ProductDetail;

use App\Domain\Catalog\CategoryTree;
use App\Support\Text;

/**
 * Product specification rules in one place: profile choice, value lookup with aliases, package contents,
 * the spec table, highlight chips and decision metrics. Profile data lives in config/pdp.php.
 */
class ProductSpecs
{
    private const MISSING = '/^(?:không công bố|không áp dụng|không có thông tin|chưa cập nhật|đang cập nhật|chưa xác minh|chưa tìm được|n\/a|na|-)\b[^\S\r\n]*(?:exact-model)?\.?$/iu';

    /** @param array<string, mixed> $list the product's top category (slug, pdp_profile) */
    public function profileKey(array $product, array $list = []): string
    {
        if (! empty($product['pdp_profile'])) {
            return $this->normaliseProfile((string) $product['pdp_profile']);
        }
        if (! empty($product['category_profile'])) {
            return $this->normaliseProfile((string) $product['category_profile']);
        }
        if (! empty($list['pdp_profile'])) {
            return $this->normaliseProfile((string) $list['pdp_profile']);
        }

        return config('pdp.list_slug_profiles')[trim((string) ($list['slug'] ?? ''), '/ ')] ?? 'general';
    }

    /** The page type set on the product's own category or the nearest one above it below the top ("Kiểu trang sản phẩm"). */
    public static function categoryProfile(CategoryTree $tree, int $categoryId): string
    {
        $chain = $categoryId > 0 ? $tree->chain($categoryId) : [];
        foreach (array_reverse(array_slice($chain, 1)) as $node) {
            if (! empty($node['pdp_profile'])) {
                return (string) $node['pdp_profile'];
            }
        }

        return '';
    }

    /** Same answer as the product page, from what the Admin form holds (product override, then category chain). */
    public function profileForCategory(?string $productProfile, int $categoryId): string
    {
        $tree = app(CategoryTree::class);
        $root = $categoryId > 0 ? ($tree->chain($categoryId)[0] ?? null) : null;

        return $this->profileKey(['pdp_profile' => (string) $productProfile, 'category_profile' => self::categoryProfile($tree, $categoryId)],
            $root ? ['slug' => $root['slug'], 'pdp_profile' => $root['pdp_profile'] ?? ''] : []);
    }

    /** @return array<string, mixed> */
    public function profile(string $key): array
    {
        return config('pdp.profiles')[$this->normaliseProfile($key)];
    }

    public function valueIsMissing(mixed $value): bool
    {
        $normalised = mb_strtolower(trim(strip_tags(htmlspecialchars_decode((string) $value, ENT_QUOTES))));

        return $normalised === '' || (bool) preg_match(self::MISSING, $normalised);
    }

    /** @return list<array{key: string, label: string, value: string}> */
    public function additional(array $row): array
    {
        if (empty($row['additional_specs'])) {
            return [];
        }
        $decoded = json_decode(htmlspecialchars_decode((string) $row['additional_specs'], ENT_QUOTES), true);
        if (! is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $spec) {
            $label = ! empty($spec['label']) ? trim((string) $spec['label']) : '';
            $value = isset($spec['value']) ? trim((string) $spec['value']) : '';
            $key = ! empty($spec['key']) && preg_match('/^[a-z0-9_]+$/', (string) $spec['key']) ? (string) $spec['key'] : '';
            if ($label !== '' && $value !== '') {
                $out[] = ['key' => $key, 'label' => $label, 'value' => $value];
            }
        }

        return $out;
    }

    /** Spec keys of config/pdp.php that are stored under another column name. */
    private const COLUMNS = ['cong_suat' => 'power_watts'];

    public function value(array $row, string $key): string
    {
        $column = self::COLUMNS[$key] ?? $key;
        if (isset($row[$column]) && trim((string) $row[$column]) !== '' && ! ($key === 'cong_suat' && (float) $row[$column] <= 0)) {
            $value = trim((string) $row[$column]);
            if ($this->valueIsMissing($value)) {
                return '';
            }
            if ($key === 'package_contents') {
                return $this->packageContents($value);
            }
            if ($key === 'cong_suat' && is_numeric(str_replace(',', '.', $value))) {
                $value = sprintf('%g', (float) str_replace(',', '.', $value)).'W';
            }

            return $value;
        }
        $aliases = config('pdp.spec_aliases')[$key] ?? [];
        $additional = $this->additional($row);
        if ($aliases) {
            foreach ($additional as $spec) {
                $label = mb_strtolower(trim($spec['label']));
                foreach ($aliases as $alias) {
                    if ($label === $alias && ! $this->valueIsMissing($spec['value'])) {
                        return $spec['value'];
                    }
                }
            }
        }
        foreach ($additional as $spec) {
            if ($spec['key'] === $key && ! $this->valueIsMissing($spec['value'])) {
                return $key === 'package_contents' ? $this->packageContents($spec['value']) : $spec['value'];
            }
        }

        return '';
    }

    /** One way to write a package list, from text that may be glued together by Excel exports. */
    public function packageContents(mixed $value): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8')));
        if ($value === '') {
            return '';
        }
        $canonical = config('pdp.package_components');
        $separated = (string) preg_replace('/[ \t]*[+,;|·][ \t]*/u', '|', $value);
        $separated = (string) preg_replace('/[ \t]*\R+[ \t]*/u', '|', $separated);
        $separated = (string) preg_replace('/[ \t]+-[ \t]+/u', '|', $separated);
        $segments = [];
        foreach (preg_split('/\|+/', $separated, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $lower = mb_strtolower($part);
            if (isset($canonical[$lower])) {
                $segments[] = $canonical[$lower];

                continue;
            }
            $remaining = $part;
            while (trim($remaining) !== '') {
                $remaining = ltrim($remaining);
                $matched = false;
                foreach ($canonical as $needle => $label) {
                    $length = mb_strlen($needle);
                    if (mb_strtolower(mb_substr($remaining, 0, $length)) === $needle) {
                        $segments[] = $label;
                        $remaining = mb_substr($remaining, $length);
                        $matched = true;
                        break;
                    }
                }
                if (! $matched) {
                    $segments[] = trim($remaining);
                    break;
                }
            }
        }
        $result = [];
        foreach ($segments as $segment) {
            $segment = trim((string) preg_replace('/\s+/u', ' ', (string) $segment));
            if (preg_match('/^\(/u', $segment) && $result) {
                $result[count($result) - 1] .= ' '.$segment;

                continue;
            }
            if ($segment !== '' && ! in_array(mb_strtolower($segment), array_map('mb_strtolower', $result), true)) {
                $result[] = $segment;
            }
        }

        return implode(' · ', $result);
    }

    /** @return list<array{key: string, label: string, value: string}> */
    public function structured(array $row, string $profileKey): array
    {
        $profile = $this->profile($profileKey);
        $labels = ! empty($profile['fields']) ? $profile['fields'] : [
            'cong_suat' => 'Công suất', 'light_source' => 'Nguồn sáng', 'lamp_dimensions' => 'Kích thước đèn',
            'color_temperature' => 'Nhiệt độ màu (CCT)', 'light_color' => 'Màu ánh sáng', 'battery_capacity' => 'Dung lượng pin',
            'panel_power' => 'Công suất tấm pin', 'material' => 'Chất liệu', 'charging_time' => 'Thời gian sạc',
            'lighting_time' => 'Thời gian chiếu sáng', 'waterproof_rating' => 'Chỉ số chống nước', 'led_chip' => 'Loại chip LED',
            'lighting_area' => 'Diện tích chiếu sáng', 'installation_height' => 'Độ cao lắp đặt', 'panel_dimensions' => 'Kích thước tấm pin',
            'package_contents' => 'Bộ sản phẩm gồm', 'carton_quantity' => 'Số lượng / thùng', 'carton_dimensions' => 'Kích thước thùng', 'warranty' => 'Bảo hành',
        ];
        $specs = [];
        $used = [];
        foreach ($labels as $field => $label) {
            $value = $this->value($row, (string) $field);
            if ($value === '') {
                continue;
            }
            if ($field === 'cong_suat' && is_numeric(str_replace(',', '.', trim($value)))) {
                $value = sprintf('%g', (float) str_replace(',', '.', trim($value))).'W';
            }
            $specs[] = ['key' => (string) $field, 'label' => (string) $label, 'value' => $value];
            $used[$field] = true;
        }
        $additionalValid = true;
        if (! empty($row['additional_specs'])) {
            $additional = json_decode(htmlspecialchars_decode((string) $row['additional_specs'], ENT_QUOTES), true);
            $additionalValid = is_array($additional);
            if ($additionalValid) {
                foreach ($additional as $spec) {
                    $key = ! empty($spec['key']) ? trim((string) $spec['key']) : '';
                    $label = ! empty($spec['label']) ? trim((string) $spec['label']) : '';
                    $value = isset($spec['value']) ? trim((string) $spec['value']) : '';
                    if ($key !== '' && isset($used[$key])) {
                        continue;
                    }
                    if ($label !== '' && $value !== '') {
                        $specs[] = ['key' => $key, 'label' => $label, 'value' => $value];
                    }
                }
            }
        }
        $useLegacy = $specs === [] || (! $additionalValid && ! empty($row['specs_html']));

        return $useLegacy ? [] : $specs;
    }

    /**
     * First spec whose label contains a needle. The spec table tries needles in order;
     * the decision metrics walk the specs first.
     * The two orders give different answers when several labels match, so both are kept.
     */
    private function lookup(array $structured, string|array $needles, bool $needlesFirst = true): string
    {
        $needles = array_map(fn ($n) => mb_strtolower(trim((string) $n)), (array) $needles);
        $pairs = [];
        foreach ($needlesFirst ? $needles : $structured as $outer) {
            foreach ($needlesFirst ? $structured : $needles as $inner) {
                $pairs[] = $needlesFirst ? [$outer, $inner] : [$inner, $outer];
            }
        }
        foreach ($pairs as [$needle, $spec]) {
            $label = mb_strtolower(trim((string) ($spec['label'] ?? '')));
            if ($needle !== '' && ($label === $needle || mb_strpos($label, $needle) !== false)) {
                return trim(htmlspecialchars_decode((string) ($spec['value'] ?? ''), ENT_QUOTES));
            }
        }

        return '';
    }

    /** @return list<string> up to seven "label value" chips from the profile's metric order */
    public function highlights(array $row, string $profileKey): array
    {
        $profile = $this->profile($profileKey);
        $order = ! empty($profile['metrics']) ? $profile['metrics'] : ['cong_suat', 'light_source', 'battery_capacity', 'panel_power', 'waterproof_rating', 'warranty', 'lighting_time', 'led_chip', 'color_temperature', 'charging_time', 'material', 'installation_height'];
        $prefix = ! empty($profile['fields']) ? $profile['fields'] : [
            'cong_suat' => 'Công suất', 'light_source' => 'Quang thông', 'battery_capacity' => 'Pin', 'panel_power' => 'Tấm pin',
            'waterproof_rating' => 'Chống nước', 'warranty' => 'Bảo hành', 'lighting_time' => 'Chiếu sáng', 'led_chip' => 'Chip LED',
            'color_temperature' => 'Nhiệt độ màu', 'charging_time' => 'Sạc', 'material' => 'Chất liệu', 'installation_height' => 'Cao lắp đặt',
        ];
        $chips = [];
        foreach ($order as $field) {
            if (count($chips) >= 7) {
                break;
            }
            $raw = $this->value($row, (string) $field);
            if ($raw === '') {
                continue;
            }
            $value = trim((string) preg_replace('/\s+/u', ' ', strip_tags($raw)));
            if ($value === '') {
                continue;
            }
            if ($field === 'cong_suat' && is_numeric(str_replace(',', '.', $value))) {
                $value = sprintf('%g', (float) str_replace(',', '.', $value)).'W';
            }
            $label = $prefix[$field] ?? '';
            $chip = ($label === '' || mb_stripos($value, $label) !== false) ? $value : $label.' '.$value;
            if (mb_strlen($chip) > 46) {
                $chip = rtrim(mb_substr($chip, 0, 44)).'…';
            }
            $chips[] = $chip;
        }

        return $chips;
    }

    /** @return list<array{key: string, label: string, value: string}> the public spec table */
    public function table(array $row, string $profileKey): array
    {
        $structured = $this->structured($row, $profileKey);
        $fields = $this->profile($profileKey)['fields'] ?? [];
        if ($profileKey === 'lighting') {
            $waterproof = $this->value($row, 'waterproof_rating');
            $dimensions = trim($this->value($row, 'lamp_dimensions').($waterproof !== '' ? ' - '.$waterproof : ''), ' -');
            $rows = [
                ['cong_suat', 'Công suất LED thực', $this->value($row, 'cong_suat')],
                ['quang_thong', 'Quang thông', $this->lookup($structured, ['quang thông', 'lumen'])],
                ['battery_capacity', 'Dung lượng pin lưu trữ', $this->value($row, 'battery_capacity')],
                ['panel_power', 'Công suất tấm pin', $this->value($row, 'panel_power')],
                ['light_color', 'Màu ánh sáng', $this->value($row, 'light_color')],
                ['housing_color', 'Màu sắc thân đèn', $this->lookup($structured, 'màu sắc')],
                ['operating_mode', 'Chế độ hoạt động', $this->lookup($structured, 'chế độ')],
                ['lamp_dimensions', 'Kích thước & cấp bảo vệ', $dimensions],
                ['installation_height', 'Chiều cao lắp đặt', $this->value($row, 'installation_height')],
                ['impact_rating', 'Chuẩn chống va đập', $this->lookup($structured, 'chống va đập')],
                ['installation_spacing', 'Khoảng cách lắp đặt khuyến nghị', $this->lookup($structured, 'khoảng cách lắp đặt')],
                ['lighting_time', 'Thời gian chiếu sáng', $this->value($row, 'lighting_time')],
                ['charging_time', 'Thời gian sạc', $this->value($row, 'charging_time')],
                ['lighting_area', 'Diện tích chiếu sáng', $this->value($row, 'lighting_area')],
                ['warranty', 'Thời hạn bảo hành', $this->value($row, 'warranty')],
            ];
        } else {
            $rows = [];
            foreach ($fields as $key => $label) {
                $rows[] = [(string) $key, (string) $label, $this->value($row, (string) $key)];
            }
        }

        $optional = ['light_color', 'lighting_area', 'housing_color', 'operating_mode', 'impact_rating', 'installation_spacing', 'package_contents'];
        $specs = [];
        $usedLabels = [];
        foreach ($rows as [$rawKey, $rawLabel, $rawValue]) {
            $key = preg_match('/^[a-z0-9_]+$/', $rawKey) ? $rawKey : 'spec_'.count($specs);
            $label = Text::plain($rawLabel);
            $value = Text::plain($rawValue);
            $usedLabels[] = mb_strtolower(trim($label));
            if ($label !== '' && ($value !== '' || ! in_array($key, $optional, true))) {
                $specs[] = ['key' => $key, 'label' => $label, 'value' => $value];
            }
        }

        $denylist = ['model', 'ghi chú', 'thông tin', 'mã/model nguồn', 'model nguồn', 'tên/phiên bản bảng giá', 'chứng nhận', 'kiểm nghiệm', 'tiêu chuẩn', 'hiệu suất châu âu', 'hiệu suất theo datasheet'];
        $internalPatterns = ['/\bghi chú\b/u', '/\bsuy luận\b/u', '/\bxác minh\b/u', '/\bđối chiếu\b/u', '/\brevision\b/u', '/\bbatch\d*/u', '/\bfamily\b/u', '/\bcatalog(?:ue)?\b/u',
            '/\btrích nguồn\b/u', '/\bnguồn tham khảo\b/u', '/\bnguồn exact\b/u', '/\btham khảo\b/u', '/\bcăn cứ\b/u', '/\bnguồn (?:đúng|xác nhận|hiện tại|phổ biến|độc lập|hãng)\b/u',
            '/\btrạng thái nguồn\b/u', '/\bmức xác minh\b/u', '/\bkhuyến nghị\b/u', '/\bgiữ cấu hình\b/u', '/\blưu ý\b/u', '/\btrạng thái\b/u', '/\bxử lý\b/u', '/\bphân loại\b/u'];
        if ($profileKey === 'lighting') {
            $usedLabels[] = 'lumen';
            $usedLabels[] = 'độ cao lắp đặt';
        }
        foreach ($this->additional($row) as $extra) {
            $label = Text::plain($extra['label']);
            $value = Text::plain($extra['value']);
            $lower = mb_strtolower($label);
            if ($label === '' || $value === '' || in_array($lower, $denylist, true)) {
                continue;
            }
            $internal = false;
            foreach ($internalPatterns as $pattern) {
                if (preg_match($pattern, $lower)) {
                    $internal = true;
                    break;
                }
            }
            if ($lower !== 'nguồn sáng' && preg_match('/\b(?:nguồn|source)\b/u', $lower)) {
                $internal = true;
            }
            if (preg_match('/https?:\/\//i', $value) || preg_match('/\b(?:không công bố|chưa xác minh|chưa tìm được|không tự suy|chưa có nguồn|theo nguồn|theo revision|theo family|catalog(?:ue)?)\b/iu', $value)) {
                $internal = true;
            }
            if ($internal) {
                continue;
            }
            foreach ($usedLabels as $usedLabel) {
                if ($usedLabel === $lower || mb_strpos($usedLabel, $lower) !== false || mb_strpos($lower, $usedLabel) !== false) {
                    continue 2;
                }
            }
            $key = $extra['key'] !== '' ? $extra['key'] : 'extra_'.count($specs);
            $specs[] = ['key' => $key, 'label' => $label, 'value' => $value];
            $usedLabels[] = $lower;
        }

        return $specs;
    }

    /** @return list<array<string, string>> */
    public function decisionMetrics(array $row, string $profileKey): array
    {
        $profile = $this->profile($profileKey);
        $structured = $this->structured($row, $profileKey);
        $metrics = [];
        if ($profileKey === 'lighting') {
            $height = $this->value($row, 'installation_height');
            if ($height === '') {
                $height = $this->lookup($structured, ['chiều cao lắp đặt', 'độ cao lắp đặt'], false);
            }
            if ($height === '') {
                $technical = $this->lookup($structured, ['mô tả kỹ thuật', 'cấu tạo'], false);
                if ($technical !== '' && preg_match('/tr[ụu]\s*(?:đôi|đơn)?\s*cao\s+(\d+(?:[.,]\d+)?)\s*(?:mét|m)\b/ui', $technical, $match)) {
                    $height = str_replace('.', ',', $match[1]).'m (theo mô tả kỹ thuật)';
                }
            }
            $images = config('pdp_metrics.lighting_metric_images');
            foreach ([
                ['quang_thong', 'Quang thông', $this->lookup($structured, ['quang thông', 'lumen'], false), 'lm', 'sun'],
                ['battery_capacity', 'Pin lưu trữ', $this->value($row, 'battery_capacity'), 'Wh', 'battery'],
                ['installation_height', 'Chiều cao lắp', $height, 'm', 'height'],
                ['warranty', 'Bảo hành', $this->value($row, 'warranty'), 'tháng', 'shield'],
            ] as [$key, $label, $value, $unit, $icon]) {
                $metrics[] = ['key' => $key, 'label' => $label, 'value' => Text::plain($value), 'unit' => $unit, 'icon' => $icon, 'iconImage' => $images[$key], 'iconDir' => 'pdp-lighting'];
            }

            return $metrics;
        }
        $images = config('pdp_metrics.metric_images')[$profileKey] ?? [];
        foreach ((array) ($profile['metrics'] ?? []) as $key) {
            if (! preg_match('/^[a-z0-9_]+$/', (string) $key)) {
                continue;
            }
            $iconImage = $images[$key] ?? '';
            $metrics[] = [
                'key' => (string) $key,
                'label' => Text::plain($profile['fields'][$key] ?? $key),
                'value' => Text::plain($this->value($row, (string) $key)),
                'unit' => config('pdp_metrics.metric_units')[$key] ?? '',
                'icon' => config('pdp_metrics.metric_icons')[$key] ?? 'bolt',
                'iconImage' => $iconImage,
                'iconDir' => $iconImage !== '' && in_array($key, config('pdp_metrics.metric_extra_dir_keys'), true) ? 'pdp-extra' : 'pdp',
            ];
        }

        return $metrics;
    }

    private function normaliseProfile(string $value): string
    {
        $value = trim($value);

        return array_key_exists($value, config('pdp.profiles')) ? $value : 'general';
    }
}
