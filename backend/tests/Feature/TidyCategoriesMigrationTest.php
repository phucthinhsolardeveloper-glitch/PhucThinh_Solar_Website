<?php

use App\Domain\Routing\Redirects;
use Illuminate\Support\Facades\DB;

function categoryId(array $slugs): ?int
{
    $parent = null;
    foreach ($slugs as $slug) {
        $id = DB::table('categories')->where('slug', $slug)->when($parent === null, fn ($q) => $q->whereNull('parent_id'), fn ($q) => $q->where('parent_id', $parent))->value('id');
        if ($id === null) {
            return null;
        }
        $parent = (int) $id;
    }

    return $parent;
}

it('folds the duplicate garden lights, moves the panel clamps and the tapes, and keeps old links as 301s', function () {
    if (categoryId(['den-nlmt', 'den-san-vuon']) === null || categoryId(['thiet-bi-dien-nang-luong-mat-troi', 'vat-tu-phu-tro']) === null) {
        $this->markTestSkipped('This copy of the database does not have the production category tree.');
    }
    $migration = require database_path('migrations/2026_11_10_001800_tidy_product_categories.php');
    $migration->up();
    $garden = categoryId(['den-nlmt', 'den-san-vuon']);
    $supplies = categoryId(['thiet-bi-dien-nang-luong-mat-troi', 'vat-tu-phu-tro']);

    expect(categoryId(['den-san-vuon']))->toBeNull()
        ->and(DB::table('categories')->where('parent_id', $garden)->pluck('slug')->sort()->values()->all())->toBe(['den-cam-co', 'den-san-vuon-den-nam', 'den-treo-tuong', 'den-tru-cong'])
        ->and(DB::table('categories')->where('parent_id', $garden)->pluck('depth')->unique()->all())->toBe([3])
        ->and(categoryId(['thiet-bi-dien-nang-luong-mat-troi', 'vat-tu-phu-tro', 'kep-tam-pin']))->not->toBeNull()
        ->and(categoryId(['tam-pin-nang-luong-mat-troi', 'kep-tam-pin']))->toBeNull()
        ->and(DB::table('products')->whereIn('slug', ['bang-keo-trong', 'bang-keo-hang-de-vo'])->pluck('category_id')->unique()->all())->toBe([$supplies])
        ->and(Redirects::find('/danh-muc-san-pham/den-san-vuon/'))->toBe('/danh-muc-san-pham/den-nlmt/den-san-vuon/')
        ->and(Redirects::find('/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/kep-tam-pin/'))->toBe('/danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/vat-tu-phu-tro/kep-tam-pin/');

    // A second run finds nothing left to do.
    $before = DB::table('categories')->orderBy('id')->get(['id', 'parent_id', 'depth', 'slug'])->toJson();
    $migration->up();
    expect(DB::table('categories')->orderBy('id')->get(['id', 'parent_id', 'depth', 'slug'])->toJson())->toBe($before);
});
