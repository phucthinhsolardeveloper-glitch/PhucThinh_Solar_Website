<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'happened_at' => 'datetime', 'created_at' => 'datetime'];
    }
}
