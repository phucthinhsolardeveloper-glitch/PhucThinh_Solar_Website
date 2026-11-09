<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Picture approved for Google Shopping. Separate from the main picture on purpose: a main picture may carry
// text or a logo, and replacing it must not silently change what is sent to Google.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->string('feed_image')->nullable()->after('image_secondary'));
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('feed_image'));
    }
};
