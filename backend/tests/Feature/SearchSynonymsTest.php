<?php

use App\Domain\Catalog\Listing\SearchSynonyms;
use App\Filament\Pages\SiteSettingsPage;
use App\Models\Product;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('turns a keyword into the other words of its group, with or without accents', function () {
    $s = app(SearchSynonyms::class);
    expect(collect($s->variants('Inverter 5kW'))->pluck(0)->all())->toContain('biến tần 5kw')
        ->and(collect($s->variants('bien tan'))->pluck(0)->all())->toContain('inverter')
        ->and($s->variants('đèn bàn'))->toBe([])
        // "pin" alone is not a word of any group, so it is not widened to everything with a battery.
        ->and($s->variants('pin'))->toBe([]);
});

it('finds products under another name of the same thing', function () {
    $tag = bin2hex(random_bytes(3));
    $product = Product::query()->create(['name' => 'Biến tần '.$tag.' 5kW', 'slug' => 'bien-tan-'.$tag.'-5kw', 'regular_price' => 100000, 'is_visible' => true]);
    $ids = fn (string $keyword) => collect($this->getJson('/api/v1/search?keyword='.urlencode($keyword))->assertOk()->json('products'))->pluck('id')->all();

    expect($ids('inverter '.$tag))->toContain($product->id)
        ->and($ids('máy bơm '.$tag))->not->toContain($product->id);
});

it('lets settings staff edit the groups in Admin', function () {
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'chu-'.bin2hex(random_bytes(3)), 'password' => 'test-password', 'role' => User::ROLE_OWNER, 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    Livewire::test(SiteSettingsPage::class)
        ->assertSet('data.search_synonyms', implode("\n", SearchSynonyms::DEFAULTS))
        ->set('data.search_synonyms', "Tủ điện, tủ điện solar, combiner box\n\nmột từ")
        ->call('save')->assertHasNoErrors();
    app(SiteSettings::class)->clear();

    expect(json_decode(DB::table('settings')->where('key', SearchSynonyms::SETTING)->value('value'), true))->toBe(['tủ điện, tủ điện solar, combiner box'])
        ->and(collect(app(SearchSynonyms::class)->variants('combiner box'))->pluck(0)->all())->toBe(['tủ điện', 'tủ điện solar'])
        ->and(app(SearchSynonyms::class)->variants('inverter'))->toBe([]);
});
