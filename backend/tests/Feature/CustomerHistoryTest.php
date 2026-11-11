<?php

use App\Domain\Commerce\CustomerHistory;
use Illuminate\Support\Facades\DB;

it('recognises a returning customer by phone, whatever way the number was typed', function () {
    $phone = '0912'.random_int(100000, 999999);
    $make = fn (string $typed, float $total, string $status = 'done') => DB::table('orders')->insertGetId(['code' => 'CH'.bin2hex(random_bytes(3)), 'customer_name' => 'Khách', 'phone' => $typed,
        'address' => 'x', 'payment_status' => 'paid', 'subtotal' => $total, 'shipping_fee' => 0, 'total' => $total, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
    $first = $make($phone, 1000000);
    $make('+84 '.substr($phone, 1, 3).' '.substr($phone, 4), 2000000);
    $make($phone, 9000000, 'cancelled');
    DB::table('leads')->insert(['kind' => 'contact', 'name' => 'Khách', 'phone' => '84'.substr($phone, 1), 'status' => 'contacted', 'created_at' => now(), 'updated_at' => now()]);

    expect(CustomerHistory::normalise('+84 912 345 678'))->toBe('0912345678')
        ->and(app(CustomerHistory::class)->label($phone, exceptOrder: $first))->toBe('Khách cũ: 1 đơn (2.000.000đ) · 1 liên hệ trước')
        ->and(app(CustomerHistory::class)->label('0900000000'))->toBe('');
});
