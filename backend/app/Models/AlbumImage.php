<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlbumImage extends Model
{
    protected $table = 'album_images';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }
}
