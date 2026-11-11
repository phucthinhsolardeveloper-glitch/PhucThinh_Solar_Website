<?php

use App\Domain\Content\PostRevisions;
use App\Domain\Content\PublishChecks;
use App\Filament\Resources\PostRevisions\Pages\EditPostRevision;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

// A stored picture name whose file is gone from the upload disk must survive a save that did not
// touch it; only an editor removing or replacing it may change it, and the publish checks still
// treat the missing file as missing.

function mediaOwner(): User
{
    return User::query()->create(['name' => 'Chủ thử ảnh', 'username' => 'media-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

/** A file name that exists on no disk, real or fake. */
function lostFile(string $label, string $ext = 'jpg'): string
{
    return 'mat-file-'.$label.'-'.bin2hex(random_bytes(5)).'.'.$ext;
}

/** A product whose main, secondary and gallery pictures all point at files that are not on the disk. */
function productWithLostPictures(): array
{
    $product = Product::query()->firstOrFail();
    $names = ['image' => lostFile('chinh'), 'image_secondary' => lostFile('phu'), 'feed_image' => lostFile('feed')];
    $product->update($names);
    $product->images()->delete();
    $gallery = [lostFile('thu-vien-1'), lostFile('thu-vien-2'), lostFile('thu-vien-3')];
    foreach ($gallery as $i => $path) {
        $product->images()->create(['path' => $path, 'caption' => null, 'sort_order' => $i, 'is_visible' => true]);
    }

    return [$product->fresh(), $names, $gallery];
}

/** A public project whose pictures and gallery point at files that are not on the disk. */
function projectWithLostPictures(): array
{
    $post = Post::query()->where('kind', 'project')->where('is_visible', true)->firstOrFail();
    $names = ['image' => lostFile('dai-dien', 'png'), 'cover_image' => lostFile('bia', 'png')];
    $post->update($names);
    $post->seo()->updateOrCreate([], ['image' => lostFile('seo', 'png')]);
    $post->media()->delete();
    $gallery = [lostFile('cong-trinh-1', 'png'), lostFile('cong-trinh-2', 'png')];
    foreach ($gallery as $i => $path) {
        $post->media()->create(['kind' => 'image', 'path' => $path, 'sort_order' => $i, 'is_visible' => true]);
    }

    return [$post->fresh(), $names, $gallery];
}

function productGallery(Product $product): array
{
    return DB::table('product_images')->where('product_id', $product->id)->orderBy('sort_order')->orderBy('id')->pluck('path')->all();
}

beforeEach(function () {
    // Empty upload disks: whatever the shared upload volume holds, every stored name above is missing.
    Storage::fake('upload_product');
    Storage::fake('upload_news');
});

it('keeps product pictures whose files are missing when the product is saved untouched', function () {
    $this->actingAs(mediaOwner(), 'admin');
    [$product, $names, $gallery] = productWithLostPictures();

    Livewire::test(EditProduct::class, ['record' => $product->id])
        ->assertSee('Không còn file trên máy chủ: '.$names['image'])
        ->call('save')->assertHasNoFormErrors();

    expect($product->fresh()->only(array_keys($names)))->toBe($names)
        ->and(productGallery($product))->toBe($gallery);
});

it('lets an editor clear or replace a missing product picture and remove a gallery item', function () {
    $this->actingAs(mediaOwner(), 'admin');
    [$product, $names, $gallery] = productWithLostPictures();

    $page = Livewire::test(EditProduct::class, ['record' => $product->id]);
    $items = $page->get('data.images');
    $dropped = array_key_first($items);
    unset($items[$dropped]);
    $page->fillForm(['image' => null, 'image_secondary' => [UploadedFile::fake()->image('anh phu moi.jpg', 600, 600)]])
        ->set('data.images', $items)
        ->call('save')->assertHasNoFormErrors();

    $fresh = $product->fresh();
    expect($fresh->image)->toBeNull()
        ->and($fresh->feed_image)->toBe($names['feed_image'])
        ->and($fresh->image_secondary)->not->toBe($names['image_secondary'])->toStartWith('anh-phu-moi-')->toEndWith('.jpg')
        ->and(Storage::disk('upload_product')->exists($fresh->image_secondary))->toBeTrue()
        ->and(productGallery($product))->toBe(array_slice($gallery, 1));
});

it('still refuses to show a product whose kept pictures are all missing', function () {
    $this->actingAs(mediaOwner(), 'admin');
    [$product, $names, $gallery] = productWithLostPictures();
    $product->update(['is_visible' => false]);

    expect(app(PublishChecks::class)->product($product->fresh())['errors'])->toContain('Sản phẩm đang hiện nhưng không có ảnh nào');
    Livewire::test(ListProducts::class)->call('updateTableColumnState', 'is_visible', (string) $product->id, true)
        ->assertNotified('Chưa bật được hiển thị');
    Livewire::test(EditProduct::class, ['record' => $product->id])
        ->fillForm(['is_visible' => true])->call('save')->assertHasNoFormErrors()
        ->assertNotified('Đã lưu nhưng CHƯA bật hiển thị');

    expect($product->fresh()->is_visible)->toBeFalse()
        ->and($product->fresh()->image)->toBe($names['image'])
        ->and(productGallery($product))->toBe($gallery);
});

it('keeps project pictures and gallery whose files are missing on an untouched direct save', function () {
    $this->actingAs(mediaOwner(), 'admin');
    [$post, $names, $gallery] = projectWithLostPictures();
    $seoImage = $post->seo->image;

    Livewire::test(EditProject::class, ['record' => $post->id])->call('save')->assertHasNoFormErrors();

    expect($post->fresh()->only(array_keys($names)))->toBe($names)
        ->and($post->seo()->value('image'))->toBe($seoImage)
        ->and($post->media()->orderBy('sort_order')->pluck('path')->all())->toBe($gallery);
});

it('keeps draft pictures and gallery whose files are missing when the draft is saved untouched', function () {
    $owner = mediaOwner();
    $this->actingAs($owner, 'admin');
    [$post, $names, $gallery] = projectWithLostPictures();
    $draft = app(PostRevisions::class)->draft($post, $owner);
    $before = $draft->payload;
    expect(array_column($before['media'], 'path'))->toBe($gallery);

    Livewire::test(EditPostRevision::class, ['record' => $draft->id])
        ->assertSee('Không còn file trên máy chủ: '.$names['cover_image'])
        ->call('save')->assertHasNoFormErrors();

    expect($draft->fresh()->payload)->toBe($before);
});

it('lets an editor clear or replace a missing draft picture and remove a gallery item', function () {
    $owner = mediaOwner();
    $this->actingAs($owner, 'admin');
    [$post, $names, $gallery] = projectWithLostPictures();
    $draft = app(PostRevisions::class)->draft($post, $owner);

    $page = Livewire::test(EditPostRevision::class, ['record' => $draft->id]);
    $items = $page->get('data.payload.media');
    unset($items[array_key_last($items)]);
    $page->fillForm(['payload.cover_image' => null, 'payload.image' => [UploadedFile::fake()->image('anh dai dien moi.png', 800, 600)]])
        ->set('data.payload.media', $items)
        ->call('save')->assertHasNoFormErrors();

    $payload = $draft->fresh()->payload;
    expect($payload['cover_image'])->toBeNull()
        ->and($payload['image'])->not->toBe($names['image'])->toStartWith('anh-dai-dien-moi-')
        ->and(Storage::disk('upload_news')->exists($payload['image']))->toBeTrue()
        ->and(array_column($payload['media'], 'path'))->toBe(array_slice($gallery, 0, -1))
        ->and($post->fresh()->only(array_keys($names)))->toBe($names);
});

it('warns about a kept missing draft picture, and refuses to publish once the editor clears it', function () {
    $owner = mediaOwner();
    $this->actingAs($owner, 'admin');
    [$post, $names] = projectWithLostPictures();
    $workflow = app(PostRevisions::class);

    $checks = app(PublishChecks::class)->post(PublishChecks::postData($post) + ['kind' => 'project'], $post->id);
    expect($checks['warnings'])->toContain('Ảnh đại diện không tìm thấy trên máy chủ')
        ->and($checks['errors'])->not->toContain('Chưa có ảnh đại diện');

    $draft = $workflow->draft($post, $owner);
    Livewire::test(EditPostRevision::class, ['record' => $draft->id])
        ->fillForm(['payload.image' => null])->call('save')->assertHasNoFormErrors();
    expect(fn () => $workflow->publish(PostRevision::query()->findOrFail($draft->id), $owner))
        ->toThrow(ValidationException::class, 'Chưa có ảnh đại diện');
    expect($post->fresh()->image)->toBe($names['image']);
});
