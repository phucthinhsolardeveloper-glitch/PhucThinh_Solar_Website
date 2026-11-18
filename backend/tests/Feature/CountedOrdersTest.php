<?php

use App\Domain\Analytics\CountedOrders;
use Illuminate\Support\Facades\DB;

// The dashboard counts an order as a sale unless it is cancelled or an online payment that was never completed.

it('leaves out cancelled orders and unpaid VNPay orders, keeps transfer orders and paid VNPay orders', function () {
    $base = ['subtotal' => 100000, 'total' => 100000, 'customer_name' => 'Khách thử', 'phone' => '0912345678', 'address' => 'x', 'created_at' => now(), 'updated_at' => now()];
    $rows = [
        ['code' => 'CNT-TRANSFER', 'status' => 'new', 'payment_code' => 'manual', 'payment_status' => 'unpaid'],
        ['code' => 'CNT-VNPAID', 'status' => 'new', 'payment_code' => 'vnpay', 'payment_status' => 'paid'],
        ['code' => 'CNT-VNFAIL', 'status' => 'new', 'payment_code' => 'vnpay', 'payment_status' => 'unpaid'],
        ['code' => 'CNT-CANCEL', 'status' => 'cancelled', 'payment_code' => 'manual', 'payment_status' => 'unpaid'],
    ];
    foreach ($rows as $row) {
        DB::table('orders')->insert($row + $base);
    }
    $counted = CountedOrders::apply(DB::table('orders'))->where('code', 'like', 'CNT-%')->orderBy('code')->pluck('code')->all();
    expect($counted)->toBe(['CNT-TRANSFER', 'CNT-VNPAID']);
});
