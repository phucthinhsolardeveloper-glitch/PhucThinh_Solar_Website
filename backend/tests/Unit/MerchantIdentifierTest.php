<?php

use App\Domain\Catalog\MerchantIdentifier;

it('preserves UPC-A leading zeroes and validates supported GTIN lengths and check digits', function () {
    expect(MerchantIdentifier::gtin('042100005264'))->toBe('042100005264')
        ->and(MerchantIdentifier::gtin('036000291452'))->toBe('036000291452')
        ->and(MerchantIdentifier::gtin('96385074'))->toBe('96385074')
        ->and(MerchantIdentifier::gtin('12345670'))->toBe('12345670')
        ->and(MerchantIdentifier::gtin('12345671'))->toBe('')
        ->and(MerchantIdentifier::gtin('14006381333938'))->toBe('14006381333938')
        ->and(MerchantIdentifier::gtin('14006381333937'))->toBe('')
        ->and(MerchantIdentifier::gtin('20036000291456'))->toBe('20036000291456')
        ->and(MerchantIdentifier::gtin('4 006-3813-33931'))->toBe('4006381333931')
        ->and(MerchantIdentifier::gtin('4006381333932'))->toBe('')
        ->and(MerchantIdentifier::gtin('000000000000'))->toBe('')
        ->and(MerchantIdentifier::gtin('SKU-123'))->toBe('')
        ->and(MerchantIdentifier::gtin('1234567'))->toBe('');
});

it('rejects restricted and coupon GTIN ranges', function () {
    expect(MerchantIdentifier::gtin('200000000004'))->toBe('')
        ->and(MerchantIdentifier::gtin('400000000008'))->toBe('')
        ->and(MerchantIdentifier::gtin('0200000000004'))->toBe('')
        ->and(MerchantIdentifier::gtin('500000000005'))->toBe('')
        ->and(MerchantIdentifier::gtin('050000000005'))->toBe('050000000005')
        ->and(MerchantIdentifier::gtin('9800000000007'))->toBe('')
        ->and(MerchantIdentifier::gtin('01234565'))->toBe('')
        ->and(MerchantIdentifier::gtin('21234569'))->toBe('')
        ->and(MerchantIdentifier::gtin('97123453'))->toBe('');
});

it('limits manufacturer part numbers and keeps unknown identifier state distinct from confirmed absence', function () {
    expect(MerchantIdentifier::mpn('  Model   A-1  '))->toBe('Model   A-1')
        ->and(MerchantIdentifier::mpn(str_repeat('X', 71)))->toBe('')
        ->and(MerchantIdentifier::exists('042100005264', '', false, false, true))->toBe('yes')
        ->and(MerchantIdentifier::exists('', 'MPN-1', true, false, true))->toBe('yes')
        ->and(MerchantIdentifier::exists('', 'MPN-1', false, false, true))->toBe('')
        ->and(MerchantIdentifier::exists('', '', true, true, false))->toBe('no')
        ->and(MerchantIdentifier::exists('', '', false, true, false))->toBe('no')
        ->and(MerchantIdentifier::exists('', '', true, true, true))->toBe('');
});
