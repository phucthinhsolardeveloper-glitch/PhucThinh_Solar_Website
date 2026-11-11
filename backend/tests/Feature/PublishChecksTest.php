<?php

use App\Domain\Content\PostRevisions;
use App\Domain\Content\PublishChecks;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function publishOwner(): User
{
    return User::query()->create(['name' => 'Chủ', 'username' => 'pub-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

it('saves a product switched on with a blocking error but keeps it off the website, and says why', function () {
    $this->actingAs(publishOwner(), 'admin');
    $product = Product::query()->where('is_visible', false)->first() ?? tap(Product::query()->firstOrFail())->update(['is_visible' => false]);
    $product->update(['image' => null, 'image_secondary' => null]);
    $product->images()->delete();

    Livewire::test(EditProduct::class, ['record' => $product->getKey()])
        ->fillForm(['is_visible' => true, 'summary' => 'Tóm tắt mới'])->call('save')->assertHasNoFormErrors()
        ->assertNotified('Đã lưu nhưng CHƯA bật hiển thị');
    expect($product->fresh()->is_visible)->toBeFalse()->and($product->fresh()->summary)->toBe('Tóm tắt mới');
});

it('refuses the list switch for a product that is not ready and allows a ready one', function () {
    $this->actingAs(publishOwner(), 'admin');
    $ready = Product::query()->where('is_visible', true)->get()->first(fn (Product $p) => app(PublishChecks::class)->product($p)['errors'] === []);
    $broken = Product::query()->where('id', '<>', $ready->id)->firstOrFail();
    $broken->update(['is_visible' => false, 'category_id' => null]);
    $ready->update(['is_visible' => false]);

    Livewire::test(ListProducts::class)->call('updateTableColumnState', 'is_visible', (string) $broken->getKey(), true)
        ->assertNotified('Chưa bật được hiển thị');
    expect($broken->fresh()->is_visible)->toBeFalse();
    Livewire::test(ListProducts::class)->call('updateTableColumnState', 'is_visible', (string) $ready->getKey(), true);
    expect($ready->fresh()->is_visible)->toBeTrue();
});

it('lists what a post still needs and blocks publishing a draft without it', function () {
    $checks = app(PublishChecks::class)->post(['kind' => 'article', 'title' => '', 'slug' => '', 'body' => '<p>Ngắn</p>', 'image' => '', 'summary' => '']);
    expect($checks['errors'])->toContain('Chưa có tiêu đề', 'Chưa có đường dẫn', 'Chưa có ảnh đại diện')
        ->and($checks['warnings'])->toContain('Nội dung ngắn (dưới 300 ký tự)', 'Chưa có mô tả SEO');

    $taken = Post::query()->where('kind', 'article')->firstOrFail();
    $other = Post::query()->where('kind', 'project')->firstOrFail();
    expect(app(PublishChecks::class)->post(['kind' => 'project', 'slug' => $taken->slug] + PublishChecks::postData($other), $other->id)['errors'])
        ->toContain('Đường dẫn trùng với một trang khác trên website');

    $owner = publishOwner();
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $workflow = app(PostRevisions::class);
    $draft = $workflow->draft($post, $owner);
    $draft->update(['payload' => ['image' => ''] + $draft->payload]);
    expect(fn () => $workflow->publish($draft->fresh(), $owner))->toThrow(ValidationException::class, 'Chưa có ảnh đại diện');
});
