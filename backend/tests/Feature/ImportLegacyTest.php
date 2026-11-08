<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

it('keeps existing app data when a fresh legacy import fails', function () {
    $username = 'import-rollback-'.bin2hex(random_bytes(4));
    DB::table('users')->insert([
        'name' => 'Import rollback probe', 'username' => $username, 'password' => 'probe',
        'role' => 'editor', 'permissions' => '[]', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $beforeProducts = DB::table('products')->count();

    config()->set('database.connections.legacy.database', 'pts_import_missing_source');
    DB::purge('legacy');

    expect(fn () => Artisan::call('pts:import-legacy', ['--fresh' => true]))->toThrow(QueryException::class);
    expect(DB::table('users')->where('username', $username)->exists())->toBeTrue()
        ->and(DB::table('products')->count())->toBe($beforeProducts);
});
