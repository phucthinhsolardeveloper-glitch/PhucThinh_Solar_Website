<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Old address => new address. Written automatically when a public slug changes, or by hand in the Admin.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $t) {
            $t->id();
            $t->string('from_path', 500)->unique(); // "/old-slug/", always with leading slash
            $t->string('to_path', 500);
            $t->boolean('is_prefix')->default(false); // a category: everything below the old path moves too
            $t->string('source', 20)->default('auto'); // auto | manual
            $t->unsignedInteger('hits')->default(0);
            $t->timestamp('last_hit_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
