<?php

use Illuminate\Support\Facades\DB;

it('gives a project without SEO text or summary a description taken from its story', function () {
    $id = DB::table('posts')->insertGetId(['kind' => 'project', 'title' => 'Dự án thử mô tả', 'slug' => 'du-an-thu-mo-ta-'.bin2hex(random_bytes(3)),
        'summary' => '', 'body' => '<p>Hệ thống <strong>10 kWp</strong> áp mái cho gia đình.</p>', 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()]);
    $slug = DB::table('posts')->where('id', $id)->value('slug');

    $this->getJson('/api/v1/project-detail?slug='.$slug)->assertOk()
        ->assertJsonPath('seo.description', 'Hệ thống 10 kWp áp mái cho gia đình.');

    DB::table('posts')->where('id', $id)->update(['body' => '']);
    expect($this->getJson('/api/v1/project-detail?slug='.$slug)->json('seo.description'))->toStartWith('Dự án thử mô tả')->toContain('Phúc Thịnh Solar');
});
