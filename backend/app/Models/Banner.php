<?php

namespace App\Models;

use App\Casts\UnicodeJson;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'banners';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'options' => UnicodeJson::class,
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }
}
