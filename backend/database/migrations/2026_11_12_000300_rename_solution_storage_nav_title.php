<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $target = DB::table('posts')
            ->where('kind', 'solution')
            ->where('slug', 'he-thong-luu-tru-bess')
            ->where('nav_title', 'Lưu trữ điện');

        // The kind/slug key is unique; count guards against an unexpected duplicate
        // so an ambiguous production dataset is left untouched for manual review.
        if ($target->count() === 1) {
            $target->update(['nav_title' => 'Pin lưu trữ']);
        }
    }

    public function down(): void
    {
        DB::table('posts')
            ->where('kind', 'solution')
            ->where('slug', 'he-thong-luu-tru-bess')
            ->where('nav_title', 'Pin lưu trữ')
            ->update(['nav_title' => 'Lưu trữ điện']);
    }
};
