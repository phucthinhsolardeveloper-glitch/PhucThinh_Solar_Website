<?php

use App\Domain\Content\PostWorkflow;
use App\Filament\Resources\Policies\Pages\CreatePolicy;
use App\Filament\Resources\Policies\Pages\EditPolicy;
use App\Filament\Resources\Policies\PolicyResource;
use App\Filament\Resources\PostDrafts\Pages\EditPostDraft;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Solutions\Pages\CreateSolution;
use App\Filament\Resources\Solutions\Pages\EditSolution;
use App\Filament\Resources\Solutions\SolutionResource;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

function postWorkflowActor(array $permissions): User
{
    return User::query()->create([
        'name' => 'Biên tập thử', 'username' => 'post-draft-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR,
        'permissions' => $permissions, 'is_active' => true,
    ]);
}

it('keeps each public post unchanged until an authorized publish and can restore history', function (string $kind) {
    $editor = postWorkflowActor(['content']);
    $publisher = postWorkflowActor(['content', 'content_publish']);
    $post = Post::query()->where('kind', $kind)->where('is_visible', true)->firstOrFail();
    $original = $post->title;
    $workflow = app(PostWorkflow::class);
    $draft = $workflow->draft($post, $editor);
    expect($workflow->draft($post, $editor)->id)->toBe($draft->id);
    $input = [
        'title' => 'Bản nháp '.$kind, 'summary' => 'Mô tả mới',
        'body' => '<h2>Nội dung mới</h2><p>Chi tiết.</p>',
        'seo' => ['title' => 'SEO '.$kind, 'description' => 'Mô tả SEO', 'keywords' => 'solar'],
    ];
    if ($kind === 'solution') {
        $input['nav_title'] = 'Menu mới';
        $input['extra_body'] = '<p>Nội dung phụ.</p>';
    }
    if ($kind === 'project') {
        $input['client_name'] = 'Khách hàng thử';
        $input['address'] = 'TP Hồ Chí Minh';
    }
    $workflow->save($draft, $input, $editor);
    expect($post->fresh()->title)->toBe($original);
    expect(fn () => $workflow->publish($draft, $editor))->toThrow(HttpException::class);
    $workflow->publish($draft, $publisher);
    expect($post->fresh()->title)->toBe('Bản nháp '.$kind)
        ->and($post->seo?->fresh()->title)->toBe('SEO '.$kind)
        ->and($draft->fresh()->state)->toBe('published');
    $endpoint = match ($kind) {
        'solution' => 'solution-detail', 'project' => 'project-detail', 'policy' => 'policy-detail',
    };
    $titlePath = $kind === 'policy' ? 'title' : $kind.'.title';
    $this->getJson('/api/v1/'.$endpoint.'?slug='.$post->slug)->assertOk()
        ->assertJsonPath($titlePath, 'Bản nháp '.$kind);
    if ($kind === 'solution') {
        expect($post->fresh()->nav_title)->toBe('Menu mới');
    }
    if ($kind === 'project') {
        expect($post->fresh()->client_name)->toBe('Khách hàng thử');
    }
    $baseline = $post->revisions()->where('version', 0)->firstOrFail();
    $restored = $workflow->restoreAsDraft($baseline, $publisher);
    $workflow->publish($restored, $publisher);
    expect($post->fresh()->title)->toBe($original);
})->with(['solution', 'project', 'policy']);

it('blocks stale drafts and direct public edits by content-only editors', function (string $kind, string $editPage, string $resource) {
    $editor = postWorkflowActor(['content']);
    $publisher = postWorkflowActor(['content', 'content_publish']);
    $post = Post::query()->where('kind', $kind)->where('is_visible', true)->firstOrFail();
    $workflow = app(PostWorkflow::class);
    $draft = $workflow->draft($post, $editor);

    $this->actingAs($editor, 'admin');
    expect($resource::canDelete($post))->toBeFalse();
    Livewire\Livewire::test($editPage, ['record' => $post->id])
        ->assertActionExists('draft')->fillForm(['title' => 'Sửa public trái quyền'])
        ->call('save')->assertForbidden();
    expect($post->fresh()->title)->not->toBe('Sửa public trái quyền');
    Livewire\Livewire::test(EditPostDraft::class, ['record' => $draft->id])
        ->assertActionHidden('publish')->call('save')->assertHasNoFormErrors();

    $post->update(['title' => 'Sửa trực tiếp sau khi tạo nháp']);
    expect(fn () => $workflow->publish($draft, $publisher))->toThrow(ValidationException::class);
})->with([
    ['solution', EditSolution::class, SolutionResource::class],
    ['project', EditProject::class, ProjectResource::class],
    ['policy', EditPolicy::class, PolicyResource::class],
]);

it('creates solution, project and policy hidden for an editor without publish permission', function (string $kind, string $createPage) {
    $editor = postWorkflowActor(['content']);
    $this->actingAs($editor, 'admin');
    $slug = 'editor-hidden-'.bin2hex(random_bytes(4));
    Livewire\Livewire::test($createPage)
        ->fillForm(['title' => 'Trang mới', 'slug' => $slug, 'body' => '<p>Nội dung.</p>', 'is_visible' => true])
        ->call('create')->assertHasNoFormErrors();
    expect(Post::query()->where('kind', $kind)->where('slug', $slug)->value('is_visible'))->toBeFalse();
})->with([
    ['solution', CreateSolution::class],
    ['project', CreateProject::class],
    ['policy', CreatePolicy::class],
]);

it('keeps draft HTML and SEO intact on an unchanged save', function (string $kind) {
    $editor = postWorkflowActor(['content']);
    $post = Post::query()->where('kind', $kind)->whereNotNull('body')->firstOrFail();
    $draft = app(PostWorkflow::class)->draft($post, $editor);
    $before = $draft->payload;

    $this->actingAs($editor, 'admin');
    $this->get('/admin/post-drafts')->assertOk();
    $this->get('/admin/post-drafts/'.$draft->id.'/edit')->assertOk();
    Livewire\Livewire::test(EditPostDraft::class, ['record' => $draft->id])
        ->call('save')->assertHasNoFormErrors();
    expect($draft->fresh()->payload)->toBe($before);

    $this->flushSession();
    $this->actingAs(postWorkflowActor(['catalog']), 'admin');
    $this->get('/admin/post-drafts')->assertForbidden();
})->with(['solution', 'project', 'policy']);

it('publishes and restores project product links with the draft', function () {
    $publisher = postWorkflowActor(['content', 'content_publish']);
    $post = Post::query()->where('kind', 'project')->where('is_visible', true)->firstOrFail();
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $workflow = app(PostWorkflow::class);
    $draft = $workflow->draft($post, $publisher);
    $original = $post->products()->pluck('products.id')->all();
    $workflow->save($draft, [
        'title' => $post->title, 'body' => $post->body, 'seo' => ['title' => $post->seo?->title],
        'products' => [$product->id],
    ], $publisher);
    expect($post->products()->pluck('products.id')->all())->toBe($original);
    $workflow->publish($draft, $publisher);
    expect($post->products()->pluck('products.id')->all())->toBe([$product->id]);

    $baseline = $post->revisions()->where('version', 0)->firstOrFail();
    $workflow->publish($workflow->restoreAsDraft($baseline, $publisher), $publisher);
    expect($post->products()->pluck('products.id')->all())->toBe($original);
});
