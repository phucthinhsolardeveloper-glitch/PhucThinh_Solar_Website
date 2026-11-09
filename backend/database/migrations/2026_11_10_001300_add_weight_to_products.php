<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Packed weight for the Nhất Tín fee at checkout. Empty means staff quote the delivery by hand.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->decimal('weight_kg', 8, 2)->nullable()->after('sale_price'));
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('weight_kg'));
    }
};
