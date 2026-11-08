<?php

use App\Domain\Catalog\ProductPriceCsv;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->csvActor = User::query()->create(['name' => 'Quản trị CSV', 'username' => 'csv-service-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
});

function priceCsvFile(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'pts-price-');
    $out = fopen($path, 'w');
    fputcsv($out, ProductPriceCsv::HEADERS, ',', '"', '');
    foreach ($rows as $row) {
        fputcsv($out, $row, ',', '"', '');
    }
    fclose($out);

    return $path;
}

function priceCsvRow(Product $product): array
{
    return [$product->id, $product->code, $product->name, $product->regular_price, $product->sale_price, (int) $product->quote_only, (int) $product->is_visible];
}

it('exports then imports an unchanged file without changing products', function () {
    $service = app(ProductPriceCsv::class);
    ob_start();
    $service->export()->sendContent();
    $csv = ob_get_clean();
    $path = tempnam(sys_get_temp_dir(), 'pts-price-');
    file_put_contents($path, $csv);
    try {
        $preview = $service->preview($path);
        expect($preview['total'])->toBe(Product::query()->count())
            ->and($preview['changed'])->toBe(0)
            ->and($preview['errors'])->toBe([]);
        $applied = $service->apply($path, $preview['fingerprint'], $this->csvActor);
        expect($applied['changed'])->toBe(0);
    } finally {
        unlink($path);
    }
});

it('rejects a header-only CSV', function () {
    $path = priceCsvFile([]);
    try {
        expect(fn () => app(ProductPriceCsv::class)->preview($path))->toThrow(ValidationException::class);
    } finally {
        unlink($path);
    }
});

it('previews and applies only edited fields and treats blanks as unchanged', function () {
    $product = Product::query()->whereNull('sale_price')->firstOrFail();
    $before = $product->only(['sale_price', 'quote_only', 'image', 'name']);
    $row = priceCsvRow($product);
    $row[3] = '1.250.000 đ';
    $row[4] = '';
    $row[5] = '';
    $row[6] = '0';
    $path = priceCsvFile([$row]);
    try {
        $service = app(ProductPriceCsv::class);
        $preview = $service->preview($path);
        expect($preview['total'])->toBe(1)->and($preview['changed'])->toBe(1)->and($preview['errors'])->toBe([]);
        $service->apply($path, $preview['fingerprint'], $this->csvActor);
        $product->refresh();
        expect((string) $product->regular_price)->toBe('1250000')
            ->and($product->is_visible)->toBeFalse()
            ->and($product->sale_price)->toBe($before['sale_price'])
            ->and($product->quote_only)->toBe($before['quote_only'])
            ->and($product->image)->toBe($before['image'])
            ->and($product->name)->toBe($before['name']);
    } finally {
        unlink($path);
    }
});

it('rejects bad rows, duplicate IDs and changes since preview without partial writes', function () {
    $products = Product::query()->limit(2)->get();
    [$first, $second] = [$products[0], $products[1]];
    $good = priceCsvRow($first);
    $good[3] = '99999999999999';
    $bad = priceCsvRow($second);
    $bad[3] = 'không phải giá';
    $path = priceCsvFile([$good, $bad, $good]);
    try {
        $service = app(ProductPriceCsv::class);
        $preview = $service->preview($path);
        expect($preview['errors'])->toHaveCount(2);
        try {
            $service->apply($path, $preview['fingerprint'], $this->csvActor);
            test()->fail('CSV lỗi phải bị chặn.');
        } catch (ValidationException) {
            expect($first->fresh()->regular_price)->toBe($first->regular_price);
        }
    } finally {
        unlink($path);
    }

    $path = priceCsvFile([$good]);
    try {
        $preview = $service->preview($path);
        $first->update(['regular_price' => 999999]);
        $this->expectException(ValidationException::class);
        $service->apply($path, $preview['fingerprint'], $this->csvActor);
    } finally {
        unlink($path);
    }
});

it('shows the CSV actions only inside the authorized product Admin page', function () {
    $owner = User::query()->create(['name' => 'Quản trị CSV', 'username' => 'csv-owner-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    Livewire\Livewire::test(ListProducts::class)
        ->callAction('exportPrices')
        ->assertFileDownloaded();
    Livewire\Livewire::test(ListProducts::class)
        ->assertActionExists('exportPrices')
        ->assertActionExists('previewPrices')
        ->assertActionExists('applyPrices');

    $this->flushSession();
    $editor = User::query()->create(['name' => 'Biên tập CSV', 'username' => 'csv-editor-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['content'], 'is_active' => true]);
    $this->actingAs($editor, 'admin');
    $this->get('/admin/products')->assertForbidden();
});

it('previews and commits a CSV through the Filament actions', function () {
    $owner = User::query()->create(['name' => 'Quản trị CSV', 'username' => 'csv-action-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $product = Product::query()->whereNull('sale_price')->firstOrFail();
    $row = priceCsvRow($product);
    $row[3] = '1250000';
    $path = priceCsvFile([$row]);
    $upload = UploadedFile::fake()->createWithContent('prices.csv', file_get_contents($path));
    unlink($path);

    Livewire\Livewire::test(ListProducts::class)
        ->callAction('previewPrices', ['file' => $upload])
        ->assertHasNoActionErrors();
    expect(session('product_price_csv_preview.changed'))->toBe(1);
    Livewire\Livewire::test(ListProducts::class)
        ->callAction('downloadPricePreview')
        ->assertFileDownloaded('pts-ket-qua-xem-truoc-gia.csv');
    $relative = session('product_price_csv_preview.path');
    try {
        Livewire\Livewire::test(ListProducts::class)
            ->callAction('applyPrices')
            ->assertHasNoActionErrors();
        expect((string) $product->fresh()->regular_price)->toBe('1250000');
    } finally {
        if (is_string($relative)) {
            Storage::disk('local')->delete($relative);
        }
    }
});
