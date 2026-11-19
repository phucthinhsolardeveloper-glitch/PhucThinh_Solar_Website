<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shipping warehouses. A branch row can be marked as a warehouse and carries its own sender (manager name, phone, ward);
 * each product says which warehouse holds it; each order records the warehouse that ships it.
 * Existing branches whose name starts with "Kho " are marked as warehouses; senders are filled in Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_locations', function (Blueprint $t) {
            $t->boolean('is_warehouse')->default(false)->after('is_visible');
            $t->string('sender_name')->nullable()->after('is_warehouse');
            $t->string('sender_phone', 30)->nullable()->after('sender_name');
            $t->unsignedBigInteger('ward_id')->nullable()->after('sender_phone');
        });
        Schema::table('products', function (Blueprint $t) {
            $t->unsignedBigInteger('warehouse_id')->nullable()->index();
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->unsignedBigInteger('warehouse_id')->nullable()->index();
        });
        DB::table('store_locations')->where('name', 'like', 'Kho %')->update(['is_warehouse' => true]);
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('warehouse_id'));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('warehouse_id'));
        Schema::table('store_locations', fn (Blueprint $t) => $t->dropColumn(['is_warehouse', 'sender_name', 'sender_phone', 'ward_id']));
    }
};
