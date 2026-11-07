<?php

namespace App\Models;

use App\Casts\UnicodeJson;
use Illuminate\Database\Eloquent\Model;

class PageItem extends Model
{
    protected $table = 'page_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'flags' => UnicodeJson::class,
            'data' => UnicodeJson::class,
            'is_visible' => 'boolean',
        ];
    }
}
