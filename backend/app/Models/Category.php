<?php

namespace App\Models;

use App\Domain\Catalog\CategoryTree;
use App\Models\Concerns\Audited;
use App\Models\Concerns\RedirectsOldSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Category extends Model
{
    use Audited, RedirectsOldSlug;

    protected $table = 'categories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_major' => 'boolean',
            'in_solution_hub' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderByDesc('id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function contentSection(): MorphOne
    {
        return $this->morphOne(ContentSection::class, 'owner');
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

    /** The tree is read from the database, so before a save it still gives the old address. */
    public function publicPath(?string $slug = null): ?string
    {
        return '/'.(new CategoryTree)->path((int) $this->getKey());
    }

    protected function publicPathIsSection(): bool
    {
        return true;
    }
}
