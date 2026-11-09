<?php

use App\Domain\Catalog\Bulk\BulkApplier;
use App\Domain\Catalog\Bulk\BulkCatalog;
use App\Domain\Catalog\Bulk\BulkColumns;
use App\Domain\Catalog\Bulk\BulkExporter;
use App\Domain\Catalog\Bulk\BulkImages;
use App\Domain\Catalog\Bulk\BulkPlanner;
use App\Domain\Catalog\Bulk\BulkReader;
use App\Domain\Catalog\Bulk\BulkText;
use App\Domain\Catalog\MerchantFeed;
use App\Filament\Pages\ProductBulkPage;
use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

function bulkRow(array $cells, int $row = 2): array
{
    return ['_row' => (string) $row] + $cells;
}

function bulkPlan(array $rows, array $baseline = [], array $choices = [], bool $strict = false): array
{
    return (new BulkPlanner(new BulkCatalog, $choices, $strict))->plan($rows, $baseline);
}

function sampleProduct(): Product
{
    return Product::query()->where('is_visible', true)->whereNotNull('code')->where('code', '!=', '')->where('regular_price', '>', 0)->firstOrFail();
}

function leafCategory(): string
{
    return (new BulkCatalog(collect()))->categoryPaths[Product::query()->value('category_id')];
}

function exportedBook(): string
{
    $response = (new BulkExporter)->xlsx(new BulkCatalog(Product::query()->with(['seo', 'images', 'contentSection'])->limit(5)->get()), 'x');
    $path = tempnam(sys_get_temp_dir(), 'book').'.xlsx';
    copy($response->getFile()->getPathname(), $path);

    return $path;
}

it('exports every column with pick lists, a locked ID, a guide and a hidden snapshot', function () {
    $book = IOFactory::load(exportedBook());
    $sheet = $book->getSheetByName('SanPham');
    expect($book->getSheetNames())->toBe(['SanPham', 'DanhMuc', 'HuongDan', '_HeThong'])
        ->and($book->getSheetByName('_HeThong')->getSheetState())->toBe('veryHidden')
        ->and(count($sheet->toArray()[0]))->toBe(count(BulkColumns::exported()))
        ->and(count(BulkColumns::exported()))->toBeGreaterThan(45)
        ->and($sheet->getProtection()->getSheet())->toBeTrue()
        ->and($sheet->getDataValidationCollection())->not->toBeEmpty();
});

it('finds nothing to change when an exported file comes straight back', function () {
    $read = (new BulkReader)->read(exportedBook(), 'san-pham.xlsx');
    $plan = bulkPlan($read['rows'], $read['baseline']);
    $noisy = collect($plan['rows'])->filter(fn ($r) => $r['errors'] !== [] || $r['changes'] !== [])->map(fn ($r) => [$r['row'], $r['errors'], array_keys($r['changes'])])->values()->all();
    expect($noisy)->toBe([])->and($plan['counts']['unchanged'])->toBe(5);
});

it('reads files issued by the old site, including its headers', function () {
    $file = UploadedFile::fake()->createWithContent('cu.csv', "\xEF\xBB\xBFID sản phẩm;Giá gốc;Bán chạy (1/0);Thông số khác (Tên | Giá trị)\n5;\"1.235.000\";1;A | B\n");
    $rows = (new BulkReader)->read($file->getPathname(), 'cu.csv')['rows'];
    expect($rows[0])->toMatchArray(['id' => '5', 'regular_price' => '1.235.000', 'is_bestseller' => '1', 'additional_specs' => 'A | B']);

    $bad = UploadedFile::fake()->createWithContent('x.csv', "ID sản phẩm,Cột lạ\n1,2\n");
    expect(fn () => (new BulkReader)->read($bad->getPathname(), 'x.csv'))->toThrow(ValidationException::class, 'Cột không được hỗ trợ');
});

