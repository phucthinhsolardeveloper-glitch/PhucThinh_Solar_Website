<?php

use App\Domain\Content\PostRevisions;
use App\Models\Post;
use App\Models\User;

it('previews solution, project and policy drafts as the public page while the live page stays unchanged', function (string $kind, string $endpoint, string $titlePath, string $page) {
    $actor = User::query()->create(['name' => 'Chủ', 'username' => 'preview-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $post = Post::query()->where('kind', $kind)->where('is_visible', true)->orderBy('id')->firstOrFail();
    $live = $this->getJson('/api/v1/'.$endpoint.'?slug='.$post->slug)->assertOk()->json($titlePath);
    $workflow = app(PostRevisions::class);
    $draft = $workflow->draft($post, $actor);
    $workflow->save($draft, ['title' => 'Bản nháp xem trước '.$kind, 'summary' => 'Tóm tắt nháp', 'body' => '<p>Nội dung nháp để xem trước.</p>',
        'seo' => ['title' => 'SEO nháp', 'description' => 'Mô tả nháp', 'keywords' => '']], $actor);

    $url = $workflow->previewUrl($draft->fresh());
    expect(parse_url($url, PHP_URL_PATH))->toBe('/xem-truoc/'.$page.'/'.$draft->id);
    $api = '/api/v1/post-draft-preview/'.$draft->id.'?'.parse_url($url, PHP_URL_QUERY);
    $this->getJson($api)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertJsonPath($titlePath, 'Bản nháp xem trước '.$kind);
    $this->getJson($api.'&x=1')->assertForbidden();
    $this->getJson('/api/v1/post-draft-preview/'.$draft->id)->assertForbidden();

    expect($post->fresh()->title)->toBe($post->title);
    $this->getJson('/api/v1/'.$endpoint.'?slug='.$post->slug)->assertOk()->assertJsonPath($titlePath, $live);
})->with([
    'solution' => ['solution', 'solution-detail', 'solution.title', 'giai-phap'],
    'project' => ['project', 'project-detail', 'project.title', 'du-an'],
    'policy' => ['policy', 'policy-detail', 'title', 'chinh-sach'],
]);

it('previews a hidden post that has never been published', function () {
    $actor = User::query()->create(['name' => 'Chủ', 'username' => 'preview-h-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $post = Post::query()->where('kind', 'policy')->where('is_visible', true)->orderBy('id')->firstOrFail();
    $post->update(['is_visible' => false]);
    $workflow = app(PostRevisions::class);
    $draft = $workflow->draft($post, $actor);
    $workflow->save($draft, ['title' => 'Chính sách mới chưa đăng', 'summary' => '', 'body' => '<p>Nội dung.</p>', 'seo' => []], $actor);
    $url = $workflow->previewUrl($draft->fresh());
    $this->getJson('/api/v1/post-draft-preview/'.$draft->id.'?'.parse_url($url, PHP_URL_QUERY))->assertOk()->assertJsonPath('title', 'Chính sách mới chưa đăng');
    expect($post->fresh()->is_visible)->toBeFalse();
    $this->getJson('/api/v1/policy-detail?slug='.$post->slug)->assertNotFound();
});
