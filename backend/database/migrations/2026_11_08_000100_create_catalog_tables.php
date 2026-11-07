<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catalog: one category tree (up to 4 levels), brands, products with typed specification columns.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $t->unsignedTinyInteger('depth')->default(1);
            $t->string('name');
            $t->string('slug');
            $t->text('summary')->nullable();
            $t->longText('body')->nullable();
            $t->string('image')->nullable();
            $t->string('pdp_profile', 32)->nullable();
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_major')->default(false);
            $t->boolean('in_solution_hub')->default(false);
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->unique(['parent_id', 'slug']);
            $t->index('slug');
        });

        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('summary')->nullable();
            $t->longText('body')->nullable();
            $t->string('image')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('code')->nullable();
            $t->text('summary')->nullable();
            $t->longText('body')->nullable();
            $t->longText('highlights')->nullable();
            $t->longText('applications')->nullable();
            $t->longText('installation')->nullable();
            $t->longText('specs_html')->nullable();
            $t->longText('purchase_policy')->nullable();
            $t->string('price_range')->nullable();
            $t->decimal('regular_price', 15, 0)->nullable();
            $t->decimal('sale_price', 15, 0)->nullable();
            $t->boolean('quote_only')->default(false);
            $t->string('image')->nullable();
            $t->string('image_secondary')->nullable();
            $t->string('video_url')->nullable();
            $t->string('pdp_profile', 32)->nullable();
            // Specifications; free text as entered, "additional_specs" holds extra label/value rows.
            $t->decimal('power_watts', 10, 2)->nullable();
            foreach (['battery_capacity', 'panel_power', 'light_source', 'lamp_dimensions', 'color_temperature', 'light_color', 'material', 'charging_time',
                'lighting_time', 'waterproof_rating', 'led_chip', 'lighting_area', 'installation_height', 'panel_dimensions', 'carton_quantity', 'carton_dimensions', 'weight', 'warranty'] as $column) {
                $t->string($column)->nullable();
            }
            $t->text('package_contents')->nullable();
            $t->json('additional_specs')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->boolean('is_bestseller')->default(false);
            $t->integer('sort_order')->default(0);
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();
            $t->index(['is_visible', 'category_id']);
        });

        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('caption')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('product_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 20);
            $t->string('title');
            $t->string('path');
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('product_options', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 10); // color | size
            $t->string('name');
            $t->string('value')->nullable();
            $t->unsignedBigInteger('legacy_id')->nullable(); // id the PHP site used in carts
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        // Description blocks of a product, brand or category, with inheritance modes.
        Schema::create('content_sections', function (Blueprint $t) {
            $t->id();
            $t->morphs('owner');
            $t->string('mode', 20)->default('own');
            $t->string('inherit_source', 20)->nullable();
            $t->json('blocks');
            $t->boolean('is_visible')->default(true);
            $t->timestamps();
            $t->unique(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        foreach (['content_sections', 'product_options', 'product_documents', 'product_images', 'products', 'brands', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
