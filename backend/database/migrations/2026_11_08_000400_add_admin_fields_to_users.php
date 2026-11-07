<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Admin accounts: sign-in name, role and per-area permissions.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('username', 100)->nullable()->unique()->after('name');
            $t->string('phone', 30)->nullable()->after('email');
            $t->string('role', 20)->default('editor')->after('password'); // owner | editor
            $t->json('permissions')->nullable()->after('role');
            $t->boolean('is_active')->default(true)->after('permissions');
            $t->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['username', 'phone', 'role', 'permissions', 'is_active']);
        });
    }
};
