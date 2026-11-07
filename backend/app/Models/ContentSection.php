<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentSection extends Model
{
    protected $table = 'content_sections';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'is_visible' => 'boolean',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
