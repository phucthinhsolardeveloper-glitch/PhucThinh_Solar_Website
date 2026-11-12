<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function reworkBaseline(array $records): string
{
    $path = tempnam(sys_get_temp_dir(), 'pts-content-rework-');
    file_put_contents($path, json_encode(['records' => $records], JSON_THROW_ON_ERROR));

    return $path;
}

function reworkRecord(int $id, string $kind = 'article'): array
{
    return [
        'post_id' => $id, 'kind' => $kind, 'slug' => 'test-'.$id, 'is_visible' => false,
        'body_sha256' => hash('sha256', ''), 'summary_sha256' => hash('sha256', ''),
        'nav_title_sha256' => hash('sha256', ''),
    ];
}

it('supports explicit ids and skips the stale nav-title baseline safely', function () {
    $baseline = reworkBaseline([reworkRecord(133, 'solution')]);

    expect(Artisan::call('pts:content-rework-apply', [
        '--baseline' => $baseline, '--ids' => ['133'],
    ]))->toBe(0)
        ->and(Artisan::output())->toContain('nav_title changed after baseline');
});

it('reports unselected records without blocking a dry-run', function () {
    $baseline = reworkBaseline([reworkRecord(133, 'solution'), reworkRecord(107)]);

    expect(Artisan::call('pts:content-rework-apply', [
        '--baseline' => $baseline, '--ids' => ['133'],
    ]))->toBe(0)
        ->and(Artisan::output())->toContain('SKIP 107: not selected');
});

it('fails a selected batch closed when its staged source is absent', function () {
    DB::table('posts')->insert([
        'id' => 107, 'kind' => 'article', 'slug' => 'test-107',
        'title' => 'Fixture legacy article', 'nav_title' => '',
        'body' => '', 'summary' => '', 'is_visible' => false,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $baseline = reworkBaseline([reworkRecord(107)]);

    expect(Artisan::call('pts:content-rework-apply', [
        '--baseline' => $baseline, '--ids' => ['107'],
    ]))->not->toBe(0)
        ->and(Artisan::output())->toContain('staged Markdown/proposal missing');
});

it('rejects apply outside the explicit baseline workflow', function () {
    expect(Artisan::call('pts:content-rework-apply', [
        '--baseline' => '/does/not/exist', '--apply' => true,
    ]))->not->toBe(0);
});
