<?php

use App\Domain\Content\SectionHistory;
use App\Domain\Content\SectionTransfer;
use App\Filament\Resources\ContentSections\ContentSectionResource;
use App\Filament\Resources\ContentSections\Pages\ManageContentSections;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Brand;
use App\Models\ContentSection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function sectionAdmin(): User
{
    return User::query()->create(['name' => 'Thử', 'username' => 'cs-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

function blocksOf(ContentSection $section): array
{
    return json_decode((string) $section->fresh()->getRawOriginal('blocks'), true);
}

it('keeps every earlier state of a description and restores any of them, even after deletion', function () {
    $section = ContentSection::query()->where('owner_type', Product::class)->firstOrFail();
    $original = blocksOf($section);
    $section->update(['blocks' => [['type' => 'text', 'data' => ['text' => 'Bản sửa 1']]]]);
    $section->update(['blocks' => [['type' => 'text', 'data' => ['text' => 'Bản sửa 2']]]]);
    $versions = ContentSectionResource::versions(Product::class, (int) $section->owner_id);
    expect($versions)->toHaveCount(2);

    SectionHistory::restore(array_key_last($versions));
    expect(blocksOf($section))->toBe($original)->and(ContentSectionResource::versions(Product::class, (int) $section->owner_id))->toHaveCount(3);

    $owner = [$section->owner_type, $section->owner_id];
    $section->delete();
    $deleted = DB::table('content_section_versions')->where('owner_type', $owner[0])->where('owner_id', $owner[1])->where('reason', 'delete')->value('id');
    SectionHistory::restore((int) $deleted);
    expect(json_decode(DB::table('content_sections')->where('owner_type', $owner[0])->where('owner_id', $owner[1])->value('blocks'), true))->toBe($original);
});

it('exports brand and category descriptions and imports them back, also from files of the old site', function () {
    $brand = Brand::query()->whereHas('contentSection')->firstOrFail();
    $file = app(SectionTransfer::class)->export();
    ob_start();
    $file->sendContent();
    $json = (string) ob_get_clean();
    $items = json_decode($json, true)['items'];
    expect(collect($items)->pluck('scope')->unique()->sort()->values()->all())->toBe(['brand', 'category']);

    $transfer = app(SectionTransfer::class);
    expect($transfer->preview($json)['counts']['create'] + $transfer->preview($json)['counts']['update'])->toBe(0);

    $legacy = json_encode(['items' => [
        ['scope' => 'brand', 'scope_name' => $brand->name, 'blocks' => [['type' => 'heading', 'text' => 'Từ tệp cũ'], ['type' => 'script', 'text' => 'x']]],
        ['scope' => 'cat', 'scope_name' => 'Không có danh mục này', 'blocks' => [['type' => 'text', 'text' => 'x']]],
        ['scope' => 'product', 'scope_name' => 'x', 'blocks' => []],
    ]]);
    $preview = $transfer->preview($legacy);
    expect(collect($preview['rows'])->pluck('status')->all())->toBe(['update', 'error', 'skip']);
    expect($transfer->apply($preview))->toBe(1)
        ->and(json_decode(DB::table('content_sections')->where('owner_type', Brand::class)->where('owner_id', $brand->id)->value('blocks'), true))->toBe([['type' => 'heading', 'text' => 'Từ tệp cũ']])
        ->and(ContentSectionResource::versions(Brand::class, $brand->id))->not->toBeEmpty();
});

it('turns old rich text into blocks on the product page', function () {
    $this->actingAs(sectionAdmin(), 'admin');
    $product = Product::query()->firstOrFail();
    $product->update(['body' => '<h2>Giới thiệu</h2><p>Đoạn một.</p><p>Đoạn hai.</p><ul><li>Ý A</li><li>Ý B</li></ul><img src="/upload/product/a.jpg" alt="Ảnh"><img src="https://x.test/b.jpg">']);
    expect(SectionTransfer::htmlToBlocks($product->body))->toBe(['blocks' => [
        ['type' => 'heading', 'text' => 'Giới thiệu'], ['type' => 'text', 'text' => "Đoạn một.\n\nĐoạn hai."],
        ['type' => 'list', 'ordered' => false, 'items' => ['Ý A', 'Ý B']], ['type' => 'image', 'photo' => 'a.jpg', 'caption' => 'Ảnh'],
    ], 'skipped_images' => 1]);

    Livewire\Livewire::test(EditProduct::class, ['record' => $product->id])->callAction('htmlToBlocks', ['placement' => 'replace'])->assertHasNoActionErrors();
    expect(json_decode(DB::table('content_sections')->where('owner_type', Product::class)->where('owner_id', $product->id)->value('blocks'), true)[0])
        ->toBe(['type' => 'heading', 'text' => 'Giới thiệu']);
});

it('lists descriptions with coverage for staff with the catalog permission', function () {
    $this->actingAs(sectionAdmin(), 'admin');
    Livewire\Livewire::test(ManageContentSections::class)->assertOk()->assertSee('sản phẩm đang hiện');
});
