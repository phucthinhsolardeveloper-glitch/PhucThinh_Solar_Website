<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_metas';

    protected $guarded = ['id'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
