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
    Config::set('shipping.nhattin.mode', 'sandbox');
    Config::set('shipping.nhattin.username', 'pts');
    Config::set('shipping.nhattin.password', 'SECRET-NTL-DO-NOT-SHOW');

    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'integration-owner', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $this->get('/admin/tich-hop')->assertOk()->assertSee('Nhất Tín')->assertSee('Thiếu partner_id')
        ->assertDontSee('SECRET-VNPAY-DO-NOT-SHOW')->assertDontSee('SECRET-NTL-DO-NOT-SHOW')->assertDontSee('GHN');
    $states = app(IntegrationReadiness::class)->all();
    expect(collect($states)->firstWhere('label', 'Nhất Tín')['state'])->toBe('Sandbox · chưa sẵn sàng');
    Config::set('shipping.nhattin.mode', 'mock');
    expect(collect(app(IntegrationReadiness::class)->all())->firstWhere('label', 'Nhất Tín')['state'])->toBe('Mô phỏng (chỉ thử nghiệm)');
    Config::set('commerce.vnpay.endpoint', 'khong-phai-url');
    expect(collect(app(IntegrationReadiness::class)->all())->firstWhere('label', 'VNPay')['state'])->toBe('URL không hợp lệ');

    $editor = User::query()->create(['name' => 'Biên tập', 'username' => 'integration-editor', 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['catalog'], 'is_active' => true]);
    $this->flushSession();
    $this->actingAs($editor, 'admin');
    $this->get('/admin/tich-hop')->assertForbidden();
});

it('labels mock Nhất Tín as unavailable to customers on production', function () {
    Config::set('shipping.nhattin.mode', 'mock');
    app()->detectEnvironment(fn () => 'production');
    try {
        $item = collect(app(IntegrationReadiness::class)->all())->firstWhere('label', 'Nhất Tín');
        expect($item['state'])->toBe('Chưa dùng · phí theo Phường/Xã')
            ->and($item['detail'])->toContain('không tạo vận đơn Nhất Tín');
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});
