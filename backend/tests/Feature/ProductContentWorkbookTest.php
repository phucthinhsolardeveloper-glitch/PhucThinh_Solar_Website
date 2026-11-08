<?php

use App\Domain\Catalog\ProductContentWorkbook;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpKernel\Exception\HttpException;

function contentWorkbookFile(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'pts-content-test-').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues(ProductContentWorkbook::headers()));
    foreach ($rows as $row) {
        $writer->addRow(Row::fromValues($row));
    }
    $writer->close();

    return $path;
}

function contentWorkbookRow(Product $product, array $edits = []): array
{
    $row = [$product->id, $product->code ?? '', $product->name];
    foreach (ProductContentWorkbook::COLUMNS as $header => [$field]) {
        $row[] = $edits[$header] ?? '';
    }

    return $row;
}

it('exports an XLSX template that imports unchanged without changing catalog data', function () {
    $service = app(ProductContentWorkbook::class);
    $response = $service->export();
    $path = tempnam(sys_get_temp_dir(), 'pts-content-roundtrip-').'.xlsx';
    copy($response->getFile()->getPathname(), $path);
    try {
        $preview = $service->preview($path);
        expect($preview['total'])->toBe(Product::query()->count())
            ->and($preview['changed'])->toBe(0)->and($preview['errors'])->toBe([]);
    } finally {
        unlink($path);
        unlink($response->getFile()->getPathname());
    }
});

it('exports legacy formula-like text as literal spreadsheet cells', function () {
    $product = Product::query()->firstOrFail();
    $product->update(['summary' => '=HYPERLINK("https://example.test", "text")']);
    $response = app(ProductContentWorkbook::class)->export();
    $zip = new ZipArchive;
    try {
        expect($zip->open($response->getFile()->getPathname()))->toBeTrue();
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        expect($xml)->toContain('=HYPERLINK')->not->toContain('<f>');
    } finally {
        $zip->close();
        unlink($response->getFile()->getPathname());
    }
});

it('updates selected product facts and SEO from XLSX while preserving price and URL', function () {
    $product = Product::query()->whereNotNull('category_id')->firstOrFail();
    $oldPrice = $product->regular_price;
    $oldSlug = $product->slug;
    $path = contentWorkbookFile([contentWorkbookRow($product, [
        'Mô tả ngắn' => 'Mô tả bán hàng mới', 'Công suất (W)' => '75.5',
        'Bảo hành' => '24 tháng', 'Tiêu đề SEO' => 'Tiêu đề SEO mới',
        'Mô tả SEO' => 'Mô tả SEO cho sản phẩm',
    ])]);
    $actor = User::query()->create(['name' => 'Chủ', 'username' => 'content-workbook-owner', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    try {
        $service = app(ProductContentWorkbook::class);
        $preview = $service->preview($path);
        expect($preview['changed'])->toBe(1)->and($preview['errors'])->toBe([]);
        $service->apply($path, $preview['fingerprint'], $actor);
        $saved = $product->fresh();
        expect($saved->summary)->toBe('Mô tả bán hàng mới')->and($saved->power_watts)->toBe('75.50')
            ->and($saved->warranty)->toBe('24 tháng')->and($saved->seo->title)->toBe('Tiêu đề SEO mới')
            ->and($saved->seo->description)->toBe('Mô tả SEO cho sản phẩm')
            ->and($saved->regular_price)->toBe($oldPrice)->and($saved->slug)->toBe($oldSlug);
    } finally {
        unlink($path);
    }
});

it('rejects bad rows, stale previews and unauthorized apply without partial writes', function () {
    $product = Product::query()->whereNotNull('category_id')->firstOrFail();
    $path = contentWorkbookFile([contentWorkbookRow($product, ['Tiêu đề SEO' => 'Tên SEO đang chờ'])]);
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'content-workbook-owner-2', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $viewer = User::query()->create(['name' => 'Xem', 'username' => 'content-workbook-viewer', 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['catalog'], 'is_active' => true]);
    try {
        $service = app(ProductContentWorkbook::class);
        $preview = $service->preview($path);
        expect(fn () => $service->apply($path, $preview['fingerprint'], $viewer))->toThrow(HttpException::class);
        $product->seo()->updateOrCreate([], ['title' => 'SEO vừa được sửa ở Admin']);
        expect(fn () => $service->apply($path, $preview['fingerprint'], $owner))->toThrow(ValidationException::class);
        expect($product->fresh()->seo->title)->toBe('SEO vừa được sửa ở Admin');
    } finally {
        unlink($path);
    }

    $bad = contentWorkbookFile([contentWorkbookRow($product, ['ID danh mục' => '999999999', 'Mô tả SEO' => '<script>bad</script>'])]);
    try {
        expect(app(ProductContentWorkbook::class)->preview($bad)['errors'])->not->toBe([]);
    } finally {
        unlink($bad);
    }
});

it('previews, reports and applies an XLSX through the product Admin actions', function () {
    $actor = User::query()->create(['name' => 'Chủ', 'username' => 'content-workbook-admin', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($actor, 'admin');
    $product = Product::query()->firstOrFail();
    $path = contentWorkbookFile([contentWorkbookRow($product, ['Tiêu đề SEO' => 'SEO nhập từ Admin'])]);
    $upload = UploadedFile::fake()->createWithContent('content.xlsx', file_get_contents($path));
    unlink($path);

    Livewire\Livewire::test(ListProducts::class)->callAction('exportContentWorkbook')->assertFileDownloaded();
    Livewire\Livewire::test(ListProducts::class)->callAction('previewContentWorkbook', ['file' => $upload])->assertHasNoActionErrors();
    expect(session('product_content_workbook_preview.changed'))->toBe(1);
    Livewire\Livewire::test(ListProducts::class)->callAction('downloadContentWorkbookPreview')
        ->assertFileDownloaded('pts-ket-qua-noi-dung-seo.csv');
    $relative = session('product_content_workbook_preview.path');
    try {
        Livewire\Livewire::test(ListProducts::class)->callAction('applyContentWorkbook')->assertHasNoActionErrors();
        expect($product->fresh()->seo->title)->toBe('SEO nhập từ Admin');
    } finally {
        if (is_string($relative)) {
            Storage::disk('local')->delete($relative);
        }
    }
});
