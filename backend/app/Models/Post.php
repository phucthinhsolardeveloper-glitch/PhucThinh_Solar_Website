<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\RedirectsOldSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/** @property string $kind */
class Post extends Model
{
    use Audited, RedirectsOldSlug;

    protected $table = 'posts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'in_hub' => 'boolean',
            'started_on' => 'date',
            'finished_on' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('sort_order')->orderByDesc('id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'post_products')->withPivot(['is_visible', 'sort_order']);
    }

    /** @return MorphOne<SeoMeta, $this> */
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'owner');
    }

    /** @return HasMany<PostRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class);
    }

    /** Deletions are written to the audit log. */
    public static function auditedAttributes(): array
    {
        return [];
    }

    public function publicPath(?string $slug = null): ?string
    {
        $slug ??= $this->slug;

        return match ($this->kind) {
            'article', 'project' => '/'.$slug.'/',
            'solution' => '/giai-phap/'.$slug.'/',
            'policy' => '/ho-tro/'.$slug.'/',
            default => null,
        };
    }
}
