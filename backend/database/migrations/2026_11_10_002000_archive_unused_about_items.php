<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Hide legacy About entries that have been replaced by the current page content. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('page_items')->whereIn('section', ['about.history', 'about.reasons'])
            ->whereIn('title', ['Lịch sử hình thành', 'Giao hàng toàn quốc', 'Thanh toán linh hoạt'])
            ->update(['is_visible' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Archived content stays hidden when rolling back the application image.
    }
};
