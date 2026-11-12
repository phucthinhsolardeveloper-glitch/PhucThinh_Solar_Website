<?php

use App\Domain\Admin\AdminBell;
use App\Domain\Content\PostRevisions;
use App\Domain\Routing\PublicUrls;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Brand;
use App\Models\Post;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Models\User;
use App\Support\FormToken;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

function bellOwner(): User
{
    return User::query()->create(['name' => 'Chủ', 'username' => 'chu-'.bin2hex(random_bytes(3)), 'password' => 'test-password', 'role' => User::ROLE_OWNER, 'is_active' => true]);
}

it('knows where each kind of record shows on the website', function () {
    config(['site.public_url' => 'http://127.0.0.1:3110']);
    $tag = bin2hex(random_bytes(3));
    $product = Product::query()->create(['name' => 'Đèn '.$tag, 'slug' => 'den-'.$tag, 'regular_price' => 1, 'is_visible' => false]);
    expect(PublicUrls::url($product))->toBe('http://127.0.0.1:3110/san-pham/den-'.$tag)
        ->and(PublicUrls::isLive($product))->toBeFalse()
        ->and(PublicUrls::path(new Brand(['slug' => 'jindian'])))->toBe('/thuong-hieu/jindian/')
        ->and(PublicUrls::path(new Post(['kind' => 'solution', 'slug' => 'bess'])))->toBe('/giai-phap/bess/')
        ->and(PublicUrls::path(new Post(['kind' => 'policy', 'slug' => 'bao-hanh'])))->toBe('/ho-tro/bao-hanh/')
        ->and(PublicUrls::path(new Post(['kind' => 'article', 'slug' => 'tin-a'])))->toBe('/tin-a')
        ->and(PublicUrls::path(new SeoMeta(['page' => 'lien-he'])))->toBe('/lien-he/')
        ->and(PublicUrls::path(new Post(['kind' => 'article', 'slug' => ''])))->toBeNull();
});

it('says what changed after a save and offers the public page', function () {
    $this->actingAs(bellOwner(), 'admin');
    $tag = bin2hex(random_bytes(3));
    $product = Product::query()->create(['name' => 'Đèn '.$tag, 'slug' => 'den-'.$tag, 'regular_price' => 100000, 'is_visible' => true, 'category_id' => DB::table('categories')->value('id')]);
    $page = Livewire::test(EditProduct::class, ['record' => $product->getKey()])
        ->assertActionVisible('viewOnSite')
        ->set('data.name', 'Đèn mới '.$tag)
        ->call('save')->assertHasNoErrors()->assertNotified()
        ->instance();
    // The notice the save just showed, rebuilt from the page (same method Filament calls).
    $notice = (fn () => $this->getSavedNotification())->call($page);
    expect($notice->getBody())->toStartWith('Đã đổi:')->toContain('Tên')
        ->and($notice->getActions()[0]->getUrl())->toEndWith('/san-pham/den-'.$tag);
});

it('offers a signed draft preview after saving a hidden article', function () {
    $this->actingAs(bellOwner(), 'admin');
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $post->update(['is_visible' => false]);

    $page = Livewire::test(EditArticle::class, ['record' => $post->getKey()])
        ->assertActionVisible('viewOnSite')
        ->set('data.title', 'Bài ẩn xem trước')
        ->call('save')->assertHasNoErrors()->assertNotified()
        ->instance();
    $notice = (fn () => $this->getSavedNotification())->call($page);
    $action = $notice->getActions()[0];

    expect($action->getLabel())->toBe('Xem trước bản nháp')
        ->and($action->getUrl())->toContain('/xem-truoc/bai-viet/')
        ->and($post->fresh()->is_visible)->toBeFalse()
        ->and($post->revisions()->where('state', 'draft')->count())->toBe(1);
    $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertNotFound();
});

it('renders the scheduled post header preview as a new-tab signed link', function () {
    $owner = bellOwner();
    $this->actingAs($owner, 'admin');
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $post->update(['published_at' => now()->addDay()]);
    app(PostRevisions::class)->draft($post, $owner);

    Livewire::test(EditArticle::class, ['record' => $post->getKey()])
        ->assertSee('Xem trước bản nháp')
        ->assertSee('target="_blank"', false);
});

it('keeps the preview header action available to content editors without publish permission', function () {
    $editor = User::query()->create([
        'name' => 'Biên tập', 'username' => 'preview-editor-'.bin2hex(random_bytes(3)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['content'], 'is_active' => true,
    ]);
    $this->actingAs($editor, 'admin');
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $post->update(['is_visible' => false]);

    Livewire::test(EditArticle::class, ['record' => $post->getKey()])
        ->assertActionVisible('viewOnSite')
        ->assertSee('Xem trước bản nháp');
});

it('rings the bell once per new enquiry for staff who handle enquiries, without the phone number', function () {
    Mail::fake();
    RateLimiter::clear('form:contact|127.0.0.1');
    $owner = bellOwner();
    $editor = User::query()->create(['name' => 'Biên tập', 'username' => 'bt-'.bin2hex(random_bytes(3)), 'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => ['content'], 'is_active' => true]);
    $this->postJson('/api/v1/lead-form', ['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => ['fullname' => 'Khách Chuông', 'phone' => '0912345678', 'content' => 'Cần tư vấn']])->assertOk();
    $leadId = DB::table('leads')->orderByDesc('id')->value('id');

    $mine = DatabaseNotification::query()->where('notifiable_id', $owner->id)->get();
    expect($mine)->toHaveCount(1)
        ->and($mine[0]->data['title'])->toBe('Liên hệ mới: Khách Chuông')
        ->and(json_encode($mine[0]->data))->not->toContain('0912345678')
        ->and(DatabaseNotification::query()->where('notifiable_id', $editor->id)->count())->toBe(0);

    AdminBell::ring('leads', 'lead:'.$leadId, 'Lặp lại', 'x', null);
    expect(DatabaseNotification::query()->where('notifiable_id', $owner->id)->count())->toBe(1);
});
