<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Project case-study figures: what was built and what it delivers. All optional; empty ones are not shown.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $t) {
            $t->string('system_type', 20)->nullable()->after('finished_on');
            $t->decimal('capacity_kwp', 10, 2)->nullable()->after('system_type');
            $t->decimal('storage_kwh', 10, 2)->nullable()->after('capacity_kwp');
            $t->unsignedInteger('output_kwh_month')->nullable()->after('storage_kwh');
            $t->unsignedBigInteger('saving_vnd_month')->nullable()->after('output_kwh_month');
            $t->unsignedSmallInteger('build_days')->nullable()->after('saving_vnd_month');
        });
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $t) => $t->dropColumn(['system_type', 'capacity_kwp', 'storage_kwh', 'output_kwh_month', 'saving_vnd_month', 'build_days']));
    }
};
