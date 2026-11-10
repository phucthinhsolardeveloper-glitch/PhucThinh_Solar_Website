<?php

use App\Domain\Audit\Audit;
use App\Domain\Catalog\CategoryTree;
use App\Domain\Routing\Redirects;
use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Product category tree tidy-up approved by the owner on 2026-11-10:
 *  1. The empty level-1 "Đèn sân vườn" duplicated "Đèn năng lượng mặt trời › Đèn Sân Vườn" (68 products). Its four empty
 *     groups (trụ cổng, treo tường, đèn nấm, cắm cỏ) become level 3 under the real one; the empty duplicate goes.
 *  2. "Kẹp tấm pin" leaves "Tấm pin" for "Thiết bị & phụ kiện điện mặt trời › Vật tư phụ trợ".
 *  3. The two tape products sitting straight on "Thiết bị & phụ kiện điện mặt trời" move into "Vật tư phụ trợ".
 * Categories are found by slug path, not id; anything not found is skipped, so the migration is safe on any copy.
 * Every address that moves answers with a 301 to the new one (sections: pages below follow).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $done = [];
            $garden = $this->find(['den-nlmt', 'den-san-vuon']);
            $duplicate = $this->find(['den-san-vuon']);
            if ($garden && $duplicate && ! DB::table('products')->where('category_id', $duplicate)->exists()) {
                $fromDuplicate = $this->path($duplicate);
                foreach (DB::table('categories')->where('parent_id', $duplicate)->pluck('id') as $child) {
                    $done[] = $this->move((int) $child, $garden);
                }
                if (! DB::table('categories')->where('parent_id', $duplicate)->exists()) {
                    DB::table('seo_metas')->where('owner_type', Category::class)->where('owner_id', $duplicate)->delete();
                    DB::table('content_sections')->where('owner_type', Category::class)->where('owner_id', $duplicate)->delete();
                    DB::table('categories')->where('id', $duplicate)->delete();
                    Redirects::record($fromDuplicate, $this->path($garden), true);
                    $done[] = 'Bỏ danh mục cấp 1 rỗng "Đèn sân vườn" (#'.$duplicate.')';
                }
            }

            $supplies = $this->find(['thiet-bi-dien-nang-luong-mat-troi', 'vat-tu-phu-tro']);
            $clamp = $this->find(['tam-pin-nang-luong-mat-troi', 'kep-tam-pin']);
            if ($supplies && $clamp) {
                $done[] = $this->move($clamp, $supplies);
            }

            $equipment = $this->find(['thiet-bi-dien-nang-luong-mat-troi']);
            if ($supplies && $equipment) {
                $moved = DB::table('products')->where('category_id', $equipment)->whereIn('slug', ['bang-keo-trong', 'bang-keo-hang-de-vo'])
                    ->update(['category_id' => $supplies, 'updated_at' => now()]);
                if ($moved) {
                    $done[] = $moved.' sản phẩm băng keo vào "Vật tư phụ trợ"';
                }
            }

            if ($done !== []) {
                Audit::record('catalog.tidy_categories', 'Category', null, null, ['changes' => $done], 'system:migration');
            }
        });
    }

    public function down(): void
    {
        // Data reshuffle approved by the owner; restore from the backup taken before deploy if it must be undone.
    }

    /** Moves a category (and its sub-tree) under a new parent, fixing depths and keeping its old address as a 301. */
    private function move(int $id, int $parent): string
    {
        $from = $this->path($id);
        $name = (string) DB::table('categories')->where('id', $id)->value('name');
        $slug = (string) DB::table('categories')->where('id', $id)->value('slug');
        if (DB::table('categories')->where('parent_id', $parent)->where('slug', $slug)->where('id', '<>', $id)->exists()) {
            $slug .= '-'.$id;
        }
        $depth = (int) DB::table('categories')->where('id', $parent)->value('depth') + 1;
        DB::table('categories')->where('id', $id)->update(['parent_id' => $parent, 'depth' => $depth, 'slug' => $slug, 'updated_at' => now()]);
        $this->fixDepths($id, $depth);
        Redirects::record($from, $this->path($id), true);

        return '"'.$name.'" → '.trim($this->path($id), '/');
    }

    private function fixDepths(int $id, int $depth): void
    {
        foreach (DB::table('categories')->where('parent_id', $id)->pluck('id') as $child) {
            DB::table('categories')->where('id', $child)->update(['depth' => $depth + 1]);
            $this->fixDepths((int) $child, $depth + 1);
        }
    }

    /** Category id by slug path from the top (any visibility), or null. */
    private function find(array $slugs): ?int
    {
        $parent = null;
        foreach ($slugs as $slug) {
            $id = DB::table('categories')->where('slug', $slug)
                ->when($parent === null, fn ($q) => $q->whereNull('parent_id'), fn ($q) => $q->where('parent_id', $parent))->value('id');
            if ($id === null) {
                return null;
            }
            $parent = (int) $id;
        }

        return $parent;
    }

    private function path(int $id): string
    {
        return '/'.trim((new CategoryTree)->path($id), '/').'/';
    }
};
