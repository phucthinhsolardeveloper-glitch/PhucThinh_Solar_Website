<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->dateTime('follow_up_at')->nullable()->after('assigned_to');
            $table->index(['status', 'follow_up_at']);
        });
        Schema::create('lead_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('changes');
            $table->timestamp('created_at');
            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_events');
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['status', 'follow_up_at']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn('follow_up_at');
        });
    }
};
