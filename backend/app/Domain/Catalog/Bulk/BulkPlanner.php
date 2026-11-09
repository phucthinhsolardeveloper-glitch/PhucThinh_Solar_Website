<?php

namespace App\Domain\Catalog\Bulk;

use App\Filament\Support\Options;
use App\Models\Product;
use App\Support\Slugs;
use Illuminate\Support\Facades\Storage;

/**
 * Turns spreadsheet rows into a plan: what each row creates, changes or hides, every error, and
 * every decision staff must take. Nothing is written here; the same code runs at preview and again,
 * strictly, at apply.
 */
final class BulkPlanner
{
    /** @var array<string, array<string, mixed>> */
    private array $columns;

    /** @var array<string, array<string, array{row: int, id: int, unchanged: bool}>> */
    private array $seen = ['code' => [], 'slug' => []];

    /** @var array<int, int> */
    private array $seenIds = [];

    private array $plan = [];

    /** @param array{overwrite?: array<string, bool>, brands?: array<string, string>, products?: array<string, string>} $choices */
    public function __construct(private readonly BulkCatalog $catalog, private readonly array $choices = [], private readonly bool $strict = false)
    {
        $this->columns = BulkColumns::all();
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<int, array<string, string>>  $baseline
     * @return array<string, mixed>
     */
    public function plan(array $rows, array $baseline = []): array
    {
        $this->plan = ['rows' => [], 'errors' => 0, 'warnings' => 0, 'conflicts' => 0, 'new_brands' => [], 'slug_matches' => [],
            'counts' => ['create' => 0, 'update' => 0, 'archive' => 0, 'skip' => 0, 'unchanged' => 0, 'draft_create' => 0, 'hide_update' => 0, 'show_update' => 0]];
        foreach ($rows as $raw) {
            $item = $this->row($raw, $baseline);
            $this->plan['errors'] += count($item['errors']);
            $this->plan['warnings'] += count($item['warnings']);
            $this->plan['conflicts'] += count($item['conflicts']);
            $this->plan['rows'][] = $item;
        }

        return $this->plan;
    }

    private function row(array $raw, array $baseline): array
    {
        $item = ['row' => (int) $raw['_row'], 'action' => BulkText::action($raw['action'] ?? ''), 'id' => 0, 'code' => trim($raw['code'] ?? ''), 'name' => trim($raw['name'] ?? ''),
            'columns' => [], 'seo' => [], 'gallery' => null, 'sections' => null, 'pending_brand' => '', 'errors' => [], 'warnings' => [], 'conflicts' => [], 'changes' => []];
        $item['action'] = $item['action'] === '' ? 'auto' : $item['action'];
        if (! in_array($item['action'], ['auto', 'create', 'update', 'upsert', 'archive', 'skip'], true)) {
            $item['errors'][] = 'Thao tác không hợp lệ.';
        }
        if ($item['action'] === 'skip') {
            $this->plan['counts']['skip']++;

            return $item;
        }
        $existing = $this->identify($raw, $item);
        if ($item['action'] === 'skip') {
            return $item;
        }
        if ($item['action'] === 'auto') {
            $item['action'] = $existing ? 'update' : 'create';
        }
        if (in_array($item['action'], ['update', 'archive'], true) && ! $existing) {
            $item['errors'][] = 'Không tìm thấy sản phẩm với ID này.';
        }
        if ($item['action'] === 'create' && $item['code'] !== '' && $this->codeOwners($item['code']) !== []) {
            $item['errors'][] = 'Mã sản phẩm đã có trên hệ thống.';
        }
        if ($existing && ! isset($baseline[$existing->id]) && is_numeric($raw['expected_updated_at'] ?? '') && (int) $raw['expected_updated_at'] !== (int) $existing->updated_at?->timestamp) {
            $item['errors'][] = 'Sản phẩm đã thay đổi sau khi xuất tệp; hãy xuất lại trước khi cập nhật.';
        }
        if ($existing) {
            $item['id'] = (int) $existing->id;
            $item['name'] = $item['name'] !== '' ? $item['name'] : (string) $existing->name;
        }
        if ($item['action'] === 'archive') {
            if ($existing) {
                $item['columns'] = ['is_visible' => false, 'is_bestseller' => false];
                $this->plan['counts']['archive']++;
            }

            return $item;
        }
        $isCreate = $item['action'] === 'create' || ($item['action'] === 'upsert' && ! $existing);
        $item['action'] = $isCreate ? 'create' : 'update';
        $this->flagsFromStatus($raw, $item);
        $categoryId = $this->resolveCategory($raw, $item);
        $brandId = $this->resolveBrand($raw, $item);
        $current = $existing ? $this->catalog->values[$existing->id] : [];
        $base = $existing ? ($baseline[$existing->id] ?? null) : null;
        foreach ($this->columns as $key => $column) {
            if (in_array($column['kind'], ['control', 'ignored'], true) || ! array_key_exists($key, $raw)) {
                continue;
            }
            $this->cell($key, $column, trim((string) $raw[$key]), $item, $existing, $current, $base, $categoryId, $brandId);
        }
        $this->finish($raw, $item, $existing, $isCreate);

        return $item;
    }

    /** Finds the product a row talks about, by ID, then a code only one product has, then its slug. */
    private function identify(array &$raw, array &$item): ?Product
    {
        $rawId = trim($raw['id'] ?? '');
        $id = $rawId !== '' ? filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        if ($rawId !== '' && $id === false) {
            $item['errors'][] = 'ID không hợp lệ.';
        }
        $item['id'] = (int) $id;
        if ($item['id'] > 0) {
            if (isset($this->seenIds[$item['id']])) {
                $item['errors'][] = 'ID bị lặp trong tệp (đã có ở dòng '.$this->seenIds[$item['id']].').';
            }
            $this->seenIds[$item['id']] = $item['row'];
        }
        $byId = $item['id'] > 0 ? $this->catalog->products->get($item['id']) : null;
        $owners = $this->codeOwners($item['code']);
        $byCode = count($owners) === 1 ? $this->catalog->products->get($owners[0]) : null;
        $codeKept = $byId && self::lower($item['code']) === self::lower((string) $byId->code);
        if ($item['code'] !== '') {
            $previous = $this->seen['code'][self::lower($item['code'])] ?? null;
            if ($previous && ! ($codeKept && $previous['unchanged'] && $previous['id'] !== $item['id'])) {
                $item['errors'][] = 'Mã sản phẩm bị lặp trong tệp (đã có ở dòng '.$previous['row'].').';
            }
            $this->seen['code'][self::lower($item['code'])] = ['row' => $item['row'], 'id' => $item['id'], 'unchanged' => (bool) $codeKept];
        }
        if ($item['action'] === 'create' && $item['id'] > 0) {
            $item['errors'][] = 'Dòng tạo mới không được nhập ID.';
        }
        if (in_array($item['action'], ['update', 'archive'], true) && $item['id'] <= 0) {
            $item['errors'][] = 'Dòng cập nhật hoặc ẩn bắt buộc có ID để tránh sửa nhầm.';
        }
        if (! $byId && count($owners) > 1) {
            $item['errors'][] = 'Mã sản phẩm đang thuộc nhiều sản phẩm; hãy dùng đúng ID.';
        }
        if ($byId && $byCode && $byId->id !== $byCode->id) {
            $item['errors'][] = 'ID và mã sản phẩm đang chỉ tới hai sản phẩm khác nhau.';
        }
        $existing = $byId ?: $byCode;
        if ($existing && $item['code'] !== '' && self::lower($item['code']) !== self::lower((string) $existing->code) && $owners !== []) {
            $item['errors'][] = 'Mã sản phẩm đang thuộc về một sản phẩm khác.';
        }

        return $existing ?: $this->matchBySlug($raw, $item);
    }

    private function matchBySlug(array &$raw, array &$item): ?Product
    {
        $slug = Slugs::from(($raw['slug'] ?? '') !== '' ? $raw['slug'] : ($raw['name'] ?? ''));
        $owners = $slug !== '' ? ($this->catalog->slugOwners[$slug] ?? []) : [];
        if ($owners === [] || ! in_array($item['action'], ['auto', 'create', 'upsert'], true)) {
            return null;
        }
        $free = fn () => Slugs::free($slug, fn (string $candidate) => isset($this->catalog->slugOwners[$candidate]) || isset($this->seen['slug'][$candidate]));
        if (count($owners) !== 1 || $owners[0]['table'] !== 'products') {
            $raw['slug'] = $free();
            $item['warnings'][] = 'Đường dẫn “'.$slug.'” đã có trang khác dùng; sẽ dùng “'.$raw['slug'].'”.';

            return null;
        }
        $match = $this->catalog->products->get($owners[0]['id']);
        if (! $match) {
            return null;
        }
        $key = hash('sha256', $item['row'].'|'.$slug.'|'.$match->id);
        $this->plan['slug_matches'][$key] = ['key' => $key, 'row' => $item['row'], 'slug' => $slug, 'code' => $item['code'], 'name' => $item['name'],
            'existing_id' => (int) $match->id, 'existing_code' => (string) $match->code, 'existing_name' => (string) $match->name];
        $choice = $this->choices['products'][$key] ?? '';
        if ($choice === 'update:'.$match->id) {
            $item['id'] = (int) $match->id;
            $item['action'] = 'update';

            return $match;
        }
        if ($choice === 'create') {
            $raw['slug'] = $free();
        } elseif ($choice === 'skip') {
            $item['action'] = 'skip';
            $this->plan['counts']['skip']++;
        } elseif ($this->strict) {
            $item['errors'][] = 'Chưa chọn cách xử lý sản phẩm trùng đường dẫn “'.$slug.'”.';
        } else {
            $item['pending_match'] = true;
        }

        return null;
    }

    /** The old "Hiển thị, Bán chạy" text column, used only where the 1/0 columns are empty. */
    private function flagsFromStatus(array &$raw, array &$item): void
    {
        $text = trim($raw['status'] ?? '');
        if ($text === '') {
            return;
        }
        $parts = array_map(fn ($part) => mb_strtolower(trim($part), 'UTF-8'), explode(',', $text));
        $has = fn (array $names) => array_intersect($names, $parts) !== [];
        foreach ($parts as $part) {
            if (! in_array($part, ['hiển thị', 'hienthi', 'bán chạy', 'banchay', 'baogia', 'báo giá', 'tạm ẩn', 'khuyenmai', 'khuyến mãi', ''], true)) {
                $item['errors'][] = 'Trạng thái không hỗ trợ: '.$part;
            }
        }
        $raw['is_visible'] = ($raw['is_visible'] ?? '') !== '' ? $raw['is_visible'] : ($has(['hiển thị', 'hienthi']) ? '1' : '0');
        $raw['is_bestseller'] = ($raw['is_bestseller'] ?? '') !== '' ? $raw['is_bestseller'] : ($has(['bán chạy', 'banchay']) ? '1' : '0');
        if ($has(['baogia', 'báo giá']) && ($raw['quote_only'] ?? '') === '') {
            $raw['quote_only'] = '1';
        }
    }

    /** @return int|null null = the file says nothing about the category */
    private function resolveCategory(array &$raw, array &$item): ?int
    {
        $named = trim($raw['category'] ?? '');
        $parentNamed = trim($raw['category_parent'] ?? '');
        $id = (int) ($raw['category_id'] ?? 0);
        $parentId = (int) ($raw['category_parent_id'] ?? 0);
        $lookup = function (string $name) use (&$item): int {
            $found = $this->catalog->findCategory($name);
            if ($found === -1) {
                $item['errors'][] = 'Danh mục “'.$name.'” trùng tên; hãy ghi đủ “Cha › Con”.';
            } elseif ($found === 0) {
                $item['errors'][] = 'Danh mục “'.$name.'” không tồn tại.';
            }

            return max(0, $found);
        };
        if ($named === BulkColumns::CLEAR) {
            $item['errors'][] = 'Không được xóa danh mục của sản phẩm.';

            return null;
        }
        $parent = $parentId > 0 ? $parentId : ($parentNamed !== '' ? $lookup($parentNamed) : 0);
        $child = $id > 0 ? $id : ($named !== '' ? $lookup($named) : 0);
        foreach ([$id, $parentId] as $given) {
            if ($given > 0 && ! isset($this->catalog->categoryPaths[$given])) {
                $item['errors'][] = 'ID danh mục '.$given.' không tồn tại.';
            }
        }
        if ($child > 0 && $parent > 0 && ! $this->isUnder($child, $parent)) {
            $item['errors'][] = 'Danh mục con không thuộc danh mục cấp 1 đã chọn.';
        }
        $resolved = $child ?: $parent;
        if ($resolved > 0 && isset($this->catalog->categoryPaths[$resolved])) {
            $raw['category'] = $this->catalog->categoryPaths[$resolved];

            return $resolved;
        }

        return null;
    }

    private function isUnder(int $child, int $parent): bool
    {
        for ($node = $child; $node !== null; $node = $this->catalog->categoryParents[$node] ?? null) {
            if ($node === $parent) {
                return true;
            }
        }

        return false;
    }

    /** @return int|null null = nothing to set */
    private function resolveBrand(array &$raw, array &$item): ?int
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $raw['brand'] ?? ''));
        $id = (int) ($raw['brand_id'] ?? 0);
        if ($id > 0) {
            if (! isset($this->catalog->brands[$id])) {
                $item['errors'][] = 'ID thương hiệu không tồn tại.';

                return null;
            }
            $raw['brand'] = $this->catalog->brands[$id];

            return $id;
        }
        if ($name === '' || $name === BulkColumns::CLEAR) {
            return null;
        }
        $found = $this->catalog->findBrand($name);
        if ($found > 0) {
            $raw['brand'] = $this->catalog->brands[$found];

            return $found;
        }
        if ($found === -1) {
            $item['errors'][] = 'Thương hiệu “'.$name.'” bị trùng tên trên hệ thống.';

            return null;
        }
        $key = hash('sha256', BulkCatalog::key($name));
        $this->plan['new_brands'][$key] ??= ['key' => $key, 'name' => $name, 'rows' => []];
        $this->plan['new_brands'][$key]['rows'][] = $item['row'];
        $choice = $this->choices['brands'][$key] ?? '';
        unset($raw['brand']);
        if ($choice === 'create') {
            $item['pending_brand'] = $key;
            $item['columns']['brand_id'] = null;
            $item['warnings'][] = 'Sẽ tạo thương hiệu mới “'.$name.'”.';
        } elseif ($choice === 'blank') {
            $item['warnings'][] = 'Thương hiệu “'.$name.'” được bỏ trống theo lựa chọn.';
        } elseif (preg_match('/^existing:(\d+)$/', $choice, $match) && isset($this->catalog->brands[(int) $match[1]])) {
            $raw['brand'] = $this->catalog->brands[(int) $match[1]];

            return (int) $match[1];
        } elseif ($this->strict) {
            $item['errors'][] = 'Chưa chọn cách xử lý thương hiệu mới “'.$name.'”.';
        }

        return null;
    }

    private function cell(string $key, array $column, string $value, array &$item, ?Product $existing, array $current, ?array $base, ?int $categoryId, ?int $brandId): void
    {
        if ($value === '') {
            return;
        }
        $kind = $column['kind'];
        $clear = $value === BulkColumns::CLEAR;
        if ($clear && (in_array($key, ['code', 'name', 'slug', 'category'], true) || $kind === 'flag')) {
            $item['errors'][] = $column['label'].' không dùng được '.BulkColumns::CLEAR.'.';

            return;
        }
        $incoming = $clear ? '' : self::canon($kind, $value);
        if ($existing) {
            $now = self::canon($kind, $current[$key] ?? '');
            if ($incoming === $now || ($clear && in_array($now, ['', '0'], true))) {
                return;
            }
            if ($base !== null && array_key_exists($key, $base)) {
                $was = self::canon($kind, $base[$key]);
                if ($now !== $was) {
                    if ($incoming === $was) {
                        return;
                    }
                    $conflictKey = $item['row'].':'.$key;
                    $item['conflicts'][] = ['key' => $conflictKey, 'label' => $column['label'], 'base' => self::brief($was), 'current' => self::brief($now), 'incoming' => self::brief($incoming)];
                    if (empty($this->choices['overwrite'][$conflictKey])) {
                        return;
                    }
                }
            }
        }
        if (isset($column['max']) && mb_strlen($value, 'UTF-8') > $column['max'] && ! $clear) {
            $item['errors'][] = $column['label'].' vượt quá '.$column['max'].' ký tự.';

            return;
        }
        $errors = count($item['errors']);
        $this->store($key, $column, $clear ? null : $value, $item, $existing, $categoryId, $brandId);
        if (count($item['errors']) === $errors) {
            $item['changes'][$key] = ['label' => $column['label'], 'current' => self::brief($existing ? self::canon($kind, $current[$key] ?? '') : ''), 'incoming' => self::brief($incoming)];
        }
    }

    /** Validates one changed value and files it under the column it goes to; null clears. */
    private function store(string $key, array $column, ?string $value, array &$item, ?Product $existing, ?int $categoryId, ?int $brandId): void
    {
        $label = $column['label'];
        $field = $column['field'] ?? $key;
        $set = function (mixed $stored) use (&$item, $field): void {
            $item['columns'][$field] = $stored;
        };
        switch ($column['kind']) {
            case 'slug':
                $set(Slugs::from($value));
                break;
            case 'category':
                if ($categoryId) {
                    $set($categoryId);
                }
                break;
            case 'brand':
                if ($value === null || $brandId) {
                    $set($brandId);
                }
                break;
            case 'profile':
                if ($value !== null && ! array_key_exists($value, Options::pdpProfiles())) {
                    $item['errors'][] = $label.' không hợp lệ.';
                } else {
                    $set($value);
                }
                break;
            case 'decimal':
                $decimal = $value === null ? null : BulkText::decimal($value);
                if ($value !== null && $decimal === null) {
                    $item['errors'][] = $label.' phải nằm trong khoảng 0–99999,9.';
                } else {
                    $set($decimal);
                }
                break;
            case 'money':
                $money = $value === null ? 0 : BulkText::money($value);
                if ($money === null) {
                    $item['errors'][] = $label.' phải là số không âm.';
                } else {
                    $set($money);
                }
                break;
            case 'integer':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
                    $item['errors'][] = $label.' phải là số nguyên không âm.';
                } else {
                    $set((int) $value);
                }
                break;
            case 'flag':
                $flag = BulkText::flag((string) $value);
                if ($flag === null) {
                    $item['errors'][] = $label.' chỉ nhận 1 hoặc 0.';
                } else {
                    $set($flag);
                }
                break;
            case 'html':
                if ($existing && preg_match('/<(img|table|iframe)\b/i', (string) $existing->getRawOriginal($field))) {
                    $item['warnings'][] = $label.' đang có ảnh, bảng hoặc video; các phần đó sẽ mất khi thay bằng nội dung trong tệp.';
                }
                $set($value === null ? null : BulkText::textToHtml($value));
                break;
            case 'pairs':
            case 'lines':
                $parsed = BulkText::parseLines((string) $value, $column['kind'] === 'pairs', (int) $column['limit']);
                foreach ($parsed['errors'] as $error) {
                    $item['errors'][] = $label.': '.$error.'.';
                }
                $set($parsed['items'] !== [] ? json_encode($parsed['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null);
                break;
            case 'specs':
                $parsed = BulkText::parseSpecs((string) $value, $existing !== null);
                foreach ($parsed['errors'] as $error) {
                    $item['errors'][] = $label.': '.$error.'.';
                }
                $set($parsed['specs'] !== [] ? json_encode($parsed['specs'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null);
                break;
            case 'youtube':
                if ($value !== null && (! filter_var($value, FILTER_VALIDATE_URL) || ! in_array(strtolower((string) parse_url($value, PHP_URL_HOST)), ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true))) {
                    $item['errors'][] = 'Video phải là link YouTube hợp lệ.';
                } else {
                    $set($value);
                }
                break;
            case 'image':
            case 'feed_image':
                if ($value === null || $this->checkImage($value, $label, $item, $column['kind'] === 'feed_image')) {
                    $set($value);
                }
                break;
            case 'gallery':
                $names = $value === null ? [] : array_values(array_unique(self::names($value)));
                $known = $existing ? $existing->images->pluck('path')->all() : [];
                foreach ($names as $name) {
                    if (! in_array($name, $known, true)) {
                        $this->checkImage($name, $label, $item, false);
                    }
                }
                $item['gallery'] = $names;
                break;
            case 'sections':
                if ($existing && str_contains((string) $existing->contentSection?->getRawOriginal('blocks'), '"cards"')) {
                    $item['warnings'][] = 'Khối “Thẻ lựa chọn” không biểu diễn được trong Excel; được giữ nguyên ở cuối phần mô tả.';
                }
                $item['sections'] = $value ?? BulkColumns::CLEAR;
                break;
            case 'seo':
                $item['seo'][$field] = $value;
                break;
            default:
                $set($value);
        }
    }

    private function checkImage(string $name, string $label, array &$item, bool $forFeed): bool
    {
        $hint = fn () => ($s = $this->catalog->suggestFile($name)) !== null ? ' Có phải “'.$s.'”?' : ' Hãy tải ảnh lên trước.';
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp)$/i', $name)) {
            $item['errors'][] = $label.': “'.$name.'” phải là tên tệp ảnh, không đường dẫn hay ký tự đặc biệt.'.$hint();

            return false;
        }
        if (! $this->catalog->fileExists($name)) {
            $item['errors'][] = $label.': chưa có tệp “'.$name.'”.'.$hint();

            return false;
        }
        if ($forFeed) {
            $size = @getimagesize(Storage::disk('upload_product')->path($name));
            if (! $size || $size[0] < 500 || $size[1] < 500) {
                $item['errors'][] = $label.': “'.$name.'” phải từ 500×500 px trở lên.';

                return false;
            }
        }

        return true;
    }

    /** Checks that need the whole row: required fields, slug, category, prices, counters. */
    private function finish(array $raw, array &$item, ?Product $existing, bool $isCreate): void
    {
        $name = (string) ($item['columns']['name'] ?? $existing->name ?? '');
        $item['name'] = $name;
        if ($isCreate) {
            if (trim($name) === '') {
                $item['errors'][] = 'Dòng tạo mới bắt buộc có Tên sản phẩm.';
            }
            if ($item['code'] === '') {
                $item['errors'][] = 'Dòng tạo mới bắt buộc có Mã sản phẩm.';
            }
            if (empty($item['columns']['category_id'])) {
                $item['errors'][] = 'Dòng tạo mới bắt buộc có Danh mục.';
            }
            if (! isset($item['columns']['slug']) && $name !== '') {
                $item['columns']['slug'] = Slugs::from($name);
            }
        }
        $this->checkSlug($item, $existing);
        $regular = (float) ($item['columns']['regular_price'] ?? $existing->regular_price ?? 0);
        $sale = (float) ($item['columns']['sale_price'] ?? $existing->sale_price ?? 0);
        if ($regular > 0 && $sale > $regular) {
            $item['errors'][] = 'Giá khuyến mãi không được lớn hơn giá gốc.';
        }
        $nothing = $item['columns'] === [] && $item['seo'] === [] && $item['gallery'] === null && $item['sections'] === null;
        if ($isCreate) {
            if (($item['columns']['is_visible'] ?? true) === false) {
                $this->plan['counts']['draft_create']++;
            }
        } elseif ($existing && array_key_exists('is_visible', $item['columns'])) {
            $this->plan['counts'][$item['columns']['is_visible'] ? 'show_update' : 'hide_update']++;
        }
        $this->plan['counts'][! $isCreate && $nothing ? 'unchanged' : $item['action']]++;
    }

    private function checkSlug(array &$item, ?Product $existing): void
    {
        $slug = strtolower(trim((string) ($item['columns']['slug'] ?? $existing->slug ?? '')));
        if ($slug === '') {
            $item['errors'][] = 'Đường dẫn không được trống.';

            return;
        }
        $kept = $existing && strtolower((string) $existing->slug) === $slug;
        $previous = $this->seen['slug'][$slug] ?? null;
        $sameRow = $previous && $item['id'] > 0 && $previous['id'] === $item['id']; // already reported as a repeated ID
        if ($previous && ! $sameRow && ! ($kept && $previous['unchanged'] && $previous['id'] !== $item['id'])) {
            $item['errors'][] = 'Đường dẫn bị lặp trong tệp (đã có ở dòng '.$previous['row'].').';
        }
        $this->seen['slug'][$slug] = ['row' => $item['row'], 'id' => $item['id'], 'unchanged' => $kept];
        if (! $kept && empty($item['pending_match']) && isset($this->catalog->slugOwners[$slug])) {
            $item['errors'][] = 'Đường dẫn “'.$slug.'” đã có trang khác dùng.';
        }
    }

    /** The form two values are compared in, so 1.000 equals 1000 and spacing does not count as a change. */
    public static function canon(string $kind, string $value): string
    {
        $value = trim(str_replace("\r", '', $value));

        return match ($kind) {
            'money' => (string) (BulkText::money($value) ?? $value),
            'decimal' => $value === '' ? '' : (BulkText::decimal($value) ?? $value),
            'flag' => match (BulkText::flag($value)) {
                true => '1', false => '0', default => $value,
            },
            'integer' => ctype_digit($value) ? (string) (int) $value : $value,
            'html' => BulkText::hasTags($value) ? BulkText::htmlToText($value) : BulkText::cleanLines($value),
            'multiline' => BulkText::cleanLines($value),
            'pairs', 'lines', 'specs' => (string) preg_replace('/[\t ]*\|[\t ]*/u', ' | ', BulkText::cleanLines($value)),
            'gallery' => implode("\n", self::names($value)),
            default => $value,
        };
    }

    /** @return list<string> */
    private static function names(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n;]+/', $value) ?: []), fn ($name) => $name !== ''));
    }

    /** @return list<int> */
    private function codeOwners(string $code): array
    {
        return $this->catalog->codeOwners[self::lower($code)] ?? [];
    }

    private static function lower(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }

    private static function brief(string $value): string
    {
        return mb_strimwidth($value, 0, 800, '…', 'UTF-8');
    }
}
