<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One row per uploaded file or per file a page points to: what it is, where it is used, whether it is still there.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $t) {
            $t->id();
            $t->string('path', 500)->unique(); // relative to the uploads root, e.g. product/den-pha-abc123.jpg
            $t->string('folder', 40)->index();
            $t->boolean('on_disk')->default(true)->index();
            $t->unsignedBigInteger('size')->nullable();
            $t->string('mime', 100)->nullable();
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->char('sha256', 64)->nullable()->index();
            $t->timestamp('modified_at')->nullable();
            $t->unsignedInteger('usage_count')->default(0)->index();
            $t->json('usages')->nullable(); // first places using it, e.g. ["products#12.image"]
            $t->timestamp('first_seen_at')->nullable();
            $t->timestamp('missing_since')->nullable();
            $t->timestamp('scanned_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
