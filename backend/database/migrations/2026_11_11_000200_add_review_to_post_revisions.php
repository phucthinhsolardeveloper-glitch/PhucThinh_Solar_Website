<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Editorial review of a draft: "Chờ duyệt" (waiting) or "Trả lại" (returned with a note). Empty = still being written. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_revisions', function (Blueprint $table): void {
            $table->string('review_state', 20)->nullable()->after('state')->index();
            $table->text('review_note')->nullable()->after('review_state');
            $table->foreignId('review_requested_by')->nullable()->after('review_note')->constrained('users')->nullOnDelete();
            $table->timestamp('review_requested_at')->nullable()->after('review_requested_by');
            $table->foreignId('reviewed_by')->nullable()->after('review_requested_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('post_revisions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('review_requested_by');
            $table->dropColumn(['review_state', 'review_note', 'review_requested_at']);
        });
    }
};
