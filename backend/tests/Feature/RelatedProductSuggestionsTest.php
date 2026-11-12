<?php

use App\Console\Commands\EditorialProductLinksCommand;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Content\PostRevisions;
use App\Domain\News\RelatedProductSuggestions;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\EditorialPlanItem;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** Gives the listed products (by slug) an existing picture on a faked upload disk; every other product has none. */
function relatedProductImages(array $slugs): void
{
    Storage::fake('uploads');
    foreach ($slugs as $slug) {
        $file = 'rel-'.substr(md5($slug), 0, 10).'.jpg';
        Storage::disk('uploads')->putFileAs('product', UploadedFile::fake()->image($file), $file);
        DB::table('products')->where('slug', $slug)->update(['image' => $file]);
    }
    app()->instance(ProductMediaUrls::class, new ProductMediaUrls(Storage::disk('uploads')->path('')));
}

function manifestSlugs(): array
{
    return collect(EditorialProductLinksCommand::manifest())->pluck('products')->flatten(1)->pluck('slug')->unique()->values()->all();
}

function relatedEditorialDrafts(): void
{
    Storage::fake('upload_news');
    Artisan::call('pts:editorial-batch', ['--apply' => true]);
    Artisan::call('pts:editorial-calendar', ['--apply' => true]);
}

function draftOf(string $planKey): Post
{
    return EditorialPlanItem::query()->where('plan_key', $planKey)->sole()->post;
}

it('matches the equipment topic, not shared generic words, and returns only live products', function () {
    relatedProductImages(['tam-pin-nang-luong-mat-troi-longi-620wp-2-mat-kinh', 'tam-pin-nang-luong-mat-troi-longi-650wp-1-mat-kinh',
        'tam-pin-nang-luong-mat-troi-astronergy-625wp', 'tam-pin-nang-luong-mat-troi-astronergy-610wp-2-mat-kinh',
        'tam-pin-nang-luong-mat-troi-longi-650wp-2-mat-kinh', 'inverter-solax-x1-boost-5-g4']);
    $service = app(RelatedProductSuggestions::class);

    expect($service->topics(['title' => 'Năng lượng sạch cho tương lai', 'body' => '<p>Năng lượng mặt trời, điện mặt trời, năng lượng tái tạo.</p>']))->toBe([])
        ->and($service->topics(['title' => 'IEA dự báo thị trường 2031', 'category' => 'tin-moi-nhat',
            'body' => str_repeat('<p>Hòa lưới tăng mạnh. Pin lưu trữ tăng.</p>', 5)]))->toBe([]);

    $panel = ['title' => 'Tấm pin 620W và 650W: watt cao có tốt hơn?', 'keywords' => 'công suất module', 'body' => '<p>So sánh tấm pin 620 W, 650 W.</p>'];
    expect(array_keys($service->topics($panel)))->toBe(['pv_module']);
    $suggested = $service->suggest($panel);
    expect($suggested)->toHaveCount(4)
        ->and(collect($suggested)->pluck('topic')->unique()->all())->toBe(['pv_module'])
        ->and(collect($suggested)->pluck('url')->every(fn ($url) => str_starts_with($url, '/san-pham/')))->toBeTrue()
        ->and($suggested[0]['reason'])->toContain('công suất')
        ->and(collect($suggested)->pluck('slug'))->not->toContain('inverter-solax-x1-boost-5-g4');

    // Hidden product and product whose picture file is missing are never offered.
    $hidden = Product::query()->where('slug', $suggested[0]['slug'])->sole();
    $hidden->update(['is_visible' => false]);
    DB::table('products')->where('slug', $suggested[1]['slug'])->update(['image' => 'khong-ton-tai.jpg']);
    $again = collect($service->suggest($panel))->pluck('id');
    expect($again)->not->toContain($hidden->id)->not->toContain($suggested[1]['id'])->toHaveCount(3)
        ->and($service->suggest($panel, $again->all()))->toBe([]);
});

