<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bank transfer by VietQR: each order gets a fixed transfer instruction (account, amount, unique note).
// Money is confirmed by staff against the bank statement; a QR never marks an order paid.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', fn (Blueprint $t) => $t->string('kind', 20)->default('other')->after('name'));
        DB::table('payment_methods')->where('name', 'like', '%chuyển khoản%')->update(['kind' => 'bank_transfer']);
        DB::table('payment_methods')->where('name', 'like', '%tại công ty%')->update(['kind' => 'cash']);
        DB::table('payment_methods')->where('name', 'like', '%giao hàng%')->update(['kind' => 'cod']);

        Schema::table('orders', fn (Blueprint $t) => $t->timestamp('transfer_reported_at')->nullable()->after('payment_reference'));

        Schema::create('payment_instructions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version')->default(1);
            $t->string('bank_bin', 10);
            $t->string('bank_name');
            $t->string('account_no', 40);
            $t->string('account_name');
            $t->decimal('amount', 15, 0);
            $t->string('reference', 25)->unique(); // transfer note, A-Z0-9 only
            $t->text('qr_text');
            $t->string('state', 20)->default('active'); // active | superseded
            $t->timestamps();
            $t->index(['order_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_instructions');
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('transfer_reported_at'));
        Schema::table('payment_methods', fn (Blueprint $t) => $t->dropColumn('kind'));
    }
};
