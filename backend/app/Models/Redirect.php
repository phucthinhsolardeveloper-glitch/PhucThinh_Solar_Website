<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Old address => new address; see App\Domain\Routing\Redirects.
 *
 * @property Carbon|null $last_hit_at
 */
class Redirect extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_prefix' => 'boolean', 'last_hit_at' => 'datetime'];
    }
}
