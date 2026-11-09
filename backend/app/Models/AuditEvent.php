<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only view of admin_audit_events; rows are only ever appended by App\Domain\Audit\Audit.
 *
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property string $entity_type
 * @property int|null $entity_id
 */
class AuditEvent extends Model
{
    protected $table = 'admin_audit_events';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
    }
}
