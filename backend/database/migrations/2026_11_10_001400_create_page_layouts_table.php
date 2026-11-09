<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Order, visibility and look of the fixed sections of Home and About, with drafts and history.
// The words and pictures inside each section stay in page_items / banners.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_layouts', function (Blueprint $t) {
            $t->id();
            $t->string('page', 20); // home | about
            $t->unsignedInteger('version');
            $t->string('state', 12); // draft | published | archived
            $t->json('sections'); // list of {type, visible, variant}
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->unique(['page', 'version']);
            $t->index(['page', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_layouts');
    }
};
