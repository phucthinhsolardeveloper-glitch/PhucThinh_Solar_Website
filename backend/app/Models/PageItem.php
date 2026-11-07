<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageItem extends Model
{
    protected $table = 'page_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'flags' => 'array',
            'data' => 'array',
            'is_visible' => 'boolean',
        ];
    }
}
