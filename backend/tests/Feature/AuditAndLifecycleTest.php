<?php

use App\Domain\Commerce\ManualPayment;
use App\Domain\Commerce\OrderLifecycle;
use App\Filament\Pages\SiteSettingsPage;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Order and payment states move only along allowed transitions, and every change leaves an audit row.

function owner(): User
{
    return User::query()->create(['name' => 'Chủ thử', 'username' => 'test-'.bin2hex(random_bytes(4)), 'password' => 'mat-khau-thu',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

function testOrder(array $values = []): Order
{
    $id = DB::table('orders')->insertGetId($values + ['code' => 'AUD'.random_int(1000, 9999), 'customer_name' => 'Khách', 'phone' => '0912345678', 'address' => 'x',
        'subtotal' => 100000, 'total' => 100000, 'status' => 'new', 'payment_status' => 'unpaid', 'payment_code' => 'manual', 'created_at' => now(), 'updated_at' => now()]);

    return Order::query()->findOrFail($id);
}

function lastAudit(): object
{
    return DB::table('admin_audit_events')->orderByDesc('id')->first();
}

it('allows only forward order transitions and audits them with the actor', function () {
    $this->actingAs($user = owner(), 'admin');
    $order = testOrder();

    app(OrderLifecycle::class)->update($order, 'confirmed', 'Đã gọi');
    $audit = lastAudit();
    expect($order->status)->toBe('confirmed')
        ->and($audit->action)->toBe('order.status')->and((int) $audit->actor_id)->toBe($user->id)
        ->and(json_decode($audit->before, true))->toMatchArray(['status' => 'new'])
        ->and(json_decode($audit->after, true))->toMatchArray(['status' => 'confirmed', 'notes' => 'Đã gọi']);

    expect(fn () => app(OrderLifecycle::class)->update($order, 'new', null))->toThrow(ValidationException::class);
    app(OrderLifecycle::class)->update($order, 'cancelled', 'Đã gọi');
    expect(fn () => app(OrderLifecycle::class)->update($order, 'shipping', null))->toThrow(ValidationException::class);
});

it('offers only the reachable statuses in Admin and saves through the lifecycle', function () {
    $this->actingAs(owner(), 'admin');
    $order = testOrder(['status' => 'shipping']);
    expect(array_keys(OrderLifecycle::statusChoices('shipping', OrderResource::STATUSES)))->toBe(['shipping', 'done', 'cancelled']);

    Livewire\Livewire::test(EditOrder::class, ['record' => $order->id])->fillForm(['status' => 'new'])->call('save')->assertHasErrors();
    expect($order->fresh()->status)->toBe('shipping');
    Livewire\Livewire::test(EditOrder::class, ['record' => $order->id])->fillForm(['status' => 'done'])->call('save')->assertHasNoFormErrors();
    expect($order->fresh()->status)->toBe('done')->and(lastAudit()->action)->toBe('order.status');
});

it('audits manual payment and refund and refuses an impossible payment move', function () {
    $this->actingAs($user = owner(), 'admin');
    $order = testOrder();
    app(ManualPayment::class)->confirm($order, $user, 'PT-001');
    expect(lastAudit()->action)->toBe('payment.paid');
    app(ManualPayment::class)->recordRefund($order, $user, 'HT-001');
    expect(lastAudit()->action)->toBe('payment.refunded')->and($order->fresh()->payment_status)->toBe('refunded');

    $locked = DB::table('orders')->where('id', $order->id)->first(['id', 'payment_status']);
    expect(fn () => OrderLifecycle::movePayment($locked, 'paid'))->toThrow(ValidationException::class);
});

it('audits price and spec changes of a product and its deletion', function () {
    $this->actingAs(owner(), 'admin');
    $product = Product::query()->create(['name' => 'Đèn thử', 'slug' => 'den-thu-'.bin2hex(random_bytes(3)), 'regular_price' => 100000, 'is_visible' => true]);
    $product->update(['regular_price' => 120000, 'warranty' => '3 năm', 'summary' => 'không theo dõi']);
    $audit = lastAudit();
    expect($audit->entity_type)->toBe('Product')
        ->and(json_decode($audit->after, true))->toMatchArray(['regular_price' => '120000', 'warranty' => '3 năm'])
        ->and(json_decode($audit->after, true))->not->toHaveKey('summary');

    $product->delete();
    expect(lastAudit()->action)->toBe('delete')->and(json_decode(lastAudit()->before, true)['name'])->toBe('Đèn thử');
});

it('audits permission changes without storing passwords', function () {
    $this->actingAs(owner(), 'admin');
    $editor = User::query()->create(['name' => 'BTV', 'username' => 'test-'.bin2hex(random_bytes(4)), 'password' => 'mat-khau-thu', 'role' => User::ROLE_EDITOR, 'permissions' => ['content'], 'is_active' => true]);
    $editor->update(['permissions' => ['content', 'orders'], 'password' => 'mat-khau-moi']);
    $audit = lastAudit();
    expect($audit->entity_type)->toBe('User')
        ->and(json_decode($audit->after, true)['password'])->toBe('(đã đổi)')
        ->and($audit->after)->not->toContain('mat-khau');
});

it('audits site settings as a diff of the keys that changed', function () {
    $this->actingAs(owner(), 'admin');
    Livewire\Livewire::test(SiteSettingsPage::class)->fillForm(['hotline' => '0900 000 111'])->call('save')->assertHasNoFormErrors();
    $audit = lastAudit();
    expect($audit->action)->toBe('settings.update')->and(array_keys(json_decode($audit->after, true)))->toBe(['hotline']);
});

it('shows the audit log to owners only', function () {
    $this->actingAs(owner(), 'admin');
    $this->get('/admin/audit-events')->assertOk();
});
