<?php

namespace App\Models;

use App\Casts\UnicodeJson;
use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use Audited;

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

    /** Deletions are written to the audit log. */
    public static function auditedAttributes(): array
    {
        return [];
    }
}
