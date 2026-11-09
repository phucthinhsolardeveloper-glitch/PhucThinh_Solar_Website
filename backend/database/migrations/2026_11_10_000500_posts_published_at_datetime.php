<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// "Published at" was a Unix timestamp copied from the old site; it becomes a real date-time in the site's timezone.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', fn (Blueprint $t) => $t->dateTime('published_on')->nullable()->after('published_at'));
        foreach (DB::table('posts')->whereNotNull('published_at')->get(['id', 'published_at']) as $row) {
            DB::table('posts')->where('id', $row->id)->update(['published_on' => self::datetime($row->published_at)]);
        }
        Schema::table('posts', fn (Blueprint $t) => $t->dropColumn('published_at'));
        Schema::table('posts', fn (Blueprint $t) => $t->renameColumn('published_on', 'published_at'));
        Schema::table('posts', fn (Blueprint $t) => $t->index(['kind', 'is_visible', 'published_at']));

        // Versions keep the same value shape as the column.
        foreach (DB::table('post_revisions')->get(['id', 'payload']) as $revision) {
            $payload = json_decode((string) $revision->payload, true);
            if (is_array($payload) && is_numeric($payload['published_at'] ?? null)) {
                $payload['published_at'] = self::datetime($payload['published_at']);
                DB::table('post_revisions')->where('id', $revision->id)->update(['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(): void {}

    private static function datetime(mixed $timestamp): ?string
    {
        return (int) $timestamp > 0 ? Carbon::createFromTimestamp((int) $timestamp, config('app.timezone'))->format('Y-m-d H:i:s') : null;
    }
};
