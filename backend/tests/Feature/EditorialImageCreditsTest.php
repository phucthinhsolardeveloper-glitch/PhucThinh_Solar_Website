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
        ->and(EditorialImageCredits::forFile('unknown.jpg'))
        ->toBe(['credit' => '', 'source' => '']);
});