it('keeps blanks, clears on request and drops values that did not change', function () {
    $p = sampleProduct();
    $p->update(['warranty' => '24 tháng', 'material' => 'Nhôm']);
    $plan = bulkPlan([bulkRow(['id' => (string) $p->id, 'material' => '', 'warranty' => BulkColumns::CLEAR, 'regular_price' => number_format((float) $p->regular_price, 0, ',', '.').' đ'])]);
    expect($plan['errors'])->toBe(0)->and($plan['rows'][0]['columns'])->toBe(['warranty' => null])
        ->and(bulkPlan([bulkRow(['id' => (string) $p->id, 'code' => BulkColumns::CLEAR])])['errors'])->toBe(1);
});

it('reports a conflict when the site changed after export and overwrites only on request', function () {
    $p = sampleProduct();
    $p->update(['warranty' => 'Trên website']);
    $row = [bulkRow(['id' => (string) $p->id, 'warranty' => 'Trong Excel'])];
    $base = [$p->id => ['id' => (string) $p->id, 'warranty' => 'Lúc xuất']];

    $plan = bulkPlan($row, $base);
    expect($plan['conflicts'])->toBe(1)->and($plan['rows'][0]['columns'])->toBe([]);
    $key = $plan['rows'][0]['conflicts'][0]['key'];
    expect(bulkPlan($row, $base, ['overwrite' => [$key => true]])['rows'][0]['columns'])->toBe(['warranty' => 'Trong Excel'])
        ->and(bulkPlan([bulkRow(['id' => (string) $p->id, 'warranty' => 'Lúc xuất'])], $base)['rows'][0]['columns'])->toBe([]);
});

it('creates a product with a new brand only after a choice, and rolls everything back on failure', function () {
    Storage::fake('upload_product');
    $row = [bulkRow(['code' => 'XL-NEW-1', 'name' => 'Đèn Excel Mới', 'category' => leafCategory(), 'brand' => 'Hãng Chưa Có', 'regular_price' => '1000000', 'sale_price' => '900000', 'highlights' => 'Sáng | Rất sáng', 'sections' => "## Giới thiệu\n\n[faq] Bảo hành? | 2 năm", 'seo_title' => 'SEO mới'])];
    expect(fn () => (new BulkApplier)->apply($row, [], [], false))->toThrow(ValidationException::class, 'Chưa chọn');
    expect(Product::query()->where('code', 'XL-NEW-1')->exists())->toBeFalse();

    $key = array_key_first(bulkPlan($row)['new_brands']);
    $counts = (new BulkApplier)->apply($row, [], ['brands' => [$key => 'create']], false);
    $made = Product::query()->where('code', 'XL-NEW-1')->firstOrFail();
    expect($counts['create'])->toBe(1)->and($counts['brand_create'])->toBe(1)
        ->and($made->slug)->toBe('den-excel-moi')->and(Brand::query()->find($made->brand_id)?->name)->toBe('Hãng Chưa Có')
        ->and($made->seo->title)->toBe('SEO mới')
        ->and(json_decode(DB::table('content_sections')->where('owner_id', $made->id)->where('owner_type', Product::class)->value('blocks'), true)[1]['type'])->toBe('faq');
});

it('blocks bad data row by row and suggests the right picture name', function () {
    Storage::fake('upload_product');
    Storage::disk('upload_product')->put('den-pha-100w.png', 'x');
    $p = sampleProduct();
    $other = Product::query()->where('is_visible', true)->whereNotNull('code')->where('code', '!=', '')->whereKeyNot($p->id)->firstOrFail();
    $errors = fn (array $cells) => bulkPlan([bulkRow($cells)])['rows'][0]['errors'];

    expect($errors(['id' => (string) $p->id, 'image' => 'den-pha-100w.jpg'])[0])->toContain('den-pha-100w.png')
        ->and($errors(['id' => (string) $p->id, 'regular_price' => '100', 'sale_price' => '200'])[0])->toContain('khuyến mãi')
        ->and($errors(['id' => (string) $p->id, 'video_url' => 'https://evil.test/x'])[0])->toContain('YouTube')
        ->and($errors(['id' => (string) $p->id, 'code' => $other->code])[0])->toContain('khác nhau')
        ->and($errors(['action' => 'update', 'code' => $p->code])[0])->toContain('bắt buộc có ID')
        ->and($errors(['id' => (string) $p->id, 'additional_specs' => 'Hãng công bố | 5 năm'])[0])->toContain('không nhận nhãn')
        ->and($errors(['code' => 'NEW-2', 'name' => 'Thiếu danh mục'])[0])->toContain('Danh mục');
    expect(bulkPlan([bulkRow(['id' => (string) $p->id]), bulkRow(['id' => (string) $p->id], 3)])['errors'])->toBe(1);
});

