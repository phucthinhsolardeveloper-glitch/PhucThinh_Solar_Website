<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Official codes of the post-2025 map: province code (Ministry of Home Affairs) and the tax system codes.
// Codes are strings: leading zeros matter ("01").
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', fn (Blueprint $t) => $t->string('tax_code', 10)->nullable()->after('code'));
        Schema::table('wards', fn (Blueprint $t) => $t->string('tax_code', 12)->nullable()->after('code')->index());
    }

    public function down(): void
    {
        Schema::table('provinces', fn (Blueprint $t) => $t->dropColumn('tax_code'));
        Schema::table('wards', fn (Blueprint $t) => $t->dropColumn('tax_code'));
    }
};
