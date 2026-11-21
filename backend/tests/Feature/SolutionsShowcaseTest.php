<?php

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Solutions\SolutionsListing;
use App\Models\Category;
use App\Models\Product;
use App\Support\SiteSettings;

// The "Đi thẳng đến sản phẩm" stage shows each category's clean Shopping pictures, not the main photo.
it('lists up to three visible products with an existing Shopping picture for a category', function () {
    $tag = bin2hex(random_bytes(3));
    // A private upload folder, so the test neither needs nor touches the real upload volume.
    $root = sys_get_temp_dir().'/pts-showcase-'.$tag;
    mkdir($root.'/product', 0775, true);
    $urls = new ProductMediaUrls($root);
    $top = Category::query()->create(['name' => 'Thiết bị '.$tag, 'slug' => 'thiet-bi-'.$tag, 'depth' => 1, 'is_visible' => true]);
    $sub = Category::query()->create(['name' => 'Nhóm '.$tag, 'slug' => 'nhom-'.$tag, 'depth' => 2, 'parent_id' => $top->id, 'is_visible' => true]);

    $files = [];
    $make = function (string $name, array $extra = [], bool $withFile = true) use ($tag, $sub, $urls, &$files) {
        $file = "sp-{$tag}-{$name}-shopping-trang.jpg";
        if ($withFile) {
            $disk = $urls->absolute('/upload/product/'.$file);
            file_put_contents($disk, 'x');
            $files[] = $disk;
        }

        return Product::query()->create($extra + ['name' => "SP {$name} {$tag}", 'slug' => "sp-{$name}-{$tag}", 'category_id' => $sub->id, 'is_visible' => true, 'feed_image' => $file, 'sort_order' => 1]);
    };
    foreach (['a', 'b', 'c', 'd'] as $n) {
        $make($n);
    }
    $hidden = $make('hidden', ['is_visible' => false]);
    $noPicture = $make('nopic', ['feed_image' => null], false);
    $missingFile = $make('missing', [], false);

    try {
        $listing = new SolutionsListing($urls, app(SiteSettings::class));
        $method = new ReflectionMethod($listing, 'showcase');
        $rows = $method->invoke($listing, new CategoryTree, (int) $top->id);

        expect($rows)->toHaveCount(3);
        foreach ($rows as $row) {
            expect($row['image'])->toStartWith('/upload/product/sp-'.$tag.'-')->and($row['url'])->toStartWith('/san-pham/sp-')
                ->and($row['id'])->not->toBeIn([$hidden->id, $noPicture->id, $missingFile->id]);
        }
    } finally {
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($root.'/product');
        @rmdir($root);
    }
});
