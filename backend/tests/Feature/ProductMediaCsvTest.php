<?php

use App\Domain\Catalog\ProductMediaCsv;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function mediaCsvFile(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'pts-media-');
    $out = fopen($path, 'w');
    fputcsv($out, ProductMediaCsv::HEADERS, ',', '"', '');
    foreach ($rows as $row) {
        fputcsv($out, $row, ',', '"', '');
    }
    fclose($out);

    return $path;
}

function mediaCsvRow(Product $product): array
{
    return [$product->id, $product->code, $product->name, $product->slug, $product->category_id,
        $product->image, $product->image_secondary, $product->images->pluck('path')->implode('|')];
}

beforeEach(function () {
    Storage::fake('upload_product');
    $this->csvActor = User::query()->create(['name' => 'Quản trị CSV', 'username' => 'media-service-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
});

it('round-trips the exported catalog without changing existing products', function () {
    $service = app(ProductMediaCsv::class);
    ob_start();
    $service->export()->sendContent();
    $path = tempnam(sys_get_temp_dir(), 'pts-media-');
    file_put_contents($path, ob_get_clean());
    try {
        $preview = $service->preview($path);
        expect($preview['total'])->toBe(Product::query()->count())
            ->and($preview['created'])->toBe(0)->and($preview['updated'])->toBe(0)
            ->and($preview['errors'])->toBe([]);
        expect($service->apply($path, $preview['fingerprint'], $this->csvActor)['updated'])->toBe(0);
    } finally {
        unlink($path);
    }
});

it('creates hidden products and updates only selected media while preserving gallery captions', function () {
    $product = Product::query()->whereNotNull('category_id')->firstOrFail();
    $categoryId = Category::query()->value('id');
    Storage::disk('upload_product')->putFileAs('', UploadedFile::fake()->image('new.jpg'), 'new.jpg');
    Storage::disk('upload_product')->putFileAs('', UploadedFile::fake()->image('gallery.png'), 'gallery.png');
    $product->images()->create(['path' => 'new.jpg', 'caption' => 'Giữ chú thích', 'sort_order' => 0]);
    $product->images()->create(['path' => 'old.jpg', 'caption' => 'Bỏ ảnh', 'sort_order' => 1]);
    $product->refresh();
    $row = mediaCsvRow($product);
    $row[5] = 'gallery.png';
    $row[6] = '';
    $row[7] = 'gallery.png|new.jpg';
    $newSlug = 'nhap-csv-media-'.bin2hex(random_bytes(4));
    $path = mediaCsvFile([$row, ['', 'CSV-NEW-'.bin2hex(random_bytes(4)), 'Sản phẩm nhập CSV', $newSlug,
        $categoryId, 'new.jpg', '', 'gallery.png']]);
    $oldPrice = $product->regular_price;
    try {
        $service = app(ProductMediaCsv::class);
        $preview = $service->preview($path);
        expect($preview['created'])->toBe(1)->and($preview['updated'])->toBe(1)->and($preview['errors'])->toBe([]);
        $service->apply($path, $preview['fingerprint'], $this->csvActor);
        $saved = $product->fresh();
        expect($saved->image)->toBe('gallery.png')->and($saved->image_secondary)->toBe($product->image_secondary)
            ->and($saved->regular_price)->toBe($oldPrice)
            ->and($saved->images->pluck('path')->all())->toBe(['gallery.png', 'new.jpg'])
            ->and($saved->images->firstWhere('path', 'new.jpg')->caption)->toBe('Giữ chú thích');
        $created = Product::query()->where('slug', $newSlug)->firstOrFail();
        expect($created->is_visible)->toBeFalse()->and($created->image)->toBe('new.jpg')
            ->and($created->images()->count())->toBe(1);
    } finally {
        unlink($path);
    }
});

it('blocks missing images and stale previews without a partial write', function () {
    $product = Product::query()->whereNotNull('category_id')->firstOrFail();
    Storage::disk('upload_product')->putFileAs('', UploadedFile::fake()->image('valid.jpg'), 'valid.jpg');
    $row = mediaCsvRow($product);
    $row[5] = 'valid.jpg';
    $newSlug = 'csv-missing-'.bin2hex(random_bytes(4));
    $path = mediaCsvFile([$row, ['', '', 'Thiếu ảnh', $newSlug, $product->category_id, 'missing.jpg', '', '']]);
    try {
        $service = app(ProductMediaCsv::class);
        $preview = $service->preview($path);
        expect($preview['errors'])->toHaveCount(1);
        expect(fn () => $service->apply($path, $preview['fingerprint'], $this->csvActor))->toThrow(ValidationException::class);
        expect($product->fresh()->image)->toBe($product->image)
            ->and(Product::query()->where('slug', $newSlug)->exists())->toBeFalse();
    } finally {
        unlink($path);
    }

    $path = mediaCsvFile([$row]);
    try {
        $preview = $service->preview($path);
        $product->update(['image' => 'changed-outside-csv.jpg']);
        expect(fn () => $service->apply($path, $preview['fingerprint'], $this->csvActor))->toThrow(ValidationException::class);
    } finally {
        unlink($path);
    }
});

it('rejects renamed non-images and duplicate new product slugs', function () {
    $categoryId = Category::query()->value('id');
    Storage::disk('upload_product')->put('fake.jpg', 'not an image');
    Storage::disk('upload_product')->putFileAs('', UploadedFile::fake()->image('valid.jpg'), 'valid.jpg');
    $slug = 'csv-duplicate-'.bin2hex(random_bytes(4));
    $path = mediaCsvFile([
        ['', '', 'Sản phẩm một', $slug, $categoryId, 'valid.jpg', '', ''],
        ['', '', 'Sản phẩm hai', $slug, $categoryId, '', '', ''],
        ['', '', 'Sản phẩm ba', $slug.'-fake', $categoryId, 'fake.jpg', '', ''],
    ]);
    try {
        $preview = app(ProductMediaCsv::class)->preview($path);
        expect($preview['errors'])->toHaveCount(2)
            ->and($preview['created'])->toBe(1);
        expect(fn () => app(ProductMediaCsv::class)->apply($path, $preview['fingerprint'], $this->csvActor))
            ->toThrow(ValidationException::class);
        expect(Product::query()->where('slug', $slug)->exists())->toBeFalse();
    } finally {
        unlink($path);
    }
});

it('runs the preview and apply actions inside the catalog permission area', function () {
    $owner = User::query()->create(['name' => 'Quản trị CSV', 'username' => 'media-owner-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    Storage::disk('upload_product')->putFileAs('', UploadedFile::fake()->image('import.jpg'), 'import.jpg');
    $product = Product::query()->firstOrFail();
    $row = mediaCsvRow($product);
    $row[5] = 'import.jpg';
    $path = mediaCsvFile([$row]);
    $upload = UploadedFile::fake()->createWithContent('media.csv', file_get_contents($path));
    unlink($path);

    Livewire\Livewire::test(ListProducts::class)->callAction('previewMedia', ['file' => $upload])->assertHasNoActionErrors();
    expect(session('product_media_csv_preview.updated'))->toBe(1);
    Livewire\Livewire::test(ListProducts::class)->callAction('downloadMediaPreview')
        ->assertFileDownloaded('pts-ket-qua-san-pham-media.csv');
    $relative = session('product_media_csv_preview.path');
    try {
        Livewire\Livewire::test(ListProducts::class)->callAction('applyMedia')->assertHasNoActionErrors();
        expect($product->fresh()->image)->toBe('import.jpg');
    } finally {
        if (is_string($relative)) {
            Storage::disk('local')->delete($relative);
        }
    }

    $this->flushSession();
    $outsider = User::query()->create(['name' => 'Biên tập nội dung', 'username' => 'media-out-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => ['content'], 'is_active' => true]);
    $this->actingAs($outsider, 'admin');
    $this->get('/admin/products')->assertForbidden();

    $viewer = User::query()->create(['name' => 'Xem sản phẩm', 'username' => 'media-view-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => ['catalog'], 'is_active' => true]);
    $this->flushSession();
    $this->actingAs($viewer, 'admin');
    $this->get('/admin/products')->assertOk();
    expect(ProductResource::canBulkImport())->toBeFalse();
    Livewire\Livewire::test(ListProducts::class)->assertActionHidden('uploadMediaBatch')->assertActionHidden('previewMedia')->assertActionHidden('applyMedia')
        ->assertActionHidden('previewPrices');

    $bulkEditor = User::query()->create(['name' => 'Nhập CSV', 'username' => 'media-bulk-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => ['catalog', 'catalog_bulk'], 'is_active' => true]);
    $this->flushSession();
    $this->actingAs($bulkEditor, 'admin');
    Livewire\Livewire::test(ListProducts::class)->assertActionVisible('uploadMediaBatch')->assertActionVisible('previewMedia')->assertActionVisible('previewPrices');
});

it('uploads a batch of real images and downloads the stored filenames for CSV mapping', function () {
    $this->actingAs($this->csvActor, 'admin');
    Livewire\Livewire::test(ListProducts::class)->callAction('uploadMediaBatch', data: [
        'images' => [UploadedFile::fake()->image('batch-one.jpg'), UploadedFile::fake()->image('batch-two.png')],
    ])->assertHasNoActionErrors()->assertFileDownloaded('pts-anh-san-pham-da-tai.csv');

    $files = Storage::disk('upload_product')->files();
    expect($files)->toHaveCount(2);
    foreach ($files as $file) {
        Storage::disk('upload_product')->assertExists($file);
    }
});
