<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\RedirectsOldSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PostCategory extends Model
{
    use Audited, RedirectsOldSlug;

    protected $table = 'post_categories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
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

    public function publicPath(?string $slug = null): ?string
    {
        return (int) $this->depth === 1 ? '/'.($slug ?? $this->slug) : null;
    }
}
