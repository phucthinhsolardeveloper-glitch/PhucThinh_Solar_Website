<?php

use App\Models\EditorialPlanItem;
use App\Models\Post;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

function copyRefreshBaseline(Post $post): string
{
    $path = tempnam(sys_get_temp_dir(), 'pts-editorial-baseline-');
    $payload = ['posts' => ['PTS-KT-004' => [
        'id' => $post->id, 'slug' => $post->slug, 'kind' => 'article', 'visible' => false,
        'body_sha256' => hash('sha256', (string) $post->body),
        'summary_sha256' => hash('sha256', (string) $post->summary),
        'seo_description_sha256' => hash('sha256', (string) ($post->seo?->description ?? '')),
    ]]];
    file_put_contents($path, json_encode($payload, JSON_THROW_ON_ERROR));

    return $path;
}

function preparedCopyRefreshPost(): array
{
    Storage::fake('upload_news');
    Artisan::call('pts:editorial-batch', ['--apply' => true]);
    Artisan::call('pts:editorial-calendar', ['--apply' => true]);
    $plan = EditorialPlanItem::query()->where('plan_key', 'PTS-KT-004')->sole();
    $post = $plan->post->fresh(['seo']);
    $post->update(['body' => '<p>Copy cũ</p>', 'summary' => 'Tóm tắt cũ']);
    $post->seo()->updateOrCreate([], ['description' => 'SEO cũ']);

    return [$plan->fresh(), $post->fresh(['seo'])];
}

it('dry-runs, applies atomically, preserves links, and is idempotent', function () {
    [$plan, $post] = preparedCopyRefreshPost();
    $baseline = copyRefreshBaseline($post);
    $image = $post->image;
    $products = $post->products()->pluck('products.id')->all();

    expect(Artisan::call('pts:editorial-copy-refresh', ['--baseline' => $baseline]))->toBe(0)
        ->and($post->fresh()->body)->toBe('<p>Copy cũ</p>');
    expect(Artisan::call('pts:editorial-copy-refresh', ['--baseline' => $baseline, '--apply' => true]))->toBe(0);
    $updated = $post->fresh(['seo']);
    $metadata = json_decode((string) file_get_contents(base_path('resources/editorial/2026-11-12/written-articles.json')), true, flags: JSON_THROW_ON_ERROR);
    $approvedSummary = collect($metadata['articles'])->firstWhere('id', 'PTS-KT-004')['summary'];
    expect($updated->summary)->toBe($approvedSummary);
    expect($updated->body)->not->toBe('<p>Copy cũ</p>')
        ->and($updated->summary)->not->toBe('Tóm tắt cũ')
        ->and($updated->seo->description)->toBe($updated->summary)
        ->and($updated->image)->toBe($image)
        ->and($updated->products()->pluck('products.id')->all())->toBe($products);
    expect(Artisan::call('pts:editorial-copy-refresh', ['--baseline' => $baseline, '--apply' => true]))->toBe(0);
});

it('fails closed on copy drift and visible posts', function () {
    [$plan, $post] = preparedCopyRefreshPost();
    $baseline = copyRefreshBaseline($post);
    $post->update(['body' => '<p>Editor sửa</p>']);
    expect(Artisan::call('pts:editorial-copy-refresh', ['--baseline' => $baseline, '--apply' => true]))->not->toBe(0)
        ->and($post->fresh()->body)->toBe('<p>Editor sửa</p>');
    $post->update(['body' => '<p>Copy cũ</p>', 'is_visible' => true]);
    expect(Artisan::call('pts:editorial-copy-refresh', ['--baseline' => $baseline, '--apply' => true]))->not->toBe(0)
        ->and($post->fresh()->body)->toBe('<p>Copy cũ</p>');
});

it('requires explicit opt-in to refresh the original three drafts with hash guards', function () {
    preparedCopyRefreshPost();
    $plan = EditorialPlanItem::query()->where('plan_key', 'PTS-KT-001')->sole();
    $post = $plan->post->fresh(['seo']);
    expect($post->is_visible)->toBeFalse();
    $post->update(['body' => '<p>Legacy HTML phải giữ nếu không opt-in</p>', 'summary' => 'Tóm tắt chưa duyệt']);
    $post->seo()->updateOrCreate([], ['description' => 'SEO chưa duyệt']);
    $post = $post->fresh(['seo']);
    $image = $post->image;
    $products = $post->products()->pluck('products.id')->all();

    $path = tempnam(sys_get_temp_dir(), 'pts-first-three-');
    $record = [
        'id' => $post->id, 'slug' => $post->slug, 'kind' => 'article', 'visible' => false,
        'body_sha256' => hash('sha256', (string) $post->body),
        'summary_sha256' => hash('sha256', (string) $post->summary),
        'seo_description_sha256' => hash('sha256', (string) ($post->seo?->description ?? '')),
    ];
    file_put_contents($path, json_encode(['posts' => ['PTS-KT-001' => $record]], JSON_THROW_ON_ERROR));

    // Without the explicit flag the original HTML remains untouched.
    expect(Artisan::call('pts:editorial-copy-refresh', ['--baseline' => $path, '--apply' => true]))->toBe(0);
    expect($post->fresh()->body)->toBe('<p>Legacy HTML phải giữ nếu không opt-in</p>');

    expect(Artisan::call('pts:editorial-copy-refresh', [
        '--baseline' => $path, '--include-initial-three' => true,
    ]))->toBe(0);
    expect($post->fresh()->body)->toBe('<p>Legacy HTML phải giữ nếu không opt-in</p>');

    expect(Artisan::call('pts:editorial-copy-refresh', [
        '--baseline' => $path, '--include-initial-three' => true, '--apply' => true,
    ]))->toBe(0);

    $post = $post->fresh(['seo']);
    expect($post->body)->not->toBe('<p>Legacy HTML phải giữ nếu không opt-in</p>')
        ->and($post->summary)->not->toBe('Tóm tắt chưa duyệt')
        ->and($post->seo->description)->toBe($post->summary)
        ->and($post->image)->toBe($image)
        ->and($post->products()->pluck('products.id')->all())->toBe($products)
        ->and($post->is_visible)->toBeFalse();

    expect(Artisan::call('pts:editorial-copy-refresh', [
        '--baseline' => $path, '--include-initial-three' => true, '--apply' => true,
    ]))->toBe(0);

    $post->update(['body' => '<p>Biên tập viên đã sửa tay</p>']);
    expect(Artisan::call('pts:editorial-copy-refresh', [
        '--baseline' => $path, '--include-initial-three' => true, '--apply' => true,
    ]))->not->toBe(0)
        ->and($post->fresh()->body)->toBe('<p>Biên tập viên đã sửa tay</p>');
});
