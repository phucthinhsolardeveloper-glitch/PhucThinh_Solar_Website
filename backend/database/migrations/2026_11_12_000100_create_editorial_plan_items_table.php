<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 180 planned publishing slots are independent from public posts (no empty articles on the website). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_plan_items', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_key', 36)->unique();
            $table->dateTime('scheduled_for')->index();
            $table->string('title', 255);
            $table->string('suggested_slug', 255);
            $table->string('category_slug', 80);
            $table->string('stream', 100);
            $table->string('pillar', 100)->nullable();
            $table->string('priority', 8)->default('P2');
            $table->string('stage', 24)->default('idea')->index();
            $table->text('angle')->nullable();
            $table->string('primary_source_name', 120)->nullable();
            $table->text('primary_source_url')->nullable();
            $table->text('secondary_source_url')->nullable();
            $table->text('image_brief')->nullable();
            $table->text('image_search_url')->nullable();
            $table->string('cover_file', 180)->nullable();
            $table->string('image_status', 32)->default('mockup');
            $table->string('logo_variant', 20)->default('wide');
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->text('editor_notes')->nullable();
            $table->timestamps();
            $table->index(['scheduled_for', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_plan_items');
    }
};
