<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use Audited;

    protected $table = 'reviews';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'by_staff' => 'boolean',
            'verified_purchase' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ReviewMedia::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Deletions are written to the audit log. */
    public static function auditedAttributes(): array
    {
        return [];
    }
}
