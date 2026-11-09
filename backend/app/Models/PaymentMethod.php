<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use Audited;

    protected $table = 'payment_methods';

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
