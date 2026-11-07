<?php

use Illuminate\Support\Facades\DB;

// The app boots, reads its database and serves the Filament login.

it('reports healthy with the database reachable', function () {
    $response = $this->getJson('/health');

    $response->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database', 'ok');
    expect($response->json('checks.products'))->toBeInt()->toBeGreaterThan(0);
});

it('keeps app tables without a prefix', function () {
    expect(DB::connection()->getTablePrefix())->toBe('');
});

it('serves the Filament login at /admin-next', function () {
    $this->get('/admin-next/login')->assertOk();
});
