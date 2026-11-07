<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Admins sign in with a username. A carried-over md5 hash works once and is replaced by bcrypt.

function adminAccount(array $overrides = []): User
{
    $secret = (string) config('site.admin_password.secret');
    $salt = (string) config('site.admin_password.salt');
    $id = DB::table('users')->insertGetId(array_merge([
        'name' => 'Tài khoản thử',
        'username' => 'test-'.bin2hex(random_bytes(4)),
        'password' => md5($secret.'mat-khau-thu'.$salt),
        'role' => User::ROLE_EDITOR,
        'permissions' => json_encode(['product_man']),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));

    return User::query()->findOrFail($id);
}

it('accepts an old md5 password once and stores bcrypt instead', function () {
    $user = adminAccount();

    expect(Auth::guard('admin')->attempt(['username' => $user->username, 'password' => 'mat-khau-thu']))->toBeTrue()
        ->and(Auth::guard('admin')->id())->toBe($user->id);

    $stored = (string) DB::table('users')->where('id', $user->id)->value('password');
    expect(password_get_info($stored)['algo'])->not->toBeNull()
        ->and(Hash::check('mat-khau-thu', $stored))->toBeTrue();

    Auth::guard('admin')->logout();
    expect(Auth::guard('admin')->attempt(['username' => $user->username, 'password' => 'mat-khau-thu']))->toBeTrue();
});

it('signs in with a bcrypt password', function () {
    $user = adminAccount(['password' => Hash::make('mat-khau-moi')]);

    expect(Auth::guard('admin')->attempt(['username' => $user->username, 'password' => 'mat-khau-moi']))->toBeTrue();
});

it('refuses a wrong password and an inactive account', function () {
    $user = adminAccount();
    $hidden = adminAccount(['is_active' => false]);

    expect(Auth::guard('admin')->attempt(['username' => $user->username, 'password' => 'sai']))->toBeFalse()
        ->and(Auth::guard('admin')->attempt(['username' => $hidden->username, 'password' => 'mat-khau-thu']))->toBeFalse();
});

it('grants owners everything and editors only their list', function () {
    $owner = adminAccount(['role' => User::ROLE_OWNER, 'permissions' => json_encode([])]);
    $editor = adminAccount();

    expect($owner->hasPermission('news_man'))->toBeTrue()
        ->and($editor->hasPermission('product_man'))->toBeTrue()
        ->and($editor->hasPermission('news_man'))->toBeFalse();
});

it('serves the username login page', function () {
    $this->get('/admin-next/login')->assertOk()->assertSee('Tên đăng nhập');
});

it('signs in through the Filament login form', function () {
    $user = adminAccount();
    filament()->setCurrentPanel(filament()->getPanel('admin'));

    Livewire\Livewire::test(Login::class)
        ->fillForm(['username' => $user->username, 'password' => 'mat-khau-thu'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(Auth::guard('admin')->id())->toBe($user->id);
});
