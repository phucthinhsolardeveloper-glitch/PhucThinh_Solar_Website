<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\RedirectsOldSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Brand extends Model
{
    use Audited, RedirectsOldSlug;

    protected $table = 'brands';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
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

    public function publicPath(?string $slug = null): ?string
    {
        return '/thuong-hieu/'.($slug ?? $this->slug).'/';
    }

    /** Public address of a stored logo file name, or '' when there is none. */
    public static function logoUrl(mixed $image): string
    {
        $image = trim((string) $image);

        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g|webp|svg)$/i', $image) ? '/upload/product/'.$image : '';
    }
}
