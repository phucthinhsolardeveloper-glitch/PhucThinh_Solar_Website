<?php

namespace App\Models;

use App\Casts\UnicodeJson;
use App\Domain\Catalog\SpecFormat;
use App\Models\Concerns\Audited;
use App\Models\Concerns\RedirectsOldSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use Audited, RedirectsOldSlug;

    protected $table = 'products';

    protected $guarded = ['id'];

    /** Specs typed in Admin or imported from Excel are kept in the one written form (SpecFormat). */
    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $dirty = array_intersect_key($product->getAttributes(), array_flip([...array_keys(SpecFormat::COLUMNS), 'additional_specs']));
            $brand = $product->brand_id ? (string) DB::table('brands')->where('id', $product->brand_id)->value('name') : '';
            $changes = SpecFormat::changes(array_merge($dirty, ['additional_specs' => $product->additional_specs, 'name' => $product->name, 'brand_name' => $brand]));
            foreach ($changes as $column => $value) {
                if ($product->isDirty($column) || ! $product->exists || ($column === 'name' && $product->isDirty('brand_id'))) {
                    $product->{$column} = $value;
                }
            }
        });
    }

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

    /** Attributes whose changes are written to the audit log; deletions are always logged. */
    public static function auditedAttributes(): array
    {
        return ['name', 'slug', 'code', 'category_id', 'brand_id', 'regular_price', 'sale_price', 'weight_kg', 'quote_only', 'is_visible', 'pdp_profile', 'power_watts', 'battery_capacity', 'panel_power', 'light_source', 'lamp_dimensions', 'color_temperature', 'light_color', 'material', 'charging_time', 'lighting_time', 'waterproof_rating', 'led_chip', 'lighting_area', 'installation_height', 'panel_dimensions', 'carton_quantity', 'carton_dimensions', 'weight', 'warranty', 'package_contents', 'additional_specs'];
    }

    public function publicPath(?string $slug = null): ?string
    {
        return '/san-pham/'.($slug ?? $this->slug);
    }
}
