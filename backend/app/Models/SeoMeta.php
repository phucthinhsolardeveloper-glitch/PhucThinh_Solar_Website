<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    use Audited;

    protected $table = 'seo_metas';

    protected $guarded = ['id'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /** Attributes whose changes are written to the audit log; deletions are always logged. */
    public static function auditedAttributes(): array
    {
        return ['title', 'description', 'keywords', 'image'];
    }
}
