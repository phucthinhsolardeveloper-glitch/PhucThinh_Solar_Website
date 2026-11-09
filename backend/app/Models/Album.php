<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Album extends Model
{
    use Audited;

    protected $table = 'albums';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(AlbumImage::class)->orderBy('sort_order')->orderByDesc('id');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'owner');
    }

    /** Deletions are written to the audit log. */
    public static function auditedAttributes(): array
    {
        return [];
    }
}
