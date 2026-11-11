<?php

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Reviews\ReviewModeration;
use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

function reviewEditor(array $permissions): User
{
    return User::query()->create(['name' => 'Kiểm duyệt viên', 'username' => 'review-editor-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => $permissions, 'is_active' => true]);
}

function pendingReview(Product $product): Review
{
    return Review::query()->create(['product_id' => $product->id, 'stars' => 5, 'title' => 'Tốt',
        'content' => 'Đèn sáng', 'name' => 'Khách thử', 'status' => 'pending']);
}

it('moderates a real review and reply through Filament and changes the public product API', function () {
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $review = pendingReview($product);
    $this->actingAs(reviewEditor(['reviews', 'reviews_moderate']), 'admin');
    $url = '/api/v1/product-extras?slug='.$product->slug;
    $before = $this->getJson($url)->assertOk()->json('reviews.total');

    Livewire\Livewire::test(ManageReviews::class)->callTableAction('moderate', $review, ['status' => 'approved'])->assertHasNoActionErrors();
    expect($review->fresh()->status)->toBe('approved');
    $this->getJson($url)->assertOk()->assertJsonPath('reviews.total', $before + 1);

    Livewire\Livewire::test(ManageReviews::class)->callTableAction('reply', $review, ['content' => '<b>Cảm ơn khách hàng</b>'])
        ->assertHasNoActionErrors();
    expect($review->replies()->count())->toBe(1)
        ->and($review->replies()->first()->content)->toBe('Cảm ơn khách hàng');
    $this->getJson($url)->assertOk()->assertJsonPath('reviews.items.0.repliesTotal', 1);

    Livewire\Livewire::test(ManageReviews::class)->callTableAction('moderate', $review, ['status' => 'hidden'])->assertHasNoActionErrors();
    expect($review->fresh()->status)->toBe('hidden');
    $this->getJson($url)->assertOk()->assertJsonPath('reviews.total', $before);
});

it('approves a selected batch and refuses unauthorized or nested mutations', function () {
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $first = pendingReview($product);
    $second = pendingReview($product);
    $editor = reviewEditor(['reviews', 'reviews_moderate']);
    $this->actingAs($editor, 'admin');
    Livewire\Livewire::test(ManageReviews::class)->callTableBulkAction('approveMany', [$first, $second])
        ->assertHasNoActionErrors();
    expect($first->fresh()->status)->toBe('approved')->and($second->fresh()->status)->toBe('approved');

    $reply = $first->replies()->create(['product_id' => $product->id, 'content' => 'Cảm ơn',
        'name' => 'Phúc Thịnh Solar', 'by_staff' => true, 'status' => 'approved']);
    expect(fn () => app(ReviewModeration::class)->moderateMany(collect([$first->id, $reply->id]), $editor, 'hidden'))
        ->toThrow(ValidationException::class);
    expect($first->fresh()->status)->toBe('approved');

    $viewer = reviewEditor(['reviews']);
    $this->flushSession();
    $this->actingAs($viewer, 'admin');
    $this->get('/admin/reviews')->assertOk();
    Livewire\Livewire::test(ManageReviews::class)->assertTableActionHidden('moderate', $first)->assertTableActionHidden('reply', $first);
    expect(fn () => app(ReviewModeration::class)->moderate($first, $viewer, 'hidden'))
        ->toThrow(HttpException::class);

    $outsider = reviewEditor(['content']);
    $this->flushSession();
    $this->actingAs($outsider, 'admin');
    $this->get('/admin/reviews')->assertForbidden();
    expect(fn () => app(ReviewModeration::class)->moderate($first, $outsider, 'hidden'))
        ->toThrow(HttpException::class);
});

it('lets staff inspect uploaded review media before approval', function () {
    Storage::fake('uploads');
    app()->instance(ProductMediaUrls::class, new ProductMediaUrls(Storage::disk('uploads')->path('')));
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $review = pendingReview($product);
    Storage::disk('uploads')->putFileAs('photo', UploadedFile::fake()->image('customer.jpg'), 'customer.jpg');
    $review->media()->create(['kind' => 'photo', 'path' => 'customer.jpg']);
    $this->actingAs(reviewEditor(['reviews', 'reviews_moderate']), 'admin');

    $component = Livewire\Livewire::test(ManageReviews::class)->assertTableActionVisible('viewMedia', $review)
        ->mountTableAction('viewMedia', $review);
    expect($component->instance()->mountedActions[0]['name'])->toBe('viewMedia')
        ->and(view('filament.review-media', ['media' => $review->media])->render())->toContain('/upload/photo/customer.jpg');
    $this->get('/upload/photo/customer.jpg')->assertOk();
});
