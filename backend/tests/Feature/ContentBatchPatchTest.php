<?php

use App\Models\Post;
use App\Support\RichText;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function batchPatchFixture(string $body = '<p>Nội dung hiện tại</p>', string $summary = 'Tóm tắt cũ'): array
{
    $now = now();
    $id = DB::table('posts')->insertGetId([
        'kind' => 'article', 'category_id' => null, 'author_id' => null, 'title' => 'Bài giữ nguyên',
        'nav_title' => 'Nhãn điều hướng', 'slug' => 'batch-patch-'.Str::lower(Str::random(10)),
        'summary' => $summary, 'body' => $body, 'extra_body' => 'Extra giữ nguyên', 'image' => 'cover.jpg',
        'cover_image' => 'cover-original.jpg', 'is_visible' => false, 'is_featured' => true, 'in_hub' => true,
        'client_name' => 'Khách hàng', 'address' => 'Địa chỉ', 'started_on' => '2026-01-01', 'finished_on' => '2026-01-02',
        'sort_order' => 7, 'views' => 11, 'published_at' => '2026-10-09 08:53:20', 'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table('seo_metas')->insert([
        'owner_type' => Post::class, 'owner_id' => $id, 'page' => null,
        'title' => 'SEO title cũ', 'description' => 'SEO cũ', 'keywords' => 'giữ nguyên', 'image' => 'seo.jpg',
        'created_at' => $now, 'updated_at' => $now,
    ]);
    $post = Post::query()->with('seo')->findOrFail($id);
    $directory = base_path('resources/editorial/.content-batch-test');
    if (! is_dir($directory)) {
        mkdir($directory, 0700, true);
    }
    $markdownPath = $directory.'/target-'.$id.'.md';
    file_put_contents($markdownPath, "## Tiêu đề mới\n\nNội dung mới có thông tin hữu ích.\n");
    $manifestPath = sys_get_temp_dir().'/content-batch-patch-'.$id.'-'.Str::random(8).'.json';
    $manifest = [
        'schema_version' => 1,
        'items' => [[
            'id' => $id, 'kind' => 'article', 'slug' => $post->slug,
            'original' => [
                'body_sha256' => hash('sha256', (string) $post->body),
                'summary_sha256' => hash('sha256', (string) $post->summary),
                'seo_title_sha256' => hash('sha256', (string) $post->seo->title),
                'seo_description_sha256' => hash('sha256', (string) $post->seo->description),
            ],
            'proposed' => [
                'markdown_file' => '.content-batch-test/target-'.$id.'.md',
                'summary' => 'Tóm tắt mới', 'seo_description' => 'Mô tả SEO mới', 'seo_title' => 'SEO title mới',
            ],
            'qa' => ['state' => 'approved', 'sources_verified' => true, 'images_verified' => true, 'human_approved' => true, 'reviewer' => 'Người duyệt'],
        ]],
    ];
    file_put_contents($manifestPath, json_encode($manifest, JSON_THROW_ON_ERROR));

    return [$post, $manifestPath, $markdownPath];
}

function batchPatchCleanup(array $fixture): void
{
    @unlink($fixture[1]);
    @unlink($fixture[2]);
    @rmdir(base_path('resources/editorial/.content-batch-test'));
}

it('dry-runs without mutating article or seo values', function () {
    $fixture = batchPatchFixture();
    [$post, $manifest] = $fixture;
    $beforePost = DB::table('posts')->where('id', $post->id)->first();
    $beforeSeo = DB::table('seo_metas')->where('owner_id', $post->id)->first();

    expect(Artisan::call('pts:content-batch-patch', ['--manifest' => $manifest]))->toBe(0)
        ->and(Artisan::output())->toContain('WOULD UPDATE '.$post->id, 'DRY-RUN', 'Run report');
    expect(DB::table('posts')->where('id', $post->id)->first())->toEqual($beforePost)
        ->and(DB::table('seo_metas')->where('owner_id', $post->id)->first())->toEqual($beforeSeo);
    batchPatchCleanup($fixture);
});

