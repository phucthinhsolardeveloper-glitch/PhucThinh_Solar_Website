<?php

namespace App\Domain\Catalog\Bulk;

use App\Filament\Support\Options;
use App\Models\Product;
use App\Support\Slugs;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Everything a workbook run looks up, loaded once so thousands of rows do not mean thousands of queries. */
final class BulkCatalog
{
    /** @var array<int, string> id => "Cha › Con" */
    public array $categoryPaths;

    /** @var array<int, int|null> id => parent id */
    public array $categoryParents;

    /** @var array<int, string> */
    public array $brands;

    /** @var array<string, list<array{table: string, id: int}>> */
    public array $slugOwners;

    /** @var array<string, list<int>> lower-case code => product ids */
    public array $codeOwners = [];

    /** @var array<int, array<string, string>> product id => column key => current value as exported */
    public array $values = [];

    /** @var Collection<int, Product> */
    public Collection $products;

    /** @var list<string>|null */
    private ?array $files = null;

    public function __construct(?Collection $products = null)
    {
        $this->categoryPaths = Options::categoryPaths();
        $this->categoryParents = DB::table('categories')->pluck('parent_id', 'id')->map(fn ($id) => $id ? (int) $id : null)->all();
        $this->brands = DB::table('brands')->orderBy('sort_order')->orderBy('id')->pluck('name', 'id')->all();
        $this->slugOwners = Slugs::owners();
        $this->products = ($products ?? Product::query()->with(['seo', 'images', 'contentSection'])->orderBy('id')->get())->keyBy('id');
        foreach ($this->products as $product) {
            $code = mb_strtolower(trim((string) $product->code), 'UTF-8');
            if ($code !== '') {
                $this->codeOwners[$code][] = (int) $product->id;
            }
            $this->values[(int) $product->id] = $this->exportValues($product);
        }
    }

    /** @return array<string, string> */
    public function exportValues(Product $product): array
    {
        $section = $product->contentSection;
        $values = [];
        foreach (BulkColumns::exported() as $key => $column) {
            $raw = isset($column['field']) && $column['kind'] !== 'seo' ? $product->getRawOriginal($column['field']) : null;
            $values[$key] = match ($column['kind']) {
                'control' => $key === 'id' ? (string) $product->id : '',
                'category' => $this->categoryPaths[(int) $raw] ?? '',
                'brand' => $this->brands[(int) $raw] ?? '',
                'money' => (float) $raw > 0 ? (string) (int) $raw : '0',
                'decimal' => $raw === null ? '' : (string) BulkText::decimal((string) $raw),
                'flag' => $raw ? '1' : '0',
                'html' => BulkText::htmlToText((string) $raw),
                'pairs' => BulkText::linesFrom((string) $raw, true),
                'lines' => BulkText::linesFrom((string) $raw, false),
                'specs' => BulkText::specsFrom((string) $raw),
                'sections' => match ($section?->getRawOriginal('mode')) {
                    'own', 'both' => BulkText::blocksToMarkdown(json_decode((string) $section->getRawOriginal('blocks'), true) ?: []),
                    'hide' => BulkColumns::HIDE_SECTIONS,
                    default => '',
                },
                'gallery' => $product->images->sortBy('sort_order')->pluck('path')->implode("\n"),
                'seo' => trim((string) $product->seo?->getAttribute($column['field'])),
                default => trim(html_entity_decode((string) $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            };
        }

        return $values;
    }

    /** @return int id; 0 unknown; -1 several categories share that name */
    public function findCategory(string $name): int
    {
        $wanted = self::pathKey($name);
        $leaf = [];
        foreach ($this->categoryPaths as $id => $path) {
            if (self::pathKey($path) === $wanted) {
                return (int) $id;
            }
            $parts = explode(' › ', $path);
            if (self::pathKey((string) end($parts)) === $wanted) {
                $leaf[] = (int) $id;
            }
        }

        return count($leaf) === 1 ? $leaf[0] : (count($leaf) > 1 ? -1 : 0);
    }

    /** @return int id; 0 unknown; -1 ambiguous */
    public function findBrand(string $name): int
    {
        $wanted = self::key($name);
        $ids = [];
        foreach ($this->brands as $id => $brand) {
            if (self::key($brand) === $wanted) {
                $ids[] = (int) $id;
            }
        }

        return count($ids) === 1 ? $ids[0] : (count($ids) > 1 ? -1 : 0);
    }

    public function fileExists(string $name): bool
    {
        return in_array($name, $this->files(), true);
    }

    /** Same name in other case, same name with another extension, then a near miss. */
    public function suggestFile(string $value): ?string
    {
        foreach ($this->files() as $file) {
            if (strcasecmp($file, $value) === 0) {
                return $file;
            }
        }
        $stem = pathinfo($value, PATHINFO_FILENAME);
        foreach ($this->files() as $file) {
            if (strcasecmp(pathinfo($file, PATHINFO_FILENAME), $stem) === 0) {
                return $file;
            }
        }
        $closest = null;
        $best = 5;
        foreach ($this->files() as $file) {
            if (abs(strlen($file) - strlen($value)) <= 6 && ($distance = levenshtein(strtolower($file), strtolower($value))) < $best) {
                $best = $distance;
                $closest = $file;
            }
        }

        return $closest;
    }

    public static function key(string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $value)), 'UTF-8');
    }

    private static function pathKey(string $value): string
    {
        return self::key((string) preg_replace('/\s*(?:›|>|\/)\s*/u', ' › ', $value));
    }

    /** @return list<string> */
    private function files(): array
    {
        return $this->files ??= Storage::disk('upload_product')->files();
    }
}
