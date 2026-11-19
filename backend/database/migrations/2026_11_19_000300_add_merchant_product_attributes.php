<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('merchant_availability', 20)->nullable()->after('quote_only');
            $table->string('merchant_gtin', 32)->nullable()->after('merchant_availability');
            $table->string('merchant_mpn', 70)->nullable()->after('merchant_gtin');
            $table->boolean('merchant_identifiers_absent')->default(false)->after('merchant_mpn');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['merchant_availability', 'merchant_gtin', 'merchant_mpn', 'merchant_identifiers_absent']);
        });
    }
};
