<?php

use App\Domain\Content\PostRevisions;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

it('gives a project without SEO text or summary a description taken from its story', function () {
    $id = DB::table('posts')->insertGetId(['kind' => 'project', 'title' => 'Dự án thử mô tả', 'slug' => 'du-an-thu-mo-ta-'.bin2hex(random_bytes(3)),
        'summary' => '', 'body' => '<p>Hệ thống <strong>10 kWp</strong> áp mái cho gia đình.</p>', 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()]);
    $slug = DB::table('posts')->where('id', $id)->value('slug');

    $this->getJson('/api/v1/project-detail?slug='.$slug)->assertOk()
        ->assertJsonPath('seo.description', 'Hệ thống 10 kWp áp mái cho gia đình.');

    DB::table('posts')->where('id', $id)->update(['body' => '']);
    expect($this->getJson('/api/v1/project-detail?slug='.$slug)->json('seo.description'))->toStartWith('Dự án thử mô tả')->toContain('Phúc Thịnh Solar');
});

it('publishes project system figures through the draft and shows them only when filled in', function () {
    $publisher = User::query()->create(['name' => 'Xuất bản', 'username' => 'xb-'.bin2hex(random_bytes(3)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['content', 'content_publish'], 'is_active' => true]);
    $post = Post::query()->where('kind', 'project')->where('is_visible', true)->firstOrFail();
    $before = $this->getJson('/api/v1/project-detail?slug='.$post->slug)->json('project.system');
    expect($before)->toBeNull();

    $workflow = app(PostRevisions::class);
    $draft = $workflow->draft($post, $publisher);
    $workflow->save($draft, ['title' => $post->title, 'body' => $post->body, 'system_type' => 'hybrid', 'capacity_kwp' => '10.5', 'storage_kwh' => 14.3,
        'output_kwh_month' => 1300, 'saving_vnd_month' => 3200000, 'build_days' => 2], $publisher);
    expect($this->getJson('/api/v1/project-detail?slug='.$post->slug)->json('project.system'))->toBeNull();

    $workflow->publish($draft, $publisher);
    expect($this->getJson('/api/v1/project-detail?slug='.$post->slug)->json('project.system'))->toBe([
        'type' => 'hybrid', 'typeLabel' => 'Hybrid có lưu trữ', 'kwp' => 10.5, 'storageKwh' => 14.3, 'outputKwhMonth' => 1300, 'savingMonth' => 3200000, 'buildDays' => 2,
    ]);

    expect(fn () => $workflow->save($workflow->draft($post->fresh(), $publisher), ['title' => $post->title, 'system_type' => 'nuclear'], $publisher))
        ->toThrow(ValidationException::class);
});
