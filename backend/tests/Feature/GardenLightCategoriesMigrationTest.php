<?php

use Illuminate\Support\Facades\DB;

it('moves garden lights into their matching child categories and leaves product URLs intact', function () {
    $tag = bin2hex(random_bytes(4));
    $category = function (?int $parentId, string $slug, string $name, int $depth): int {
        $id = DB::table('categories')->where('parent_id', $parentId)->where('slug', $slug)->value('id');
        if ($id !== null) {
            return (int) $id;
        }

        return (int) DB::table('categories')->insertGetId([
            'parent_id' => $parentId, 'depth' => $depth, 'slug' => $slug, 'name' => $name,
            'is_visible' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    };
    $rootId = $category(null, 'den-nlmt', 'Đèn NLMT', 1);
    $parentId = $category($rootId, 'den-san-vuon', 'Đèn Sân Vườn', 2);
    $children = [
        'den-tru-cong' => $category($parentId, 'den-tru-cong', 'Đèn trụ cổng', 3),
        'den-treo-tuong' => $category($parentId, 'den-treo-tuong', 'Đèn treo tường', 3),
        'den-san-vuon-den-nam' => $category($parentId, 'den-san-vuon-den-nam', 'Đèn sân vườn / đèn nấm', 3),
        'den-cam-co' => $category($parentId, 'den-cam-co', 'Đèn cắm cỏ', 3),
    ];
    foreach ([
        ['den-tru-cong', 'Đèn trụ cổng kiểm tra'],
        ['den-treo-tuong', 'Đèn treo tường kiểm tra'],
        ['den-cam-co', 'Đèn cắm cỏ kiểm tra'],
        ['den-san-vuon-den-nam', 'Đèn sân vườn kiểm tra'],
    ] as $index => [$child, $name]) {
        DB::table('products')->insert([
            'category_id' => $parentId, 'name' => $name.' '.$tag, 'slug' => 'garden-migration-'.$index.'-'.$tag,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    $slugsBefore = DB::table('products')->where('category_id', $parentId)->orderBy('id')->pluck('slug')->all();
    $migration = require database_path('migrations/2026_11_10_001900_assign_garden_lights_to_children.php');

    $migration->up();
    $counts = collect($children)->mapWithKeys(fn ($id, $slug) => [$slug => DB::table('products')->where('category_id', $id)->count()]);

    expect(array_sum($counts->all()))->toBe(count($slugsBefore))
        ->and($counts['den-tru-cong'])->toBeGreaterThan(0)
        ->and($counts['den-treo-tuong'])->toBeGreaterThan(0)
        ->and($counts['den-san-vuon-den-nam'])->toBeGreaterThan(0)
        ->and($counts['den-cam-co'])->toBeGreaterThan(0)
        ->and(DB::table('products')->where('category_id', $parentId)->exists())->toBeFalse()
        ->and(DB::table('products')->whereIn('slug', $slugsBefore)->orderBy('id')->pluck('slug')->all())->toBe($slugsBefore)
        ->and(DB::table('products')->where('slug', 'garden-migration-0-'.$tag)->value('category_id'))->toBe($children['den-tru-cong'])
        ->and(DB::table('products')->where('slug', 'garden-migration-1-'.$tag)->value('category_id'))->toBe($children['den-treo-tuong'])
        ->and(DB::table('products')->where('slug', 'garden-migration-2-'.$tag)->value('category_id'))->toBe($children['den-cam-co'])
        ->and(DB::table('products')->where('slug', 'garden-migration-3-'.$tag)->value('category_id'))->toBe($children['den-san-vuon-den-nam']);

    $after = DB::table('products')->whereIn('category_id', $children)
        ->selectRaw('category_id, count(*) as total')->groupBy('category_id')->orderBy('category_id')->get()->toJson();
    $migration->up();
    expect(DB::table('products')->whereIn('category_id', $children)
        ->selectRaw('category_id, count(*) as total')->groupBy('category_id')->orderBy('category_id')->get()->toJson())->toBe($after);
});
