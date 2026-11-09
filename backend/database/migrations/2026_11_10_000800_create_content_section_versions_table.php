<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Earlier states of description blocks, kept on every change and deletion so any of them can be put back.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_section_versions', function (Blueprint $t) {
            $t->id();
            $t->string('owner_type');
            $t->unsignedBigInteger('owner_id');
            $t->string('mode', 20);
            $t->string('inherit_source', 20)->nullable();
            $t->longText('blocks');
            $t->boolean('is_visible');
            $t->string('reason', 20); // update | delete | restore | import
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['owner_type', 'owner_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_section_versions');
    }
};
