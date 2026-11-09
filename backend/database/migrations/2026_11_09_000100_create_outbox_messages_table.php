<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mail waiting to be sent, written in the same transaction as the order, payment or lead it reports.
// dedupe_key makes every notification at-most-once even when callbacks or retries repeat.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $t) {
            $t->id();
            $t->string('dedupe_key', 120)->unique();
            $t->string('kind', 40);
            $t->string('recipient');
            $t->longText('payload');
            $t->string('status', 20)->default('pending'); // pending | sending | retrying | sent | failed
            $t->unsignedSmallInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
