<?php

namespace App\Domain\Catalog\Bulk;

use App\Domain\Audit\Audit;
use App\Domain\Content\PublishChecks;
use App\Models\Brand;
use App\Models\ContentSection;
use App\Models\Product;
use App\Support\Slugs;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Re-checks a previewed file against today's data and writes it, all or nothing. */
final class BulkApplier
{
    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<int, array<string, string>>  $baseline
     * @param  array<string, mixed>  $choices
     * @return array<string, int> counts
     */
    /** @var list<string> products kept hidden by the publish checks in the last apply(), with the reasons */
    public array $heldBack = [];

    public function apply(array $rows, array $baseline, array $choices, bool $hiddenConfirmed): array
    {
        return DB::transaction(function () use ($rows, $baseline, $choices, $hiddenConfirmed): array {
            $plan = (new BulkPlanner(new BulkCatalog, $choices, strict: true))->plan($rows, $baseline);
            if ($plan['errors'] > 0) {
                $first = collect($plan['rows'])->firstWhere(fn ($row) => $row['errors'] !== []);
                throw ValidationException::withMessages(['file' => 'Dữ liệu còn '.$plan['errors'].' lỗi, chưa ghi gì. Dòng '.$first['row'].': '.$first['errors'][0]]);
            }
            $hidden = $plan['counts']['draft_create'] + $plan['counts']['hide_update'] + $plan['counts']['archive'];
            if ($hidden > 0 && ! $hiddenConfirmed) {
                throw ValidationException::withMessages(['file' => 'Có '.$hidden.' sản phẩm sẽ bị ẩn. Hãy xác nhận riêng rồi áp dụng lại; chưa ghi gì.']);
            }
            $brands = $this->createBrands($plan, $choices);
            $switchedOn = [];
            foreach ($plan['rows'] as $row) {
                if ($row['action'] !== 'skip') {
                    [$product, $wasVisible] = $this->write($row, $brands);
                    if ($product->is_visible && ! $wasVisible) {
                        $switchedOn[] = $product;
                    }
                }
            }
            // Same rule as the edit page: a product that goes on the site from this file must pass the publish checks;
            // otherwise its data is kept but it stays hidden, and the page says why.
            $this->heldBack = [];
            foreach ($switchedOn as $product) {
                $errors = app(PublishChecks::class)->product($product->refresh())['errors'];
                if ($errors !== []) {
                    $product->update(['is_visible' => false]);
                    $this->heldBack[] = ($product->code ?: '#'.$product->id).' '.$product->name.': '.implode(', ', $errors);
                }
            }
            $counts = $plan['counts'] + ['brand_create' => count($brands), 'held_back' => count($this->heldBack)];
            Audit::record('product.bulk_import', 'Product', null, null, $counts);

            return $counts;
        });
    }

    /** @return array<string, int> brand key => new id */
    private function createBrands(array $plan, array $choices): array
    {
        $ids = [];
        foreach ($plan['new_brands'] as $key => $brand) {
            if (($choices['brands'][$key] ?? '') === 'create') {
                $slug = Slugs::free($brand['name'], Slugs::publicScope('brands'));
                $ids[$key] = (int) Brand::query()->create(['name' => $brand['name'], 'slug' => $slug, 'is_visible' => true])->id;
            }
        }

        return $ids;
    }

    /** @return array{0: Product, 1: bool} the product and whether it was on the site before this file */
    private function write(array $row, array $brands): array
    {
        $columns = $row['columns'];
        if ($row['pending_brand'] !== '') {
            $columns['brand_id'] = $brands[$row['pending_brand']];
        }
        $wasVisible = false;
        if ($row['action'] === 'create') {
            $product = Product::query()->create($columns + ['is_visible' => true, 'code' => $row['code']]);
        } else {
            $product = Product::query()->findOrFail($row['id']);
            $wasVisible = (bool) $product->is_visible;
            if ($columns !== []) {
                $product->update($columns);
            }
        }
        if ($row['seo'] !== []) {
            $product->seo()->updateOrCreate([], $row['seo']);
        }
        if ($row['gallery'] !== null) {
            $this->syncGallery($product, $row['gallery']);
        }
        if ($row['sections'] !== null) {
            $this->writeSections($product, $row['sections']);
        }

        return [$product, $wasVisible];
    }

    /** Pictures staying in the list keep their caption and visibility. */
    private function syncGallery(Product $product, array $names): void
    {
        $existing = $product->images()->get()->keyBy('path');
        $product->images()->whereNotIn('path', $names ?: [''])->delete();
        foreach ($names as $order => $name) {
            if ($existing->has($name)) {
                $existing[$name]->update(['sort_order' => $order]);
            } else {
                $product->images()->create(['path' => $name, 'is_visible' => true, 'sort_order' => $order]);
            }
        }
    }

    private function writeSections(Product $product, string $value): void
    {
        $section = ContentSection::query()->firstOrNew(['owner_type' => Product::class, 'owner_id' => $product->id]);
        $old = json_decode((string) ($section->getRawOriginal('blocks') ?: '[]'), true) ?: [];
        [$mode, $blocks] = match ($value) {
            BulkColumns::HIDE_SECTIONS, '[an]' => ['hide', $old],
            BulkColumns::CLEAR => ['inherit', []],
            default => ['own', [...BulkText::markdownToBlocks($value), ...array_values(array_filter($old, fn ($block) => ($block['type'] ?? '') === 'cards'))]],
        };
        $section->historyReason = 'import';
        $section->setRawAttributes(array_merge($section->getAttributes(), ['owner_type' => Product::class, 'owner_id' => $product->id, 'mode' => $mode,
            'blocks' => json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'is_visible' => true]));
        $section->save();
    }
}
