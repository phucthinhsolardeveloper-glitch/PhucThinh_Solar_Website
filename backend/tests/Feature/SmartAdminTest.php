<?php

use App\Domain\Leads\LeadRouting;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Lead;
use App\Models\Product;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function smartUser(array $permissions, string $role = User::ROLE_EDITOR): User
{
    return User::query()->create(['name' => 'NV '.bin2hex(random_bytes(2)), 'username' => 'sm-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => $role, 'permissions' => $permissions, 'is_active' => true]);
}

it('hands a new enquiry to the least busy salesperson when switched on', function () {
    User::query()->where('role', '!=', User::ROLE_OWNER)->update(['is_active' => false]);
    $busy = smartUser(['leads']);
    $free = smartUser(['leads']);
    Lead::query()->create(['kind' => 'contact', 'name' => 'Cũ', 'phone' => '0900000201', 'status' => 'new', 'assigned_to' => $busy->id]);
    $lead = Lead::query()->create(['kind' => 'contact', 'name' => 'Mới', 'phone' => '0900000202', 'status' => 'new']);

    expect(app(LeadRouting::class)->assign($lead->id))->toBeNull(); // off by default
    DB::table('settings')->updateOrInsert(['key' => LeadRouting::SETTING], ['value' => json_encode('1')]);
    app(SiteSettings::class)->clear();
    expect(app(LeadRouting::class)->assign($lead->id)?->id)->toBe($free->id)
        ->and($lead->fresh()->assigned_to)->toBe($free->id)->and($lead->fresh()->follow_up_at)->not->toBeNull()
        ->and($lead->events()->first()->changes['auto'])->toBeTrue();
});

it('sets the call-back time inside working hours', function () {
    expect(LeadRouting::callBackAt(Carbon::parse('2026-11-11 09:00'))->format('Y-m-d H:i'))->toBe('2026-11-11 11:00')
        ->and(LeadRouting::callBackAt(Carbon::parse('2026-11-11 16:30'))->format('Y-m-d H:i'))->toBe('2026-11-12 09:00')
        ->and(LeadRouting::callBackAt(Carbon::parse('2026-11-14 20:00'))->format('Y-m-d H:i'))->toBe('2026-11-16 09:00')
        ->and(LeadRouting::callBackAt(Carbon::parse('2026-11-12 06:00'))->format('Y-m-d H:i'))->toBe('2026-11-12 09:00');
});

it('fills packing weight for many products and shows only the ones that are ready', function () {
    $this->actingAs(smartUser([], User::ROLE_OWNER), 'admin');
    $a = Product::query()->where('is_visible', true)->firstOrFail();
    $b = Product::query()->where('is_visible', true)->whereKeyNot($a->id)->firstOrFail();
    $b->update(['weight_kg' => 9]);
    Livewire::test(ListProducts::class)->callTableBulkAction('setWeight', [$a, $b], ['kg' => 2.5, 'only_empty' => 'empty']);
    expect((float) $a->fresh()->weight_kg)->toBe(2.5)->and((float) $b->fresh()->weight_kg)->toBe(9.0);

    $a->update(['is_visible' => false, 'category_id' => null]);
    $b->update(['is_visible' => false]);
    Livewire::test(ListProducts::class)->callTableBulkAction('show', [$a, $b])->assertNotified('Đã hiện 1 sản phẩm');
    expect($a->fresh()->is_visible)->toBeFalse()->and($b->fresh()->is_visible)->toBeTrue();
});

it('writes the weekly report', function () {
    Artisan::call('pts:weekly-report', ['--dry-run' => true]);
    expect(Artisan::output())->toContain('Báo cáo tuần')->toContain('Doanh số')->toContain('Đang chờ xử lý');
});
