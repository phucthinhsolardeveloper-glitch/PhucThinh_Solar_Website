<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Settings the site never reads are dropped (branch maps live on store locations); the working-hours key gets a plain name.
return new class extends Migration
{
    private const UNUSED = ['oaidzalo', 'website', 'linkedin', 'twitter', 'coords', 'coords_iframe', 'toado', 'toado_iframe', 'header_promise', 'home_hero_mode', 'lang_default', 'watermark_active'];

    public function up(): void
    {
        DB::table('settings')->whereIn('key', self::UNUSED)->delete();
        if (! DB::table('settings')->where('key', 'working_hours')->exists()) {
            DB::table('settings')->where('key', 'thoigianlamviec')->update(['key' => 'working_hours']);
        }
        DB::table('settings')->where('key', 'thoigianlamviec')->delete();
    }

    public function down(): void {}
};
