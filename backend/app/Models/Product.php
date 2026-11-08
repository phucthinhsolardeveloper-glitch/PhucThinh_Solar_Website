<?php

namespace App\Models;

use App\Casts\UnicodeJson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Product extends Model
{
    protected $table = 'products';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'additional_specs' => UnicodeJson::class,
            'highlights' => UnicodeJson::class,
            'applications' => UnicodeJson::class,
            'installation' => UnicodeJson::class,
            'quote_only' => 'boolean',
            'is_visible' => 'boolean',
            'is_bestseller' => 'boolean',
            'regular_price' => 'decimal:0',
            'sale_price' => 'decimal:0',
            'power_watts' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function visibleArticles(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_products')
            ->where('posts.kind', 'article')->where('posts.is_visible', true)->wherePivot('is_visible', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderByDesc('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class)->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function contentSection(): MorphOne
    {
        return $this->morphOne(ContentSection::class, 'owner');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'owner');
    }
}
