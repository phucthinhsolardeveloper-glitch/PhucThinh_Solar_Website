<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Promotion frames laid over product card pictures for a period, on all products or chosen categories, brands, products.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_campaigns', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('frame_image'); // transparent PNG in upload/photo
            $t->integer('priority')->default(0);
            $t->dateTime('starts_at')->nullable();
            $t->dateTime('ends_at')->nullable();
            $t->boolean('applies_to_all')->default(false);
            $t->json('category_ids')->nullable();
            $t->json('brand_ids')->nullable();
            $t->json('product_ids')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_campaigns');
    }
};