it('keeps manual choices in the CMS, adds confirmed suggestions only, and caps the article at four', function () {
    relatedProductImages(manifestSlugs());
    relatedEditorialDrafts();
    $owner = User::query()->create(['name' => 'Biên tập', 'username' => 'rel-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['content'], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $post = draftOf('PTS-KT-014');
    $manual = Product::query()->where('slug', 'inverter-solax-x3-mic-10')->sole();
    $post->products()->attach($manual->id, ['sort_order' => 0]);
    $suggest = TestAction::make('suggestProducts')->schemaComponent('products');

    $offered = app(RelatedProductSuggestions::class)->suggest(['title' => $post->title, 'summary' => $post->summary,
        'keywords' => $post->seo?->keywords, 'body' => $post->body, 'category' => $post->category?->slug], [$manual->id]);
    expect($offered)->toHaveCount(4)->and(collect($offered)->pluck('id'))->not->toContain($manual->id);

    $page = Livewire::test(EditArticle::class, ['record' => $post->id])
        ->assertActionVisible($suggest)
        ->callAction($suggest, ['suggested' => [$offered[0]['id'], $offered[1]['id']]])->assertHasNoActionErrors();
    expect(array_map('intval', $page->get('data.products')))->toBe([$manual->id, $offered[0]['id'], $offered[1]['id']])
        ->and($post->fresh()->products()->count())->toBe(1); // nothing is saved before the editor saves

    // Only one slot is left: choosing two is refused and the selection stays as it was.
    $taken = [$manual->id, $offered[0]['id'], $offered[1]['id']];
    $rest = collect(app(RelatedProductSuggestions::class)->suggest(['title' => $post->title, 'summary' => $post->summary,
        'keywords' => $post->seo?->keywords, 'body' => $post->body, 'category' => $post->category?->slug], $taken))->pluck('id')->all();
    expect(count($rest))->toBeGreaterThanOrEqual(2);
    $page->callAction($suggest, ['suggested' => [$rest[0], $rest[1]]])->assertHasActionErrors(['suggested']);
    expect(array_map('intval', $page->get('data.products')))->toBe($taken);
    $page->call('save')->assertHasNoFormErrors();
    expect($post->fresh()->products()->orderBy('post_products.sort_order')->pluck('products.id')->all())
        ->toBe([$manual->id, $offered[0]['id'], $offered[1]['id']])
        ->and($post->fresh()->is_visible)->toBeFalse();

    $this->actingAs(User::query()->create(['name' => 'Bán hàng', 'username' => 'rel-sales-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => ['orders'], 'is_active' => true]), 'admin');
    $this->get(ArticleResource::getUrl('edit', ['record' => $post]))->assertForbidden();
});

it('dry-runs, then links reviewed products once to hidden drafts without overwriting or publishing', function () {
    relatedProductImages(manifestSlugs());
    relatedEditorialDrafts();
    $productCount = Product::query()->count();
    $before = DB::table('post_products')->count();

    $manualPost = draftOf('PTS-KT-002');
    $manual = Product::query()->where('slug', 'inverter-solax-x1-boost-5-g4')->sole();
    $manualPost->products()->attach($manual->id, ['sort_order' => 0]);
    $visible = draftOf('PTS-KT-005');
    $visible->update(['is_visible' => true]);
    $draftOnly = draftOf('PTS-KT-006');
    DB::table('post_revisions')->insert(['post_id' => $draftOnly->id, 'version' => 99, 'state' => 'draft',
        'payload' => json_encode(['products' => [$manual->id]]), 'created_at' => now(), 'updated_at' => now()]);
    Product::query()->where('slug', 'tam-pin-nang-luong-mat-troi-astronergy-625wp')->update(['is_visible' => false]);
    $published = Post::query()->whereIn('id', EditorialPlanItem::query()->whereNotNull('post_id')->pluck('post_id'))->pluck('published_at', 'id');

    expect(Artisan::call('pts:editorial-product-links'))->toBe(0)
        ->and(DB::table('post_products')->count())->toBe($before + 1);

    expect(Artisan::call('pts:editorial-product-links', ['--apply' => true]))->toBe(0);
    $after = DB::table('post_products')->count();
    expect(Artisan::call('pts:editorial-product-links', ['--apply' => true]))->toBe(0)
        ->and(DB::table('post_products')->count())->toBe($after); // idempotent

    expect($manualPost->fresh()->products->pluck('id')->all())->toBe([$manual->id])
        ->and($visible->fresh()->products()->count())->toBe(0)
        ->and($draftOnly->fresh()->products()->count())->toBe(0)
        ->and(draftOf('PTS-KT-004')->products()->count())->toBe(0) // its only product is now hidden
        ->and(draftOf('PTS-KT-003')->products()->count())->toBe(0)
        ->and(draftOf('PTS-KT-012')->products()->count())->toBe(0)
        ->and(draftOf('PTS-KT-015')->products()->count())->toBe(3)
        ->and(draftOf('PTS-KT-008')->products()->orderBy('post_products.sort_order')->pluck('slug')->first())
        ->toBe('den-duong-nang-luong-mat-troi-jindian-jd-19600l-600w')
        ->and(Product::query()->count())->toBe($productCount);

    $perPost = DB::table('post_products')->select('post_id', DB::raw('count(*) as n'), DB::raw('count(distinct product_id) as d'))
        ->groupBy('post_id')->get();
    expect($perPost->every(fn ($row) => $row->n <= 4 && $row->n === $row->d))->toBeTrue();
    foreach ($published as $id => $at) {
        $post = Post::query()->find($id);
        expect($post->published_at)->toBe($at)->and($post->is_visible)->toBe($id === $visible->id);
    }
    $lights = draftOf('PTS-KT-008');
    expect($lights->is_visible)->toBeFalse();
    $this->getJson('/api/v1/news-detail?slug='.$lights->slug)->assertNotFound();

    // The draft preview and, once an editor publishes, the public page link to the same products.
    $actor = User::query()->create(['name' => 'Chủ', 'username' => 'rel-prev-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $workflow = app(PostRevisions::class);
    $draft = $workflow->draft($lights, $actor);
    $url = $workflow->previewUrl($draft->fresh());
    $this->getJson('/api/v1/article-draft-preview/'.$draft->id.'?'.parse_url($url, PHP_URL_QUERY))->assertOk()
        ->assertJsonCount(4, 'article.linkedProducts')
        ->assertJsonPath('article.linkedProducts.0.url', '/san-pham/den-duong-nang-luong-mat-troi-jindian-jd-19600l-600w');
    $lights->update(['is_visible' => true, 'published_at' => now()->subHour()]);
    $this->getJson('/api/v1/news-detail?slug='.$lights->slug)->assertOk()
        ->assertJsonCount(4, 'article.linkedProducts')
        ->assertJsonPath('article.linkedProducts.0.url', '/san-pham/den-duong-nang-luong-mat-troi-jindian-jd-19600l-600w');
});

it('refuses to write on production without an explicit approval flag', function () {
    $env = app()['env'];
    app()['env'] = 'production';
    try {
        expect(Artisan::call('pts:editorial-product-links', ['--apply' => true]))->toBe(1);
    } finally {
        app()['env'] = $env;
    }
});

it('keeps the reviewed manifest within the topics each article discusses', function () {
    $service = app(RelatedProductSuggestions::class);
    foreach (EditorialProductLinksCommand::manifest() as $entry) {
        expect(count($entry['products']))->toBeLessThanOrEqual(RelatedProductSuggestions::MAX);
        foreach ($entry['products'] as $item) {
            $category = DB::table('products as p')->join('categories as c', 'c.id', '=', 'p.category_id')
                ->where('p.slug', $item['slug'])->value('c.slug');
            expect($category)->not->toBeNull()
                ->and($service->topicCategorySlugs($item['topic']))->toContain($category);
        }
    }
});
