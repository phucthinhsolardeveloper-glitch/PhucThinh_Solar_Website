<?php

namespace App\Domain\Content;

use App\Filament\Support\Options;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ContentSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Brand and category descriptions as a JSON file, to copy between sites or keep aside. Files exported
 * by the previous site ("brand", "cat", "list" scopes) import too. Owners are matched by name.
 */
final class SectionTransfer
{
    private const OWNERS = ['brand' => Brand::class, 'category' => Category::class, 'cat' => Category::class, 'list' => Category::class];

    private const TYPES = ['heading', 'text', 'list', 'image', 'media', 'faq', 'cards'];

    public function export(): StreamedResponse
    {
        $paths = Options::categoryPaths();
        $brands = DB::table('brands')->pluck('name', 'id');
        $items = [];
        foreach (DB::table('content_sections')->whereIn('owner_type', [Brand::class, Category::class])->orderBy('owner_type')->orderBy('owner_id')->get() as $row) {
            $blocks = self::clean(json_decode((string) $row->blocks, true));
            $isBrand = $row->owner_type === Brand::class;
            $name = (string) ($isBrand ? ($brands[$row->owner_id] ?? '') : ($paths[$row->owner_id] ?? ''));
            if ($name !== '' && ($blocks !== [] || $row->mode !== 'own')) {
                $items[] = ['scope' => $isBrand ? 'brand' : 'category', 'scope_name' => $name, 'scope_id' => (int) $row->owner_id,
                    'mode' => $row->mode, 'inherit_source' => $row->inherit_source, 'visible' => (bool) $row->is_visible, 'blocks' => $blocks];
            }
        }
        $json = (string) json_encode(['meta' => ['exported_at' => now()->toIso8601String(), 'count' => count($items)], 'items' => $items],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return response()->streamDownload(fn () => print ($json), 'khoi-mo-ta-'.date('Ymd-His').'.json', ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, counts: array<string, int>}
     */
    public function preview(string $json): array
    {
        if (strlen($json) > 3 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Tệp lớn hơn 3 MB.']);
        }
        $payload = json_decode($json, true);
        $items = is_array($payload) ? ($payload['items'] ?? (array_is_list($payload) ? $payload : null)) : null;
        if (! is_array($items)) {
            throw ValidationException::withMessages(['file' => 'Nội dung không phải tệp khối mô tả hợp lệ.']);
        }
        $paths = Options::categoryPaths();
        $brands = DB::table('brands')->pluck('name', 'id')->all();
        $counts = ['create' => 0, 'update' => 0, 'unchanged' => 0, 'skip' => 0, 'error' => 0, 'missing_images' => 0];
        $rows = [];
        foreach ($items as $index => $item) {
            $row = ['line' => $index + 1, 'scope' => '', 'name' => '', 'owner_type' => null, 'owner_id' => null, 'status' => 'error', 'message' => '', 'blocks' => [], 'mode' => 'own', 'visible' => true];
            $scope = is_array($item) ? (string) ($item['scope'] ?? '') : '';
            $row['scope'] = $scope;
            $row['name'] = is_array($item) ? trim((string) ($item['scope_name'] ?? '')) : '';
            if (! isset(self::OWNERS[$scope])) {
                $row['status'] = 'skip';
                $row['message'] = 'Chỉ nhận khối của thương hiệu và danh mục.';
            } elseif ($row['name'] === '') {
                $row['message'] = 'Thiếu tên.';
            } else {
                $ids = self::OWNERS[$scope] === Brand::class ? self::match($brands, $row['name'], false) : self::match($paths, $row['name'], true);
                $row['blocks'] = self::clean($item['blocks'] ?? []);
                $row['mode'] = in_array($item['mode'] ?? 'own', ['own', 'inherit', 'both', 'hide'], true) ? ($item['mode'] ?? 'own') : 'own';
                $row['visible'] = (bool) ($item['visible'] ?? true);
                if (count($ids) !== 1) {
                    $row['message'] = $ids === [] ? 'Không tìm thấy “'.$row['name'].'”.' : 'Có '.count($ids).' mục cùng tên “'.$row['name'].'”.';
                } elseif ($row['blocks'] === [] && $row['mode'] === 'own') {
                    $row['status'] = 'skip';
                    $row['message'] = 'Không có khối hợp lệ.';
                } else {
                    $row['owner_type'] = self::OWNERS[$scope];
                    $row['owner_id'] = $ids[0];
                    $current = DB::table('content_sections')->where('owner_type', $row['owner_type'])->where('owner_id', $ids[0])->first();
                    $same = $current && self::clean(json_decode((string) $current->blocks, true)) === $row['blocks'] && $current->mode === $row['mode'] && (bool) $current->is_visible === $row['visible'];
                    $row['status'] = $current ? ($same ? 'unchanged' : 'update') : 'create';
                    $missing = array_values(array_filter(self::photos($row['blocks']), fn ($photo) => ! Storage::disk('upload_product')->exists($photo)));
                    if ($missing !== []) {
                        $row['message'] = 'Thiếu ảnh: '.implode(', ', array_slice($missing, 0, 3)).(count($missing) > 3 ? '…' : '');
                        $counts['missing_images']++;
                    }
                }
            }
            $counts[$row['status']]++;
            $rows[] = $row;
        }

        return ['rows' => $rows, 'counts' => $counts];
    }

    /** @param array{rows: list<array<string, mixed>>} $preview @return int sections written */
    public function apply(array $preview): int
    {
        return DB::transaction(function () use ($preview): int {
            $written = 0;
            foreach ($preview['rows'] as $row) {
                if (! in_array($row['status'], ['create', 'update'], true)) {
                    continue;
                }
                $section = ContentSection::query()->firstOrNew(['owner_type' => $row['owner_type'], 'owner_id' => $row['owner_id']]);
                $section->historyReason = 'import';
                $section->setRawAttributes(array_merge($section->getAttributes(), ['owner_type' => $row['owner_type'], 'owner_id' => $row['owner_id'],
                    'mode' => $row['mode'], 'blocks' => json_encode($row['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'is_visible' => $row['visible']]));
                $section->save();
                $written++;
            }

            return $written;
        });
    }

    /**
     * Old rich-text content split into blocks: headings, lists, local pictures and paragraphs.
     *
     * @return array{blocks: list<array<string, mixed>>, skipped_images: int}
     */
    public static function htmlToBlocks(string $html): array
    {
        $html = trim($html);
        if ($html === '') {
            return ['blocks' => [], 'skipped_images' => 0];
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $errors = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>');
        libxml_clear_errors();
        libxml_use_internal_errors($errors);
        $root = $dom->getElementById('root');
        $blocks = [];
        $buffer = '';
        $skipped = 0;
        $flush = function () use (&$blocks, &$buffer): void {
            $text = trim((string) preg_replace(['/[ \t]+\n/u', '/\n{3,}/u'], ["\n", "\n\n"], $buffer));
            if ($text !== '') {
                $blocks[] = ['type' => 'text', 'text' => $text];
            }
            $buffer = '';
        };
        $walk = function (\DOMNode $node) use (&$walk, &$blocks, &$buffer, $flush, &$skipped): void {
            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMText) {
                    $buffer .= (string) preg_replace('/\s+/u', ' ', $child->nodeValue);

                    continue;
                }
                if (! $child instanceof \DOMElement) {
                    continue;
                }
                $tag = strtolower($child->nodeName);
                $text = fn () => trim((string) preg_replace('/\s+/u', ' ', $child->textContent));
                if ($tag === 'img') {
                    $flush();
                    if (preg_match('#(?:^|/)upload/product/([A-Za-z0-9._-]+\.(?:jpe?g|png|webp|gif))#i', $child->getAttribute('src'), $m)) {
                        $blocks[] = ['type' => 'image', 'photo' => $m[1], 'caption' => trim($child->getAttribute('alt'))];
                    } else {
                        $skipped++;
                    }
                } elseif (in_array($tag, ['h1', 'h2', 'h3', 'h4'], true)) {
                    $flush();
                    if ($text() !== '') {
                        $blocks[] = ['type' => 'heading', 'text' => $text()];
                    }
                } elseif ($tag === 'ul' || $tag === 'ol') {
                    $flush();
                    $items = [];
                    foreach ($child->getElementsByTagName('li') as $li) {
                        if (($item = trim((string) preg_replace('/\s+/u', ' ', $li->textContent))) !== '') {
                            $items[] = $item;
                        }
                    }
                    if ($items !== []) {
                        $blocks[] = ['type' => 'list', 'ordered' => $tag === 'ol', 'items' => $items];
                    }
                } elseif ($tag === 'br') {
                    $buffer .= "\n";
                } elseif (in_array($tag, ['p', 'div', 'section', 'article'], true)) {
                    $before = strlen($buffer);
                    $walk($child);
                    if (strlen($buffer) > $before) {
                        $buffer .= "\n\n";
                    }
                } elseif (! in_array($tag, ['script', 'style', 'iframe'], true)) {
                    $walk($child);
                }
            }
        };
        if ($root) {
            $walk($root);
        }
        $flush();
        $merged = [];
        foreach ($blocks as $block) {
            $last = count($merged) - 1;
            if ($block['type'] === 'text' && $last >= 0 && $merged[$last]['type'] === 'text') {
                $merged[$last]['text'] = trim($merged[$last]['text']."\n\n".$block['text']);
            } else {
                $merged[] = $block;
            }
        }

        return ['blocks' => $merged, 'skipped_images' => $skipped];
    }

    /** @return list<array<string, mixed>> known block types with their fields as plain values */
    public static function clean(mixed $blocks): array
    {
        $out = [];
        foreach (is_array($blocks) ? $blocks : [] as $block) {
            if (is_array($block) && in_array($block['type'] ?? null, self::TYPES, true)) {
                $out[] = array_map(fn ($value) => is_string($value) ? trim(strip_tags($value)) : $value, $block);
            }
        }

        return $out;
    }

    /** @return list<int> */
    private static function match(array $names, string $wanted, bool $path): array
    {
        $key = fn (string $value) => mb_strtolower(trim((string) preg_replace(['/\s*(?:›|>)\s*/u', '/\s+/u'], [' › ', ' '], $value)), 'UTF-8');
        $exact = array_keys(array_filter($names, fn ($name) => $key((string) $name) === $key($wanted)));
        if ($exact !== [] || ! $path) {
            return array_map('intval', $exact);
        }

        return array_map('intval', array_keys(array_filter($names, fn ($name) => $key((string) last(explode(' › ', (string) $name))) === $key($wanted))));
    }

    /** @return list<string> */
    private static function photos(array $blocks): array
    {
        $photos = [];
        array_walk_recursive($blocks, function ($value, $key) use (&$photos) {
            if ($key === 'photo' && is_string($value) && $value !== '') {
                $photos[] = $value;
            }
        });

        return array_values(array_unique($photos));
    }
}
