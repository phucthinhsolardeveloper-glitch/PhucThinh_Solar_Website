<?php

use App\Domain\Content\ContentTidy;
use Illuminate\Support\Facades\DB;

$hosts = ['phucthinhsolar.com'];

it('turns a same-site absolute address into a relative one, and leaves other sites alone', function () use ($hosts) {
    $html = '<p><a href="http://phucthinhsolar.com/san-pham/den/">Đèn</a> '
        .'<a href="https://www.phucthinhsolar.com/tin-tuc/">Tin</a> '
        .'<a href="https://google.com/search">Google</a></p>';

    ['html' => $out, 'changes' => $changes] = ContentTidy::clean($html, $hosts);

    expect($out)->toContain('href="/san-pham/den/"')
        ->toContain('href="/tin-tuc/"')
        ->toContain('href="https://google.com/search"')
        ->and($changes['absolute_link'])->toBe(2);
});

it('keeps an image its size by moving the inline pixels into real attributes', function () use ($hosts) {
    $html = '<p><img src="/upload/filemanager/a.jpg" style="width: 750px; height: 1111px;"></p>';

    ['html' => $out, 'changes' => $changes] = ContentTidy::clean($html, $hosts);

    expect($out)->toContain('width="750"')->toContain('height="1111"')
        ->not->toContain('style=')
        ->and($changes['image_size'])->toBe(2);
});

it('does not overwrite a size the image already states', function () use ($hosts) {
    $out = ContentTidy::clean('<img src="/upload/a.jpg" width="100" style="width: 750px;">', $hosts)['html'];

    expect($out)->toContain('width="100"')->not->toContain('750');
});

it('drops the attributes the website never renders, and empty paragraphs', function () use ($hosts) {
    $html = '<p style="text-align: justify;">Nội dung</p><p>&nbsp;</p><p class="MsoNormal">Hai</p>';

    ['html' => $out, 'changes' => $changes] = ContentTidy::clean($html, $hosts);

    expect($out)->toBe('<p>Nội dung</p><p>Hai</p>')
        ->and($changes['style_attr'])->toBe(1)
        ->and($changes['class_attr'])->toBe(1)
        ->and($changes['empty_paragraph'])->toBe(1);
});

it('changes nothing the second time it runs', function () use ($hosts) {
    $once = ContentTidy::clean('<p style="x"><a href="http://phucthinhsolar.com/a/">A</a></p>', $hosts)['html'];
    ['html' => $twice, 'changes' => $changes] = ContentTidy::clean($once, $hosts);

    expect($twice)->toBe($once)->and(array_filter($changes))->toBe([]);
});

it('writes nothing until --apply, then cleans the row', function () {
    $id = DB::table('products')->insertGetId([
        'name' => 'Thử dọn nội dung', 'slug' => 'thu-don-noi-dung-'.bin2hex(random_bytes(3)),
        'body' => '<p style="text-align: center;">Xin chào</p>', 'is_visible' => false,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->artisan('pts:tidy-content')->assertSuccessful();
    expect(DB::table('products')->where('id', $id)->value('body'))->toContain('style=');

    $this->artisan('pts:tidy-content --apply')->assertSuccessful();
    expect(DB::table('products')->where('id', $id)->value('body'))->toBe('<p>Xin chào</p>');

    DB::table('products')->where('id', $id)->delete();
});
