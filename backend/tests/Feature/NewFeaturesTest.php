<?php

use App\Filament\Support\NewFeatures;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

function featureUser(): User
{
    return User::query()->create(['name' => 'Thử', 'username' => 'nf-'.bin2hex(random_bytes(4)), 'password' => 'test-password', 'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

it('shows a Mới label until that account opens the page, and only for that account', function () {
    [$a, $b] = [featureUser(), featureUser()];
    expect(NewFeatures::isNew('kho-chi-nhanh', $a))->toBeTrue();

    $this->actingAs($a, 'admin');
    expect(NewFeatures::badge('kho-chi-nhanh'))->toBe('Mới')->and((string) NewFeatures::label('Kho giữ hàng', 'kho-chi-nhanh'))->toContain('Mới')->toContain('Kho giữ hàng');

    NewFeatures::markVisited(Request::create('/admin/store-locations', 'GET'), $a);
    expect(NewFeatures::isNew('kho-chi-nhanh', $a->fresh()))->toBeFalse()
        ->and(NewFeatures::isNew('kho-san-pham', $a->fresh()))->toBeTrue()   // another feature, still new
        ->and(NewFeatures::isNew('kho-chi-nhanh', $b->fresh()))->toBeTrue(); // another person, still new
    expect(NewFeatures::label('Kho giữ hàng', 'kho-chi-nhanh'))->toBe('Kho giữ hàng');
});

it('ignores page loads that are not a plain visit, and visits to other pages', function () {
    $user = featureUser();
    NewFeatures::markVisited(Request::create('/admin/store-locations', 'POST'), $user);
    NewFeatures::markVisited(Request::create('/admin/orders', 'GET'), $user);
    expect(NewFeatures::isNew('kho-chi-nhanh', $user->fresh()))->toBeTrue()->and(NewFeatures::isNew('kho-don-hang', $user->fresh()))->toBeTrue();
    NewFeatures::markVisited(Request::create('/admin/orders/12/edit', 'GET'), $user);
    expect(NewFeatures::isNew('kho-don-hang', $user->fresh()))->toBeFalse();
});

it('stops showing a label after a few weeks even if the page was never opened', function () {
    $user = featureUser();
    expect(NewFeatures::isNew('mau-email', $user))->toBeTrue();
    Carbon::setTestNow(now()->addDays(NewFeatures::SHOW_DAYS + 40));
    expect(NewFeatures::isNew('mau-email', $user))->toBeFalse();
    Carbon::setTestNow();
});

it('marks the feature seen when the Admin page is really opened, after the page was built', function () {
    $user = featureUser();
    $this->actingAs($user, 'admin')->get('/admin/store-locations')->assertOk()->assertSee('Mới');
    expect(NewFeatures::isNew('kho-chi-nhanh', $user->fresh()))->toBeFalse();
});
