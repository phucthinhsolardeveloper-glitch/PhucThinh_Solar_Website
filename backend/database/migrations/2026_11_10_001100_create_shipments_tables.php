<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Waybills with Nhất Tín and the status history they report. Separate from payment: a delivery update never marks money received.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('provider', 20)->default('nhattin');
            $t->string('environment', 12); // mock | sandbox | production
            $t->string('ref_code', 40)->unique();
            $t->string('bill_code', 40)->nullable();
            $t->string('status', 30); // create_pending | draft | waiting_pickup | in_transit | out_for_delivery | delivered | failed | returning | returned | cancelled | unknown
            $t->string('status_raw', 20)->nullable();
            $t->string('service_id', 10)->nullable();
            $t->string('payer_id', 10)->nullable();
            $t->decimal('weight_kg', 8, 2);
            $t->unsignedInteger('length_cm')->default(0);
            $t->unsignedInteger('width_cm')->default(0);
            $t->unsignedInteger('height_cm')->default(0);
            $t->decimal('cod_amount', 15, 0)->default(0);
            $t->decimal('cargo_value', 15, 0)->default(0);
            $t->decimal('fee', 15, 0)->nullable();
            $t->string('note', 255)->nullable();
            $t->json('request')->nullable();
            $t->json('response')->nullable();
            $t->string('error', 255)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['provider', 'environment', 'bill_code']);
            $t->index(['order_id', 'status']);
        });
        Schema::create('shipment_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $t->string('source', 20); // webhook | tracking | system
            $t->string('status_raw', 20)->nullable();
            $t->string('status_name')->nullable();
            $t->timestamp('happened_at')->nullable();
            $t->char('fingerprint', 64)->unique();
            $t->json('payload')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('shipments');
    }
};
