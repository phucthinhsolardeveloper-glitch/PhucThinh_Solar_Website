<?php

use App\Domain\Integrations\IntegrationReadiness;
use App\Models\User;
use Illuminate\Support\Facades\Config;

it('shows integration readiness without exposing secrets or claiming carrier APIs are ready', function () {
    Config::set('commerce.vnpay.active', true);
    Config::set('commerce.vnpay.tmn_code', 'TESTCODE');
    Config::set('commerce.vnpay.hash_secret', 'SECRET-VNPAY-DO-NOT-SHOW');
    Config::set('commerce.vnpay.return_url', 'https://example.test/return');
    Config::set('commerce.vnpay.ipn_url', 'https://example.test/ipn');
    Config::set('commerce.carriers.ghn.active', true);
    Config::set('commerce.carriers.ghn.required.token', 'SECRET-GHN-DO-NOT-SHOW');
    Config::set('commerce.carriers.ghn.required.shop_id', '123');

    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'integration-owner', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $this->get('/admin/tich-hop')->assertOk()->assertSee('Cần mapping')
        ->assertDontSee('SECRET-VNPAY-DO-NOT-SHOW')->assertDontSee('SECRET-GHN-DO-NOT-SHOW');
    $states = app(IntegrationReadiness::class)->all();
    expect(collect($states)->firstWhere('label', 'GHN')['state'])->toBe('Cần mapping');

    $editor = User::query()->create(['name' => 'Biên tập', 'username' => 'integration-editor', 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['catalog'], 'is_active' => true]);
    $this->flushSession();
    $this->actingAs($editor, 'admin');
    $this->get('/admin/tich-hop')->assertForbidden();
});
