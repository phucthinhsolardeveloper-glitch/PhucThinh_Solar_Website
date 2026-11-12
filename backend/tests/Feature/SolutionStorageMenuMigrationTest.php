<?php

use Illuminate\Support\Facades\DB;

function storageSolutionFixture(): int
{
    $query = DB::table('posts')->where('kind', 'solution')->where('slug', 'he-thong-luu-tru-bess');
    $id = $query->value('id');
    if ($id === null) {
        $id = DB::table('posts')->insertGetId([
            'kind' => 'solution', 'title' => 'Giải pháp lưu trữ năng lượng & BESS',
            'nav_title' => 'Lưu trữ điện', 'slug' => 'he-thong-luu-tru-bess',
            'summary' => 'Fixture for the guarded navigation migration.', 'body' => '<p>Fixture</p>',
            'is_visible' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    return (int) $id;
}

it('renames only the exact storage solution navigation label and is idempotent', function () {
    $migration = require base_path('database/migrations/2026_11_12_000300_rename_solution_storage_nav_title.php');
    $id = storageSolutionFixture();
    DB::table('posts')->where('id', $id)->update(['nav_title' => 'Lưu trữ điện']);

    $migration->up();
    expect(DB::table('posts')->where('id', $id)->value('nav_title'))->toBe('Pin lưu trữ')
        ->and(DB::table('posts')->where('id', $id)->value('slug'))->toBe('he-thong-luu-tru-bess');

    $migration->up();
    expect(DB::table('posts')->where('id', $id)->value('nav_title'))->toBe('Pin lưu trữ');
});

it('does not overwrite a changed editorial label', function () {
    $migration = require base_path('database/migrations/2026_11_12_000300_rename_solution_storage_nav_title.php');
    $id = storageSolutionFixture();
    DB::table('posts')->where('id', $id)->update(['nav_title' => 'Hệ thống BESS']);

    $migration->up();
    expect(DB::table('posts')->where('id', $id)->value('nav_title'))->toBe('Hệ thống BESS');
});

it('exposes the renamed label while preserving the public solution URL', function () {
    $migration = require base_path('database/migrations/2026_11_12_000300_rename_solution_storage_nav_title.php');
    $id = storageSolutionFixture();
    DB::table('posts')->where('id', $id)->update(['nav_title' => 'Lưu trữ điện', 'is_visible' => true]);
    $migration->up();

    $navigation = collect($this->getJson('/api/v1/site')->assertOk()->json('navigation'));
    $solutionLinks = collect($navigation->firstWhere('label', 'Giải pháp')['children']);
    $storage = $solutionLinks->firstWhere('label', 'Pin lưu trữ');

    expect($storage['href'] ?? null)->toBe('/giai-phap/he-thong-luu-tru-bess/');
});
