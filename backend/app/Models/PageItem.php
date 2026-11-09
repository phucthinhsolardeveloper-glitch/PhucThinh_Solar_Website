<?php

namespace App\Models;

use App\Casts\UnicodeJson;
use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class PageItem extends Model
{
    use Audited;

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

    /** Deletions are written to the audit log. */
    public static function auditedAttributes(): array
    {
        return [];
    }
}
