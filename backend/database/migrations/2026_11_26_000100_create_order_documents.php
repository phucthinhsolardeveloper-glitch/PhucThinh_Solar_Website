<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The order sheet as it stood when the order was placed; the PDF is rendered from this text on demand. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $t->longText('snapshot');
            $t->char('sha256', 64);
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_documents');
    }
};
