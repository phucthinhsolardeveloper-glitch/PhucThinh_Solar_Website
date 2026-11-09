<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property list<int>|null $category_ids
 * @property list<int>|null $brand_ids
 * @property list<int>|null $product_ids
 */
class ProductCampaign extends Model
{
    use Audited;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'applies_to_all' => 'boolean', 'is_visible' => 'boolean',
            'category_ids' => 'array', 'brand_ids' => 'array', 'product_ids' => 'array'];
    }

    /** @return list<string> */
    public static function auditedAttributes(): array
    {
        return ['name', 'frame_image', 'priority', 'starts_at', 'ends_at', 'applies_to_all', 'category_ids', 'brand_ids', 'product_ids', 'is_visible'];
    }
}
