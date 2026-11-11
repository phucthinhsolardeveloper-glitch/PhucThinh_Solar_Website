<?php

use App\Domain\Content\PostVisibility;
use App\Domain\Routing\PublicStatus;
use App\Domain\Routing\PublicUrls;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Widgets\AttentionWidget;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;

it('keeps a switched-on post with a future publish date off the website until the date comes', function () {
    $live = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $post = $live->replicate();
    $post->fill(['slug' => 'bai-hen-gio-'.bin2hex(random_bytes(3)), 'title' => 'Bài hẹn giờ thử', 'is_visible' => true, 'published_at' => now()->addDay()]);
    $post->save();

    expect(PostVisibility::isScheduled($post))->toBeTrue()->and(PublicUrls::isLive($post))->toBeFalse();
    $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertNotFound();
    expect($this->get('/sitemap.xml')->getContent())->not->toContain($post->slug);
    expect(json_encode($this->getJson('/api/v1/news')->json()))->not->toContain($post->slug);

    $this->travel(2)->days();
    expect(PublicUrls::isLive($post->fresh()))->toBeTrue();
    $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertOk()->assertJsonPath('article.title', 'Bài hẹn giờ thử');
    expect($this->get('/sitemap.xml')->getContent())->toContain($post->slug);
});

it('says on the lists what the website really does with a row', function () {
    $live = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $scheduled = $live->replicate()->fill(['slug' => 'hen-'.bin2hex(random_bytes(3)), 'published_at' => now()->addDays(3)]);
    $scheduled->save();
    $hidden = $live->replicate()->fill(['slug' => 'an-'.bin2hex(random_bytes(3)), 'is_visible' => false]);
    $hidden->save();

    expect(PublicStatus::of($live)['key'])->toBe('live')
        ->and(PublicStatus::of($scheduled)['key'])->toBe('scheduled')
        ->and(PublicStatus::of($scheduled)['label'])->toStartWith('Hẹn giờ đăng')
        ->and(PublicStatus::of($hidden)['key'])->toBe('hidden');

    $category = Category::query()->where('is_visible', false)->first()
        ?? tap(Category::query()->firstOrFail())->update(['is_visible' => false]);
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $product->update(['category_id' => $category->id]);
    app()->forgetScopedInstances();
    expect(PublicStatus::of($product->fresh()))->toMatchArray(['key' => 'unreachable']);

    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'st-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    Livewire\Livewire::test(ListArticles::class)
        ->filterTable('scheduled', true)->assertCanSeeTableRecords([$scheduled])->assertCanNotSeeTableRecords([$live, $hidden]);
    Livewire\Livewire::test(ListProducts::class)->assertSuccessful();
    expect(collect((new AttentionWidget)->items())->pluck('text')->implode(' | '))->toContain('hẹn giờ đăng');
});
