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

it('switches a new article on when a future publish time is picked, so the date alone schedules it', function () {
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'sc-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $page = Livewire\Livewire::test(\App\Filament\Resources\Articles\Pages\CreateArticle::class)->assertFormSet(['is_visible' => false]);
    $page->set('data.published_at', now()->addDays(2)->format('Y-m-d H:i:s'))->assertFormSet(['is_visible' => true]);
});

it('does not switch an article on for a past time', function () {
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'sp-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    Livewire\Livewire::test(\App\Filament\Resources\Articles\Pages\CreateArticle::class)
        ->set('data.published_at', now()->subDay()->format('Y-m-d H:i:s'))->assertFormSet(['is_visible' => false]);
});

it('edits the publish date and the visible switch right on the article list once the pencil is on', function () {
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'ib-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $source = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $post = $source->replicate()->fill(['slug' => 'sua-nhanh-'.bin2hex(random_bytes(3)), 'title' => 'Bài sửa nhanh', 'is_visible' => false, 'published_at' => now()->subDay()]);
    $post->save();
    $key = (string) $post->getKey();

    Livewire\Livewire::test(ListArticles::class)->assertTableColumnDoesNotExist('is_visible')->assertTableColumnExists('published_at');
    $page = Livewire\Livewire::withQueryParams(['sua' => 1])->test(ListArticles::class)->assertTableColumnExists('is_visible')->assertTableColumnExists('published_at');

    // A future date typed in the box: saved as that moment; a malformed one is refused and nothing changes.
    $page->call('updateTableColumnState', 'published_at', $key, '25/12/2026 08:30');
    expect(Illuminate\Support\Carbon::parse($post->fresh()->published_at)->format('Y-m-d H:i'))->toBe('2026-12-25 08:30');
    $page->call('updateTableColumnState', 'published_at', $key, 'hôm qua');
    expect(Illuminate\Support\Carbon::parse($post->fresh()->published_at)->format('Y-m-d H:i'))->toBe('2026-12-25 08:30');

    // Switching it on goes through the same publish checks as the edit form.
    $page->call('updateTableColumnState', 'is_visible', $key, true);
    expect((bool) $post->fresh()->is_visible)->toBe(true);

    // Nobody without the publish permission gets the pencil.
    $this->flushSession();
    $editor = User::query()->create(['name' => 'Biên tập', 'username' => 'ie-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['content'], 'is_active' => true]);
    $this->actingAs($editor, 'admin');
    Livewire\Livewire::test(ListArticles::class)->assertActionHidden('inlineEdit');
});
