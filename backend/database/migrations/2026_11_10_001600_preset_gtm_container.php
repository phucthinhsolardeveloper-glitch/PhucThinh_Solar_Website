<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The owner's Google Tag Manager container, set once so the live site measures from the first deploy.
 * Kept when Admin already has a value; changed or cleared later in Admin → Thông tin website → Đo lường.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('settings')->where('key', 'gtm_id')->exists()) {
            DB::table('settings')->insert(['key' => 'gtm_id', 'value' => json_encode('GTM-57TJSRVM'), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'gtm_id')->where('value', json_encode('GTM-57TJSRVM'))->delete();
    }
};
