<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Inventory row written by App\Domain\Media\MediaInventory; the Admin only reads it.
 *
 * @property string $path
 * @property string $folder
 * @property bool $on_disk
 * @property int|null $size
 * @property string|null $mime
 * @property int|null $width
 * @property int|null $height
 * @property string|null $sha256
 * @property int $usage_count
 * @property list<string>|null $usages
 * @property Carbon|null $modified_at
 * @property Carbon|null $missing_since
 */
class MediaFile extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'on_disk' => 'boolean', 'usages' => 'array', 'modified_at' => 'datetime', 'first_seen_at' => 'datetime',
            'missing_since' => 'datetime', 'scanned_at' => 'datetime',
        ];
    }

    public function url(): string
    {
        return '/upload/'.implode('/', array_map('rawurlencode', explode('/', $this->path)));
    }

    /** Small preview: a cached thumbnail where the site makes them, the file itself only when it is small. */
    public function previewUrl(): ?string
    {
        if (! $this->on_disk || ! $this->isImage()) {
            return null;
        }
        if (in_array($this->folder, ['news', 'photo', 'product', 'seopage'], true) && preg_match('/\.(jpe?g|png|gif|webp)$/i', $this->path)) {
            return '/thumbs/96x96x1'.$this->url();
        }

        return (int) $this->size <= 512 * 1024 ? $this->url() : null;
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
