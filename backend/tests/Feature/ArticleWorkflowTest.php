<?php

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Content\ArticleWorkflow;
use App\Filament\Resources\ArticleDrafts\Pages\EditArticleDraft;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

function articleWorkflowUser(array $permissions = []): User
{
    return User::query()->create([
        'name' => 'Biên tập thử', 'username' => 'article-draft-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => $permissions === [] ? User::ROLE_OWNER : User::ROLE_EDITOR,
        'permissions' => $permissions, 'is_active' => true,
    ]);
}

function articleDraftInput(string $title): array
{
    return [
        'title' => $title, 'summary' => 'Tóm tắt bản nháp',
        'body' => '<h2>Nội dung thử</h2><p>Bản nháp chỉ hiện khi xuất bản.</p>',
        'extra_body' => null,
        'seo' => ['title' => 'SEO bản nháp', 'description' => 'Mô tả bản nháp', 'keywords' => 'năng lượng mặt trời'],
    ];
}

it('keeps public content unchanged until publish, then can restore an earlier version', function () {
    $actor = articleWorkflowUser();
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $originalTitle = $post->title;
    $before = $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertOk()->json();
    $workflow = app(ArticleWorkflow::class);

    $draft = $workflow->draft($post, $actor);
    expect($workflow->draft($post, $actor)->id)->toBe($draft->id);
    $workflow->save($draft, articleDraftInput('Tiêu đề mới bản nháp'), $actor);
    expect($post->fresh()->title)->toBe($originalTitle);
    $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertOk()->assertJsonPath('article.title', $before['article']['title']);

    $url = $workflow->previewUrl($draft->fresh());
    $query = parse_url($url, PHP_URL_QUERY);
    $path = '/api/v1/article-draft-preview/'.$draft->id.'?'.$query;
    $this->getJson($path)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertJsonPath('article.title', 'Tiêu đề mới bản nháp');
    $this->getJson($path.'&extra=1')->assertForbidden();

    $workflow->publish($draft, $actor);
    expect($post->fresh()->title)->toBe('Tiêu đề mới bản nháp')
        ->and($draft->fresh()->state)->toBe('published')
        ->and($post->revisions()->where('state', 'published')->count())->toBe(2);
    $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertOk()->assertJsonPath('article.title', 'Tiêu đề mới bản nháp');

    $baseline = $post->revisions()->where('version', 0)->firstOrFail();
    $restore = $workflow->restoreAsDraft($baseline, $actor);
    expect($restore->payload['title'])->toBe($originalTitle)
        ->and($post->fresh()->title)->toBe('Tiêu đề mới bản nháp');
    $workflow->publish($restore, $actor);
    expect($post->fresh()->title)->toBe($originalTitle)
        ->and($post->fresh()->seo?->title)->toBe($baseline->payload['seo']['title']);
});

it('blocks publishing a stale draft and exposes the editor only to the content area', function () {
    $actor = articleWorkflowUser(['content', 'content_publish']);
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $workflow = app(ArticleWorkflow::class);
    $draft = $workflow->draft($post, $actor);
    $workflow->save($draft, articleDraftInput('Tiêu đề thay đổi'), $actor);
    $post->update(['title' => 'Sửa trực tiếp sau khi tạo nháp']);
    expect(fn () => $workflow->publish($draft, $actor))->toThrow(ValidationException::class);
    expect($post->fresh()->title)->toBe('Sửa trực tiếp sau khi tạo nháp');

    $this->actingAs($actor, 'admin');
    $this->get('/admin/article-drafts')->assertOk();
    $this->get('/admin/article-drafts/'.$draft->id.'/edit')->assertOk();
    $this->flushSession();
    $other = articleWorkflowUser(['catalog']);
    $this->actingAs($other, 'admin');
    $this->get('/admin/article-drafts')->assertForbidden();
});

it('lets Filament save a draft without changing the published article', function () {
    $actor = articleWorkflowUser();
    $this->actingAs($actor, 'admin');
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    Livewire\Livewire::test(EditArticle::class, ['record' => $post->id])->assertActionExists('draft');
    $draft = app(ArticleWorkflow::class)->draft($post, $actor);
    Livewire\Livewire::test(EditArticleDraft::class, ['record' => $draft->id])
        ->fillForm(['payload' => articleDraftInput('Tiêu đề qua Filament')])
        ->call('save')->assertHasNoFormErrors();
    expect($draft->fresh()->payload['title'])->toBe('Tiêu đề qua Filament')
        ->and($post->fresh()->title)->not->toBe('Tiêu đề qua Filament');
});