it('asks what to do when a new row has the slug of an existing product', function () {
    $p = sampleProduct();
    $row = [bulkRow(['code' => 'XL-DUP', 'name' => 'Khác', 'slug' => $p->slug, 'category' => leafCategory()])];
    $plan = bulkPlan($row);
    $key = array_key_first($plan['slug_matches']);
    expect($plan['errors'])->toBe(0)
        ->and(bulkPlan($row, [], ['products' => [$key => 'update:'.$p->id]])['rows'][0]['id'])->toBe($p->id)
        ->and(bulkPlan($row, [], ['products' => [$key => 'create']])['rows'][0]['columns']['slug'])->toBe($p->slug.'-2')
        ->and(bulkPlan($row, [], ['products' => [$key => 'skip']])['counts']['skip'])->toBe(1)
        ->and(bulkPlan($row, strict: true)['errors'])->toBeGreaterThan(0);
});

it('needs a separate confirmation before hiding products', function () {
    $p = sampleProduct();
    $row = [bulkRow(['id' => (string) $p->id, 'action' => 'ẩn'])];
    expect(fn () => (new BulkApplier)->apply($row, [], [], false))->toThrow(ValidationException::class, 'sẽ bị ẩn');
    (new BulkApplier)->apply($row, [], [], true);
    expect($p->fresh()->is_visible)->toBeFalse()->and(Product::query()->whereKey($p->id)->exists())->toBeTrue();
});

it('round-trips descriptions and specs without losing a separator', function () {
    $md = "## Tiêu đề\n\nĐoạn văn\n\n- Một\n- Hai\n\n[faq] Hỏi? | Đáp\n\n![Ảnh](a.jpg)";
    expect(BulkText::blocksToMarkdown(BulkText::markdownToBlocks($md)))->toBe($md);
    $specs = json_encode([['key' => 'ac', 'label' => 'Điện áp', 'value' => '110 | 220V'], ['key' => '', 'label' => 'Cân', 'value' => '<105 kg']], JSON_UNESCAPED_UNICODE);
    $parsed = BulkText::parseSpecs(BulkText::specsFrom($specs), false);
    expect($parsed['errors'])->toBe([])->and($parsed['specs'][0]['value'])->toBe('110 | 220V')->and($parsed['specs'][1]['value'])->toBe('<105 kg')
        ->and(BulkText::money('1.235.000 đ'))->toBe(1235000)->and(BulkText::money('abc'))->toBeNull();
});