it('applies approved fields while preserving metadata and is idempotent', function () {
    $fixture = batchPatchFixture();
    [$post, $manifest] = $fixture;
    $before = DB::table('posts')->where('id', $post->id)->first();
    $beforeSeo = DB::table('seo_metas')->where('owner_id', $post->id)->first();
    $expectedBody = RichText::sanitize(Str::markdown("## Tiêu đề mới\n\nNội dung mới có thông tin hữu ích.\n", ['html_input' => 'strip', 'allow_unsafe_links' => false]));

    expect(Artisan::call('pts:content-batch-patch', ['--manifest' => $manifest, '--apply' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('APPLIED', 'backup:');
    $after = DB::table('posts')->where('id', $post->id)->first();
    $afterSeo = DB::table('seo_metas')->where('owner_id', $post->id)->first();
    expect($after->body)->toBe($expectedBody)
        ->and($after->summary)->toBe('Tóm tắt mới')
        ->and($after->title)->toBe($before->title)
        ->and($after->slug)->toBe($before->slug)
        ->and($after->image)->toBe($before->image)
        ->and($after->cover_image)->toBe($before->cover_image)
        ->and($after->is_visible)->toBe($before->is_visible)
        ->and($after->category_id)->toBe($before->category_id)
        ->and($after->extra_body)->toBe($before->extra_body)
        ->and($after->published_at)->toBe($before->published_at)
        ->and($afterSeo->title)->toBe('SEO title mới')
        ->and($afterSeo->description)->toBe('Mô tả SEO mới')
        ->and($afterSeo->keywords)->toBe($beforeSeo->keywords)
        ->and($afterSeo->image)->toBe($beforeSeo->image);
    expect(Artisan::call('pts:content-batch-patch', ['--manifest' => $manifest, '--apply' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('NOOP '.$post->id);
    batchPatchCleanup($fixture);
});

it('fails closed when content drifted after baseline capture', function () {
    $fixture = batchPatchFixture();
    [$post, $manifest] = $fixture;
    DB::table('posts')->where('id', $post->id)->update(['body' => '<p>Editor đã sửa</p>']);

    expect(Artisan::call('pts:content-batch-patch', ['--manifest' => $manifest, '--apply' => true]))->not->toBe(0)
        ->and(Artisan::output())->toContain('baseline drift')
        ->and(DB::table('posts')->where('id', $post->id)->value('body'))->toBe('<p>Editor đã sửa</p>');
    batchPatchCleanup($fixture);
});

it('reports unapproved copy as a dry-run blocker and does not mutate', function () {
    $fixture = batchPatchFixture();
    [$post, $manifest] = $fixture;
    $payload = json_decode(file_get_contents($manifest), true, flags: JSON_THROW_ON_ERROR);
    $payload['items'][0]['qa']['human_approved'] = false;
    file_put_contents($manifest, json_encode($payload, JSON_THROW_ON_ERROR));

    expect(Artisan::call('pts:content-batch-patch', ['--manifest' => $manifest]))->not->toBe(0)
        ->and(Artisan::output())->toContain('BLOCKED', 'FAIL CLOSED')
        ->and(DB::table('posts')->where('id', $post->id)->value('body'))->toBe('<p>Nội dung hiện tại</p>');
    batchPatchCleanup($fixture);
});

it('rejects a markdown path outside the protected editorial root', function () {
    $fixture = batchPatchFixture();
    [$post, $manifest] = $fixture;
    $payload = json_decode(file_get_contents($manifest), true, flags: JSON_THROW_ON_ERROR);
    $payload['items'][0]['proposed']['markdown_file'] = '../../.env';
    file_put_contents($manifest, json_encode($payload, JSON_THROW_ON_ERROR));

    expect(Artisan::call('pts:content-batch-patch', ['--manifest' => $manifest]))->not->toBe(0)
        ->and(Artisan::output())->toContain('đường dẫn tương đối an toàn')
        ->and(DB::table('posts')->where('id', $post->id)->value('body'))->toBe('<p>Nội dung hiện tại</p>');
    batchPatchCleanup($fixture);
});

it('blocks apply in production unless separately opted in and referenced', function () {
    $fixture = batchPatchFixture();
    [$post, $manifest] = $fixture;
    $this->app['env'] = 'production';

    expect(Artisan::call('pts:content-batch-patch', ['--manifest' => $manifest, '--apply' => true]))->not->toBe(0)
        ->and(Artisan::output())->toContain('Apply bị từ chối')
        ->and(DB::table('posts')->where('id', $post->id)->value('body'))->toBe('<p>Nội dung hiện tại</p>');
    $this->app['env'] = 'testing';
    batchPatchCleanup($fixture);
});
