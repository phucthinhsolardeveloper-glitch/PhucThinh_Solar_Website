<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The ad campaign (utm_source, utm_medium, utm_campaign) of an order placed by a buyer who accepted cookies. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->string('campaign', 255)->nullable()->after('notes'));
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('campaign'));
    }
};
