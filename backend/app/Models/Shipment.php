<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $status
 * @property string|null $bill_code
 * @property string $environment
 */
class Shipment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['request' => 'array', 'response' => 'array'];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<ShipmentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->orderByDesc('happened_at')->orderByDesc('id');
    }
}
