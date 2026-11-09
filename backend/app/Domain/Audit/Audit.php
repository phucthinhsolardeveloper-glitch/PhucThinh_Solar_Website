<?php

namespace App\Domain\Audit;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Appends one row to admin_audit_events. The actor is the signed-in admin unless a system label is given. */
final class Audit
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function record(string $action, string $entityType, ?int $entityId, ?array $before, ?array $after, ?string $systemActor = null): void
    {
        $user = auth('admin')->user();
        $actor = $user instanceof User ? $user : null;
        $label = $actor ? ($actor->name ?: (string) $actor->username) : ($systemActor ?? (app()->runningInConsole() ? 'system:console' : 'system'));
        DB::table('admin_audit_events')->insert([
            'actor_id' => $actor?->id,
            'actor_label' => mb_substr($label, 0, 120),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
            'after' => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Only the keys whose value changed, as [before, after].
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function diff(array $before, array $after): array
    {
        $changed = array_filter(array_keys($before + $after), fn ($key) => ($before[$key] ?? null) != ($after[$key] ?? null));

        return [array_intersect_key($before, array_flip($changed)), array_intersect_key($after, array_flip($changed))];
    }
}
