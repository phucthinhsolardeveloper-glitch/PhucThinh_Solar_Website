<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Editorial content: posts by kind (article, solution, project, policy), their categories and media,
// typed page items (the small blocks Admin edits per page), banners by placement, SEO and settings.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 20);
            $t->foreignId('parent_id')->nullable()->constrained('post_categories')->restrictOnDelete();
            $t->unsignedTinyInteger('depth')->default(1);
            $t->string('name');
            $t->string('slug');
            $t->text('summary')->nullable();
            $t->longText('body')->nullable();
            $t->string('image')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->unique(['kind', 'parent_id', 'slug']);
        });

        Schema::create('posts', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 20); // article | solution | project | policy
            $t->foreignId('category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            $t->unsignedBigInteger('author_id')->nullable();
            $t->string('title');
            $t->string('nav_title')->nullable();
            $t->string('slug');
            $t->text('summary')->nullable();
            $t->longText('body')->nullable();
            $t->longText('extra_body')->nullable();
            $t->string('image')->nullable();
            $t->string('cover_image')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->boolean('is_featured')->default(false);
            $t->boolean('in_hub')->default(false);
            // Project facts.
            $t->string('client_name')->nullable();
            $t->string('address')->nullable();
            $t->date('started_on')->nullable();
            $t->date('finished_on')->nullable();
            $t->integer('sort_order')->default(0);
            $t->unsignedInteger('views')->default(0);
            $t->unsignedInteger('published_at')->nullable();
            $t->timestamps();
            $t->unique(['kind', 'slug']);
            $t->index(['kind', 'is_visible']);
        });

        Schema::create('post_media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 10); // image | video
            $t->string('path')->nullable();
            $t->string('video_url')->nullable();
            $t->string('caption')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('post_products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
        });

        // Typed small blocks owned by a page section, e.g. "home.process", "about.trust", "dealer.stats".
        Schema::create('page_items', function (Blueprint $t) {
            $t->id();
            $t->string('section', 60);
            $t->string('title')->nullable();
            $t->string('subtitle')->nullable();
            $t->text('summary')->nullable();
            $t->longText('body')->nullable();
            $t->text('extra')->nullable();
            $t->string('figure')->nullable();
            $t->string('image')->nullable();
            $t->string('link')->nullable();
            $t->json('flags')->nullable();
            $t->json('data')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index(['section', 'is_visible']);
        });

        // Pictures with a place on the site: hero slides, campaign banners, certificates, partner logos, singletons like the logo.
        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->string('placement', 60);
            $t->string('title')->nullable();
            $t->text('summary')->nullable();
            $t->string('image')->nullable();
            $t->string('link')->nullable();
            $t->string('video_url')->nullable();
            $t->json('options')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->boolean('is_featured')->default(false);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index(['placement', 'is_visible']);
        });

        Schema::create('store_locations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('address');
            $t->string('map_url')->nullable();
            $t->text('map_embed')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('slug')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        // SEO of an entity (owner) or of a fixed page (page key such as "home", "san-pham", "lien-he").
        Schema::create('seo_metas', function (Blueprint $t) {
            $t->id();
            $t->nullableMorphs('owner');
            $t->string('page', 60)->nullable()->unique();
            $t->string('title')->nullable();
            $t->text('description')->nullable();
            $t->text('keywords')->nullable();
            $t->string('image')->nullable();
            $t->timestamps();
            $t->unique(['owner_type', 'owner_id']);
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key', 80)->primary();
            $t->json('value')->nullable();
            $t->timestamps();
        });

        Schema::create('albums', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('summary')->nullable();
            $t->string('image')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('album_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('album_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('caption')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('videos', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('url');
            $t->boolean('is_visible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['videos', 'album_images', 'albums', 'settings', 'seo_metas', 'payment_methods', 'store_locations', 'banners', 'page_items', 'post_products', 'post_media', 'posts', 'post_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
