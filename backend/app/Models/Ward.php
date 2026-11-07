<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ward extends Model
{
    protected $table = 'wards';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'shipping_fee' => 'decimal:0',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
