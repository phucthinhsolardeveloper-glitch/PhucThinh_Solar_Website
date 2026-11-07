<?php

use App\Filament\Pages\Auth\Login;
use App\Models\Legacy\AdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Admins sign in to Filament with their table_user account, and signing in never writes to table_user.

function legacyAdmin(array $overrides = []): int
{
    $secret = (string) config('legacy.admin_password.secret');
    $salt = (string) config('legacy.admin_password.salt');

    return DB::connection('legacy')->table('table_user')->insertGetId(array_merge([
        'username' => 'lv-test-'.bin2hex(random_bytes(4)),
        'password' => md5($secret.'mat-khau-thu'.$salt),
        'fullname' => 'Tài khoản thử',
        'status' => 'hienthi',
        'role' => 3,
        'id_permission' => 0,
        'user_token' => 'token-cu',
        'login_session' => 'phien-cu',
        'lastlogin' => '1700000000',
    ], $overrides));
}

afterEach(function () {
    DB::connection('legacy')->table('table_user')->where('username', 'like', 'lv-test-%')->delete();
});

it('signs in with the old md5 password and leaves the row untouched', function () {
    $id = legacyAdmin();
    $before = (array) DB::connection('legacy')->table('table_user')->find($id);

    expect(Auth::guard('admin')->attempt(['username' => $before['username'], 'password' => 'mat-khau-thu']))->toBeTrue()
        ->and(Auth::guard('admin')->id())->toBe($id);

    $after = (array) DB::connection('legacy')->table('table_user')->find($id);
    expect($after)->toEqual($before);
});

it('signs in with a modern hash too', function () {
    $id = legacyAdmin(['password' => password_hash('mat-khau-moi', PASSWORD_DEFAULT)]);
    $username = DB::connection('legacy')->table('table_user')->where('id', $id)->value('username');

    expect(Auth::guard('admin')->attempt(['username' => $username, 'password' => 'mat-khau-moi']))->toBeTrue();
});

it('refuses a wrong password and an account that is not active', function () {
    $id = legacyAdmin();
    $username = DB::connection('legacy')->table('table_user')->where('id', $id)->value('username');
    $hidden = legacyAdmin(['status' => '']);
    $hiddenName = DB::connection('legacy')->table('table_user')->where('id', $hidden)->value('username');

    expect(Auth::guard('admin')->attempt(['username' => $username, 'password' => 'sai']))->toBeFalse()
        ->and(Auth::guard('admin')->attempt(['username' => $hiddenName, 'password' => 'mat-khau-thu']))->toBeFalse();
});

it('reads permissions the way the PHP admin does', function () {
    $id = legacyAdmin(['role' => 1, 'id_permission' => 0]);
    $user = AdminUser::query()->find($id);

    expect($user->isSuperAdmin())->toBeFalse()
        ->and($user->permissionKeys())->toBe([]);
});

it('refuses to save changes to an admin row', function () {
    $user = AdminUser::query()->find(legacyAdmin());
    $user->fullname = 'Đổi tên';

    expect($user->save())->toBeFalse();
});

it('serves the username login page', function () {
    $this->get('/admin-next/login')->assertOk()->assertSee('Tên đăng nhập');
});

it('signs in through the Filament login form', function () {
    $id = legacyAdmin();
    $username = DB::connection('legacy')->table('table_user')->where('id', $id)->value('username');
    filament()->setCurrentPanel(filament()->getPanel('admin'));

    Livewire\Livewire::test(Login::class)
        ->fillForm(['username' => $username, 'password' => 'mat-khau-thu'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(Auth::guard('admin')->id())->toBe($id);
});
