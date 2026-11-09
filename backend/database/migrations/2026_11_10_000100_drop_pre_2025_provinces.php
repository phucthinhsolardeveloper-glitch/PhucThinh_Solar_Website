<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The 63 provinces from before the 2025 merger have no wards and nothing points at them; only the 34 current ones stay.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('provinces')
            ->whereNotExists(fn ($q) => $q->from('wards')->whereColumn('wards.province_id', 'provinces.id'))
            ->whereNotExists(fn ($q) => $q->from('orders')->whereColumn('orders.province_id', 'provinces.id'))
            ->whereNotExists(fn ($q) => $q->from('leads')->whereColumn('leads.province_id', 'provinces.id'))
            ->delete();
    }

    public function down(): void {}
};
