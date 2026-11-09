<?php

use App\Domain\Solar\SolarEstimator;
use App\Filament\Pages\SolarEstimatorPage;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function estimatorOn(array $overrides = []): void
{
    DB::table('settings')->updateOrInsert(['key' => SolarEstimator::SETTING], ['value' => json_encode(['enabled' => true] + $overrides)]);
    app(SiteSettings::class)->clear();
}

it('stays hidden until the owner switches it on', function () {
    $this->getJson('/api/v1/solar-estimator')->assertOk()->assertJsonPath('enabled', false);
    $this->postJson('/api/v1/solar-estimate', ['type' => 'household', 'bill' => 2000000, 'region' => 'nam'])->assertNotFound();
});

it('reads usage back from the tiered bill and sizes for daytime use', function () {
    estimatorOn();
    $r = $this->postJson('/api/v1/solar-estimate', ['type' => 'household', 'bill' => 2000000, 'region' => 'tay-nguyen', 'habit' => 'mixed'])->assertOk()->json('result');

    // 2.000.000đ incl. 8% VAT under the 2025 tiers is about 625 kWh; half by day at 4.3 kWh/kWp → 2.5 kWp → 5 panels of 620W.
    expect($r['usageKwh'])->toBeBetween(620, 630)
        ->and($r['panels'])->toBe(5)->and($r['kwp'])->toBe(3.1)
        ->and($r['saving'])->toBeGreaterThan(800000)->toBeLessThan(1200000)
        ->and($r['cost']['low'])->toBeLessThan($r['cost']['high'])
        ->and($r['payback']['low'])->toBeGreaterThan(1)
        ->and($r['summary'])->toContain('3,1 kWp')->toContain('Hoàn vốn');
});

it('caps the system at the roof, adds a battery and prices businesses per kWh', function () {
    estimatorOn();
    $small = $this->postJson('/api/v1/solar-estimate', ['type' => 'household', 'bill' => 5000000, 'region' => 'nam', 'roof_m2' => 15])->json('result');
    expect($small['limitedByRoof'])->toBeTrue()->and($small['roofM2'])->toBeLessThanOrEqual(18);

    $battery = $this->postJson('/api/v1/solar-estimate', ['type' => 'household', 'bill' => 3000000, 'region' => 'nam', 'battery' => true])->json('result');
    expect($battery['batteryKwh'])->toBeGreaterThanOrEqual(5)->and($battery['summary'])->toContain('pin lưu trữ');

    $factory = $this->postJson('/api/v1/solar-estimate', ['type' => 'business', 'bill' => 100000000, 'region' => 'bac', 'habit' => 'day', 'price' => 2500])->json('result');
    expect($factory['usageKwh'])->toBe((int) round(100000000 / 1.08 / 2500))->and($factory['kwp'])->toBeGreaterThan(200);
});

it('refuses bills outside the range and unknown choices', function () {
    estimatorOn();
    $this->postJson('/api/v1/solar-estimate', ['type' => 'household', 'bill' => 1000, 'region' => 'mars'])->assertStatus(422)
        ->assertJsonPath('ok', false)->assertJsonCount(2, 'errors');
});

it('lets settings staff change the figures, records it, and keeps the last tier open', function () {
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'chu-'.bin2hex(random_bytes(3)), 'password' => 'test-password', 'role' => User::ROLE_OWNER, 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    Livewire::test(SolarEstimatorPage::class)
        ->set('data.enabled', true)->set('data.price_household', 12000000)
        ->call('save')->assertHasNoErrors();
    $c = app(SolarEstimator::class)->config();
    expect($c['enabled'])->toBeTrue()->and($c['price_per_kwp']['household'])->toBe(12000000)
        ->and(end($c['tiers'])[0])->toBeNull()->and(count($c['regions']))->toBe(4)
        ->and(DB::table('admin_audit_events')->where('action', 'settings.update')->count())->toBe(1);

    $editor = User::query()->create(['name' => 'Biên tập', 'username' => 'bt-'.bin2hex(random_bytes(3)), 'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => ['content'], 'is_active' => true]);
    $this->actingAs($editor, 'admin');
    expect(SolarEstimatorPage::canAccess())->toBeFalse();
});
