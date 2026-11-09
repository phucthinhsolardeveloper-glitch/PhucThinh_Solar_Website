<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Who changed what: order and payment transitions, prices, specs, SEO, settings, permissions, deletions.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('actor_label', 120); // user name, or "system:vnpay", "system:console"
            $t->string('action', 60);
            $t->string('entity_type', 60);
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['entity_type', 'entity_id']);
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_events');
    }
};
