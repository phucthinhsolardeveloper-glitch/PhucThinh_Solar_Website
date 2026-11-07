<?php

use App\Domain\Catalog\ProductDetail\ProductContentResolver;
use Illuminate\Support\Facades\DB;

// The modes real data does not use yet (hide, both, stack, brand, list, missing and hidden sources).
// Rows are written to the test database only, with ids far outside the real ones.

const P = 990001;
const BRAND = 990002;
const CAT = 990003;
const LIST_ID = 990004;

function section(string $scope, int $id, array $blocks, string $mode = '', string $source = '', string $status = 'hienthi'): void
{
    DB::connection('legacy')->table('table_content_section')->insert([
        'scope' => $scope, 'scope_id' => $id, 'mode' => $mode, 'inherit_source' => $source, 'status' => $status,
        'blocksvi' => htmlspecialchars(json_encode($blocks, JSON_UNESCAPED_UNICODE), ENT_QUOTES), 'blocksen' => '', 'date_updated' => 0,
    ]);
}

function block(string $text): array
{
    return ['type' => 'paragraph', 'text' => $text];
}

function resolveTest(): array
{
    return (new ProductContentResolver)->resolve(['id' => P, 'id_brand' => BRAND, 'id_cat' => CAT, 'id_list' => LIST_ID]);
}

function texts(array $resolved): array
{
    return array_column($resolved['blocks'], 'text');
}

afterEach(function () {
    DB::connection('legacy')->table('table_content_section')->whereIn('scope_id', [P, BRAND, CAT, LIST_ID])->delete();
});

it('returns nothing when no section exists', function () {
    expect(resolveTest())->toBe(['blocks' => [], 'source' => '']);
});

it('prefers brand, then level-2, then level-1 category in auto mode', function () {
    section('list', LIST_ID, [block('list')]);
    expect(resolveTest()['source'])->toBe('list');
    section('cat', CAT, [block('cat')]);
    expect(resolveTest()['source'])->toBe('cat');
    section('brand', BRAND, [block('brand')]);
    expect(texts(resolveTest()))->toBe(['brand']);
});

it('hides everything in hide mode', function () {
    section('brand', BRAND, [block('brand')]);
    section('product', P, [block('own')], 'hide');
    expect(resolveTest())->toBe(['blocks' => [], 'source' => 'hide']);
});

it('reports own with no blocks when the product chose its own but left it empty', function () {
    section('brand', BRAND, [block('brand')]);
    section('product', P, [], 'own');
    expect(resolveTest())->toBe(['blocks' => [], 'source' => 'own']);
});

it('puts own blocks first and inherited after in both mode', function () {
    section('cat', CAT, [block('cat')]);
    section('product', P, [block('own')], 'both');
    expect(texts(resolveTest()))->toBe(['own', 'cat'])->and(resolveTest()['source'])->toBe('both');
});

it('stacks level-2, level-1 then brand', function () {
    section('brand', BRAND, [block('brand')]);
    section('list', LIST_ID, [block('list')]);
    section('cat', CAT, [block('cat')]);
    section('product', P, [], 'inherit', 'stack');
    expect(texts(resolveTest()))->toBe(['cat', 'list', 'brand'])->and(resolveTest()['source'])->toBe('stack');
});

it('does not fall back from brand-only when the brand is empty', function () {
    section('cat', CAT, [block('cat')]);
    section('product', P, [], 'inherit', 'brand');
    expect(resolveTest())->toBe(['blocks' => [], 'source' => '']);
});

it('ignores hidden sources and malformed blocks', function () {
    section('brand', BRAND, [block('brand')], status: '');
    section('cat', CAT, [['text' => 'no type'], block('cat')]);
    expect(texts(resolveTest()))->toBe(['cat']);
});