it('keeps draft HTML and SEO bytes intact when the editor saves without changes', function () {
    $actor = articleWorkflowUser();
    $this->actingAs($actor, 'admin');
    $post = Post::query()->where('kind', 'article')->whereNotNull('body')->firstOrFail();
    $draft = app(ArticleWorkflow::class)->draft($post, $actor);
    $before = $draft->payload;

    Livewire\Livewire::test(EditArticleDraft::class, ['record' => $draft->id])
        ->call('save')->assertHasNoFormErrors();

    expect($draft->fresh()->payload)->toBe($before);
});

it('creates a new article hidden until an editor chooses to publish it', function () {
    $this->actingAs(articleWorkflowUser(), 'admin');
    $slug = 'draft-new-'.bin2hex(random_bytes(4));

    Livewire\Livewire::test(CreateArticle::class)
        ->fillForm(['title' => 'Bài mới chưa xuất bản', 'slug' => $slug, 'body' => '<p>Nội dung thử.</p>'])
        ->call('create')->assertHasNoFormErrors();

    expect(Post::query()->where('kind', 'article')->where('slug', $slug)->value('is_visible'))->toBeFalse();
});

it('lets a content editor draft but prevents publishing or direct public edits', function () {
    $editor = articleWorkflowUser(['content']);
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $workflow = app(ArticleWorkflow::class);
    $draft = $workflow->draft($post, $editor);
    $workflow->save($draft, articleDraftInput('Bản nháp của editor'), $editor);
    expect(fn () => $workflow->publish($draft, $editor))
        ->toThrow(HttpException::class);

    $this->actingAs($editor, 'admin');
    Livewire\Livewire::test(EditArticleDraft::class, ['record' => $draft->id])->assertActionHidden('publish');
    Livewire\Livewire::test(EditArticle::class, ['record' => $post->id])->assertSee('Bài đã xuất bản chỉ được sửa qua bản nháp')
        ->fillForm(['title' => 'Sửa public không được duyệt'])->call('save')->assertForbidden();
    expect($post->fresh()->title)->not->toBe('Sửa public không được duyệt');

    $slug = 'editor-hidden-'.bin2hex(random_bytes(4));
    Livewire\Livewire::test(CreateArticle::class)
        ->fillForm(['title' => 'Bài editor', 'slug' => $slug, 'body' => '<p>Nội dung.</p>', 'is_visible' => true])
        ->call('create')->assertHasNoFormErrors();
    expect(Post::query()->where('slug', $slug)->value('is_visible'))->toBeFalse();
});

it('previews linked products from the draft and publishes them with the article', function () {
    Storage::fake('uploads');
    Storage::disk('uploads')->putFileAs('product', UploadedFile::fake()->image('draft-linked.jpg'), 'draft-linked.jpg');
    app()->instance(ProductMediaUrls::class, new ProductMediaUrls(Storage::disk('uploads')->path('')));
    $actor = articleWorkflowUser();
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $product->update(['image' => 'draft-linked.jpg']);
    $workflow = app(ArticleWorkflow::class);
    $draft = $workflow->draft($post, $actor);
    $workflow->save($draft, [...articleDraftInput('Bài có sản phẩm'), 'products' => [$product->id]], $actor);

    $query = parse_url($workflow->previewUrl($draft->fresh()), PHP_URL_QUERY);
    $this->getJson('/api/v1/article-draft-preview/'.$draft->id.'?'.$query)->assertOk()
        ->assertJsonPath('article.linkedProducts.0.id', $product->id);
    $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertOk()->assertJsonMissingPath('article.linkedProducts');

    $workflow->publish($draft, $actor);
    expect($post->fresh()->products->pluck('id')->all())->toBe([$product->id]);
    $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertOk()
        ->assertJsonPath('article.linkedProducts.0.id', $product->id);

    $baseline = $post->revisions()->where('version', 0)->firstOrFail();
    $restored = $workflow->restoreAsDraft($baseline, $actor);
    $workflow->publish($restored, $actor);
    expect($post->fresh()->products()->count())->toBe(0);
});

it('publishes a legacy draft after its product links are edited', function () {
    $actor = articleWorkflowUser();
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $workflow = app(ArticleWorkflow::class);
    $draft = $workflow->draft($post, $actor);
    $legacyPayload = $draft->payload;
    unset($legacyPayload['products']);
    $draft->update(['payload' => $legacyPayload,
        'base_hash' => hash('sha256', json_encode($legacyPayload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))]);

    $workflow->save($draft, [...articleDraftInput('Bài nháp cũ'), 'products' => [$product->id]], $actor);
    $workflow->publish($draft, $actor);
    expect($post->fresh()->products->pluck('id')->all())->toBe([$product->id]);
});
