<?php

namespace App\Models;

use App\Casts\ContentBlocks;
use App\Domain\Content\SectionHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentSection extends Model
{
    protected $table = 'content_sections';

    protected $guarded = ['id'];

    /** Why the next save happens, written into the kept history ("update" unless set). */
    public string $historyReason = 'update';

    protected static function booted(): void
    {
        static::updating(function (self $section) {
            if ($section->isDirty(['blocks', 'mode', 'inherit_source', 'is_visible'])) {
                SectionHistory::remember($section, $section->historyReason);
            }
        });
        static::deleting(fn (self $section) => SectionHistory::remember($section, 'delete'));
    }

    protected function casts(): array
    {
        return [
            'blocks' => ContentBlocks::class,
            'is_visible' => 'boolean',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
