<?php

use App\Domain\Audit\Audit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Move products from the garden-lighting parent into the existing product subcategories. */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $lightingRootId = DB::table('categories')->whereNull('parent_id')->where('slug', 'den-nlmt')->value('id');
            $gardenId = $lightingRootId === null ? null : DB::table('categories')->where('parent_id', $lightingRootId)->where('slug', 'den-san-vuon')->value('id');
            if ($gardenId === null) {
                throw new \RuntimeException('Không tìm thấy danh mục Đèn NLMT › Đèn Sân Vườn.');
            }

            $children = DB::table('categories')->where('parent_id', $gardenId)
                ->whereIn('slug', ['den-tru-cong', 'den-treo-tuong', 'den-san-vuon-den-nam', 'den-cam-co'])
                ->pluck('id', 'slug');
            $required = ['den-tru-cong', 'den-treo-tuong', 'den-san-vuon-den-nam', 'den-cam-co'];
            if ($children->count() !== count($required)) {
                throw new \RuntimeException('Thiếu một hoặc nhiều danh mục con của Đèn Sân Vườn.');
            }

            $beforeCount = DB::table('products')->where('category_id', $gardenId)->count();
            $moved = [];
            foreach ([
                'den-tru-cong' => '%trụ cổng%',
                'den-treo-tuong' => '%treo tường%',
                'den-cam-co' => '%cắm cỏ%',
            ] as $slug => $pattern) {
                $moved[$slug] = DB::table('products')->where('category_id', $gardenId)->where('name', 'like', $pattern)
                    ->update(['category_id' => $children[$slug], 'updated_at' => now()]);
            }

            // Generic garden lamps, including mushroom and bollard models, belong in the general garden-light child.
            $moved['den-san-vuon-den-nam'] = DB::table('products')->where('category_id', $gardenId)
                ->update(['category_id' => $children['den-san-vuon-den-nam'], 'updated_at' => now()]);

            if (array_sum($moved) !== $beforeCount || DB::table('products')->where('category_id', $gardenId)->exists()) {
                throw new \RuntimeException('Không phân loại hết sản phẩm Đèn Sân Vườn; giao dịch đã được hoàn tác.');
            }

            if ($beforeCount > 0) {
                Audit::record('catalog.assign_garden_lights', 'Category', (int) $gardenId, ['products' => $beforeCount], [
                    'products_by_child' => collect($moved)->mapWithKeys(fn ($count, $slug) => [$slug => (int) $count])->all(),
                ], 'system:migration');
            }
        });
    }

    public function down(): void
    {
        // Keep this data-only change across code rollback; all product URLs stay the same.
    }
};
