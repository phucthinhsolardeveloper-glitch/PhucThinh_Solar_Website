<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class StoreLocation extends Model
{
    use Audited;

    protected $table = 'store_locations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    /** Deletions are written to the audit log. */
    public static function auditedAttributes(): array
    {
        return [];
    }
}
