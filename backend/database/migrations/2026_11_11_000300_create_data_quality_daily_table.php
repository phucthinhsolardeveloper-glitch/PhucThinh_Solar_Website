<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One count per data-quality check per day (php artisan pts:data-quality --record, nightly), for the trend on the page. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_quality_daily', function (Blueprint $table): void {
            $table->id();
            $table->date('day');
            $table->string('check_key', 80);
            $table->unsignedInteger('count');
            $table->unique(['day', 'check_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_quality_daily');
    }
};
