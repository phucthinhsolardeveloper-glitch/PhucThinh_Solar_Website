<?php

// Phase 1 gate: the app boots, reads both connections and serves the Filament login, without writing to legacy tables.

it('reports healthy with both database connections', function () {
    $response = $this->getJson('/health');

    $response->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database', 'ok');
    expect($response->json('checks.legacy_products'))->toBeInt()->toBeGreaterThan(0);
});

it('keeps Laravel tables under the lv_ prefix', function () {
    expect(DB::connection()->getTablePrefix())->toBe('lv_')
        ->and(DB::connection('legacy')->getTablePrefix())->toBe('');
});

it('serves the Filament login at /admin-next', function () {
    $this->get('/admin-next/login')->assertOk();
});
