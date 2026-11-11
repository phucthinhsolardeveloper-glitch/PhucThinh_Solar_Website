<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Charge controllers get their own product type (they shared "Phụ kiện" with cables and clamps), and solar pumps
 * the pump type (they shared "Biến tần bơm"). Only categories still without a type are touched.
 */
return new class extends Migration
{
    private const PROFILES = [
        'bo-dieu-khien-sac-mppt' => 'charge_controller',
        'bo-dieu-khien-sac-pwm' => 'charge_controller',
        'may-bom-nlmt' => 'pump_hardware',
    ];

    public function up(): void
    {
        foreach (self::PROFILES as $slug => $profile) {
            DB::table('categories')->where('slug', $slug)->whereNull('pdp_profile')->update(['pdp_profile' => $profile, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::PROFILES as $slug => $profile) {
            DB::table('categories')->where('slug', $slug)->where('pdp_profile', $profile)->update(['pdp_profile' => null]);
        }
    }
};