function bulkOwner(array $permissions = []): User
{
    return User::query()->create(['name' => 'Thử', 'username' => 'wb-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => $permissions === [] ? User::ROLE_OWNER : User::ROLE_EDITOR, 'permissions' => $permissions, 'is_active' => true]);
}

it('previews and applies a file on the admin page, and keeps it from people without the bulk permission', function () {
    Storage::fake('local');
    $this->actingAs(bulkOwner(), 'admin');
    $p = sampleProduct();
    Storage::disk('local')->put('bulk-uploads/a.csv', "ID sản phẩm,Bảo hành\n{$p->id},36 tháng thử\n");

    $page = Livewire\Livewire::test(ProductBulkPage::class)
        ->callAction('previewFile', ['file' => ['bulk-uploads/a.csv'], 'file_name' => 'a.csv']);
    expect($page->get('token'))->not->toBeNull()->and($p->fresh()->warranty)->not->toBe('36 tháng thử');
    $page->assertSee('Xem trước')->call('showRows', 'changes')->assertSee('36 tháng thử')
        ->callAction('apply')->assertHasNoActionErrors();
    expect($p->fresh()->warranty)->toBe('36 tháng thử')->and($page->get('token'))->toBeNull()
        ->and(DB::table('admin_audit_events')->where('action', 'product.bulk_import')->exists())->toBeTrue();

    $this->flushSession();
    $this->actingAs(bulkOwner(['catalog']), 'admin');
    $this->get('/admin/nhap-xuat-san-pham')->assertForbidden();
});

it('adds pictures from a zip named by product code and never replaces a main picture', function () {
    Storage::fake('local');
    Storage::fake('upload_product');
    $p = sampleProduct();
    $p->update(['code' => 'ZIP-A1', 'image' => 'co-san.jpg']);
    $empty = Product::query()->whereKeyNot($p->id)->firstOrFail();
    $empty->update(['code' => 'ZIP-B2', 'image' => null]);
    $jpg = UploadedFile::fake()->image('x.jpg', 2400, 1200)->get();
    $zipPath = tempnam(sys_get_temp_dir(), 'z');
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::OVERWRITE);
    foreach (['ZIP-A1-01.jpg', 'ZIP-A1-02.jpg', 'ZIP-B2-01.jpg', 'KHONG-CO-01.jpg'] as $name) {
        $zip->addFromString($name, $jpg);
    }
    $zip->close();

    $images = app(BulkImages::class);
    $preview = $images->preview($zipPath, 'anh.zip');
    expect($preview['counts'])->toMatchArray(['ready' => 2, 'ready_main' => 1, 'ready_gallery' => 1, 'existing' => 1, 'missing_product' => 1, 'error' => 0]);
    expect($images->apply($preview))->toBe(2)
        ->and($p->fresh()->image)->toBe('co-san.jpg')->and($empty->fresh()->image)->toBe('ZIP-B2-01.jpg')
        ->and($p->images()->where('path', 'ZIP-A1-02.jpg')->exists())->toBeTrue()
        ->and(max(getimagesize(Storage::disk('upload_product')->path('ZIP-B2-01.jpg'))[0], 0))->toBe(1920);
});

it('sends Google only products that meet every condition', function () {
    Storage::fake('upload_product');
    Storage::disk('upload_product')->put('sach.jpg', UploadedFile::fake()->image('s.jpg', 800, 800)->get());
    Storage::disk('upload_product')->put('nho.jpg', UploadedFile::fake()->image('n.jpg', 200, 200)->get());
    DB::table('products')->update(['feed_image' => null]);
    $ok = sampleProduct();
    $ok->update(['code' => 'FEED-1', 'feed_image' => 'sach.jpg', 'regular_price' => 1_000_000, 'sale_price' => 900_000, 'quote_only' => false]);
    $small = Product::query()->where('is_visible', true)->whereKeyNot($ok->id)->firstOrFail();
    $small->update(['code' => 'FEED-2', 'feed_image' => 'nho.jpg', 'regular_price' => 500_000]);
    $quote = Product::query()->where('is_visible', true)->whereNotIn('id', [$ok->id, $small->id])->firstOrFail();
    $quote->update(['code' => 'FEED-3', 'feed_image' => 'sach.jpg', 'regular_price' => 500_000, 'quote_only' => true]);

    $rows = app(MerchantFeed::class)->build('https://phucthinhsolar.com')['rows'];
    expect($rows)->toHaveCount(1)->and($rows[0][0])->toBe('FEED-1')->and($rows[0][4])->toBe('https://phucthinhsolar.com/upload/product/sach.jpg')
        ->and($rows[0][6])->toBe('1000000 VND')->and($rows[0][7])->toBe('900000 VND');
});
