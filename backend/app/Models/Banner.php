<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'banners';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }
}
