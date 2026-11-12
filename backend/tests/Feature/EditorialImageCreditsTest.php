<?php

use App\Domain\News\EditorialImageCredits;

it('shows short source credit for licensed photographs and self-created illustrations', function () {
    expect(EditorialImageCredits::forFile('pts-light-installation.webp'))
        ->toBe([
            'credit' => 'Ảnh: finix photographer / Pexels',
            'source' => 'https://www.pexels.com/photo/solar-street-light-amidst-lush-green-trees-32915849/',
        ])
        ->and(EditorialImageCredits::forFile('pts-light-benefits.webp'))
        ->toBe(['credit' => 'Hình minh họa: Phúc Thịnh Solar', 'source' => ''])
        ->and(EditorialImageCredits::forFile('pts-plan-015.webp'))
        ->toBe(['credit' => 'Hình minh họa: Phúc Thịnh Solar', 'source' => ''])
        ->and(EditorialImageCredits::forFile('pts-photo-012.webp'))
        ->toBe([
            'credit' => 'Ảnh: Quang Nguyen Vinh / Pexels',
            'source' => 'https://www.pexels.com/photo/vast-solar-panel-farm-in-scenic-landscape-35105442/',
        ])
        ->and(EditorialImageCredits::forFile('pts-explainer-inverter-types.webp'))
        ->toBe(['credit' => 'Hình minh họa: Phúc Thịnh Solar', 'source' => ''])
        ->and(EditorialImageCredits::forFile('unknown.jpg'))
        ->toBe(['credit' => '', 'source' => '']);
});

it('credits each newly curated photograph for PTS-KT-016 through 021', function () {
    $base = base_path('resources/editorial/2026-11-12');
    $manifest = json_decode((string) file_get_contents($base.'/photo-cover-selections.json'), true, flags: JSON_THROW_ON_ERROR);
    $byKey = collect($manifest['entries'])->keyBy('plan_key');
    $files = [];

    foreach (range(16, 21) as $number) {
        $key = sprintf('PTS-KT-%03d', $number);
        $entry = $byKey->get($key);
        expect($entry)->toBeArray()
            ->and($entry['type'])->toBe('licensed_photo');

        $filename = sprintf('pts-photo-%03d.webp', $number);
        expect($entry['cover_file'])->toBe($filename)
            ->and($entry['image_id'])->toBeInt()
            ->and(is_file(config('site.assets_path').'/images/editorial/'.$filename))->toBeTrue();

        $credit = EditorialImageCredits::forFile($filename);
        expect($credit['credit'])->toContain('/ Pexels')
            ->and($credit['source'])->toBe($entry['source_url'])
            ->and($credit['source'])->toStartWith('https://www.pexels.com/photo/');
        $files[] = $filename;
    }
    expect(count(array_unique($files)))->toBe(6);
});
