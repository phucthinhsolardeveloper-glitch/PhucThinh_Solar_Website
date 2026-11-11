<?php

use App\Domain\Commerce\OrderExporter;
use App\Domain\Commerce\OrderPdf;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

function orderAdmin(array $permissions = ['orders']): User
{
    return User::query()->create(['name' => 'Thử', 'username' => 'ord-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => $permissions, 'is_active' => true]);
}

function sampleOrder(string $code, string $date = '2026-11-05 10:00:00'): Order
{
    $id = DB::table('orders')->insertGetId(['code' => $code, 'customer_name' => 'Khách thử', 'phone' => '0900000001', 'address' => '1 Đường Thử', 'payment_code' => 'manual',
        'payment_status' => 'unpaid', 'subtotal' => 1000000, 'shipping_fee' => 30000, 'total' => 1030000, 'status' => 'new', 'created_at' => $date, 'updated_at' => $date]);
    DB::table('order_items')->insert(['order_id' => $id, 'name' => 'Đèn thử', 'regular_price' => 500000, 'sale_price' => 0, 'quantity' => 2]);

    return Order::query()->findOrFail($id);
}

function sheetOf($response): array
{
    $path = tempnam(sys_get_temp_dir(), 'o').'.xlsx';
    copy($response->getFile()->getPathname(), $path);

    return IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);
}

it('exports a list with totals and a single order with its lines', function () {
    $first = sampleOrder('XT-1');
    sampleOrder('XT-2');
    $list = sheetOf(app(OrderExporter::class)->list(Order::query()->whereIn('code', ['XT-1', 'XT-2'])));
    expect(collect($list)->pluck(1)->all())->toContain('XT-1', 'XT-2')
        ->and((float) end($list)[12])->toBe(2060000.0);
    $single = sheetOf(app(OrderExporter::class)->single($first->load('items')));
    expect(collect($single)->pluck(1)->all())->toContain('Đèn thử', 'Khách thử')
        ->and(collect($single)->last()[5])->toEqual(1030000);
});

it('filters orders by date and prints only for staff with the orders permission', function () {
    $old = sampleOrder('XT-OLD', '2026-01-01 10:00:00');
    $new = sampleOrder('XT-NEW');
    $this->actingAs(orderAdmin(), 'admin');
    Livewire\Livewire::test(ListOrders::class)->filterTable('created', ['from' => '2026-11-01'])
        ->assertCanSeeTableRecords([$new])->assertCanNotSeeTableRecords([$old]);
    $this->get('/admin/orders/'.$new->id.'/in')->assertOk()->assertSee('XT-NEW')->assertSee('1.030.000đ');

    $this->flushSession();
    $this->actingAs(orderAdmin(['catalog']), 'admin');
    $this->get('/admin/orders/'.$new->id.'/in')->assertForbidden();
});

it('downloads one order as a PDF sheet with Vietnamese text', function () {
    $this->actingAs(orderAdmin(), 'admin');
    $order = sampleOrder('XT-PDF');
    $pdf = app(OrderPdf::class)->render($order);
    expect(substr($pdf, 0, 5))->toBe('%PDF-')->and(strlen($pdf))->toBeGreaterThan(5000);

    Livewire\Livewire::test(ListOrders::class)->callTableAction('pdf', $order)->assertFileDownloaded('phieu-don-hang-XT-PDF.pdf');
});
