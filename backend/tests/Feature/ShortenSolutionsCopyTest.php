<?php

use Illuminate\Support\Facades\DB;

it('shortens the solution copy once, and leaves a row alone that the owner has since edited', function () {
    $migration = require database_path('migrations/2026_11_13_000200_shorten_solutions_copy.php');
    $before = DB::table('page_items')->where('section', 'solutions.cta')->value('title');
    if ($before === null || mb_strtoupper((string) $before) !== (string) $before) {
        $this->markTestSkipped('This copy of the database does not hold the 2026-11-13 production solution texts.');
    }
    DB::table('posts')->where('kind', 'solution')->where('slug', 'he-thong-luu-tru-bess')->update(['summary' => 'Chủ website tự viết lại câu này.']);

    $migration->up();
    expect(DB::table('page_items')->where('section', 'solutions.cta')->value('title'))->toBe('Chưa biết chọn giải pháp nào?')
        ->and((string) DB::table('posts')->where('kind', 'solution')->where('slug', 'dien-mat-troi-ho-gia-dinh')->value('summary'))->toStartWith('Tạo điện ngay trên mái nhà')
        ->and(DB::table('posts')->where('kind', 'solution')->where('slug', 'he-thong-luu-tru-bess')->value('summary'))->toBe('Chủ website tự viết lại câu này.');

    $snapshot = DB::table('posts')->where('kind', 'solution')->orderBy('id')->pluck('summary', 'id')->all();
    $migration->up();
    expect(DB::table('posts')->where('kind', 'solution')->orderBy('id')->pluck('summary', 'id')->all())->toBe($snapshot);
});
