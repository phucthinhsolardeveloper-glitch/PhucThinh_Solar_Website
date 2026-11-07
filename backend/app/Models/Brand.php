<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Brand extends Model
{
    protected $table = 'brands';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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
