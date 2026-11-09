<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One row per page seen on the public site. No IP is kept: the visitor is a hash that changes every day.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $t) {
            $t->id();
            $t->date('day');
            $t->string('path', 255);
            $t->char('visitor', 16);
            $t->string('source', 20); // internal (later page of a visit) | direct | google | facebook | zalo | other search | other site | campaign
            $t->string('referrer_host', 100)->nullable();
            $t->string('device', 8); // mobile | tablet | desktop
            $t->timestamp('created_at')->useCurrent();
            $t->index(['day', 'path']);
            $t->index(['day', 'visitor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
