<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Selling and enquiries: provinces and wards with delivery fees, orders, payments, reviews, leads.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('wards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('province_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('code')->nullable();
            $t->decimal('shipping_fee', 15, 0)->default(0);
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index(['province_id', 'is_visible']);
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('checkout_token', 64)->nullable()->unique();
            $t->string('customer_name');
            $t->string('phone', 30);
            $t->string('email')->nullable();
            $t->string('address');
            $t->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('payment_method_id')->nullable();
            $t->string('payment_code', 20)->default('manual');
            $t->string('payment_status', 20)->default('unpaid');
            $t->string('payment_reference')->nullable();
            $t->decimal('subtotal', 15, 0)->default(0);
            $t->decimal('shipping_fee', 15, 0)->default(0);
            $t->decimal('total', 15, 0)->default(0);
            $t->text('requirements')->nullable();
            $t->text('notes')->nullable();
            $t->string('status', 20)->default('new');
            $t->timestamps();
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('image')->nullable();
            $t->string('color')->nullable();
            $t->string('size')->nullable();
            $t->decimal('regular_price', 15, 0)->nullable();
            $t->decimal('sale_price', 15, 0)->nullable();
            $t->unsignedInteger('quantity');
            $t->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('provider', 20);
            $t->string('reference');
            $t->string('provider_reference')->nullable();
            $t->decimal('amount', 15, 0);
            $t->string('status', 20);
            $t->json('payload')->nullable();
            $t->timestamps();
            $t->unique(['provider', 'reference']);
        });

        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('reviews')->cascadeOnDelete();
            $t->unsignedTinyInteger('stars')->nullable();
            $t->string('title')->nullable();
            $t->text('content');
            $t->string('name');
            $t->string('phone', 30)->nullable();
            $t->string('email')->nullable();
            $t->boolean('by_staff')->default(false);
            $t->boolean('verified_purchase')->default(false);
            $t->string('status', 20)->default('pending'); // pending | approved | hidden
            $t->timestamps();
            $t->index(['product_id', 'status']);
        });

        Schema::create('review_media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('review_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 10); // photo | video
            $t->string('path');
            $t->string('poster')->nullable();
            $t->timestamps();
        });

        // Every enquiry from the site: contact, product consultation, dealer sign-up.
        Schema::create('leads', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 20);
            $t->string('name');
            $t->string('phone', 30);
            $t->string('email')->nullable();
            $t->string('company')->nullable();
            $t->string('subject')->nullable();
            $t->text('message')->nullable();
            $t->string('address')->nullable();
            $t->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('profile_key', 32)->nullable();
            $t->string('need_code', 64)->nullable();
            $t->string('audience')->nullable();
            $t->string('quantity')->nullable();
            $t->string('source_url')->nullable();
            $t->string('status', 20)->default('new');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['kind', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['leads', 'review_media', 'reviews', 'payment_transactions', 'order_items', 'orders', 'wards', 'provinces'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
