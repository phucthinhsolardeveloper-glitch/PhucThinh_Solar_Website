<?php

namespace App\Domain\Commerce;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeadTriage
{
    public function update(Lead $lead, array $input, User $actor): void
    {
        abort_unless($actor->hasPermission('leads'), 403);
        $data = Validator::make($input, [
            'status' => ['required', 'in:new,contacted,done,spam'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'follow_up_at' => ['nullable', 'date'],
        ])->validate();
        if (in_array($data['status'], ['done', 'spam'], true)) {
            $data['follow_up_at'] = null;
        }
        if (isset($data['assigned_to'])) {
            $assignee = User::findOrFail($data['assigned_to']);
            if (! $assignee->is_active || ! $assignee->hasPermission('leads')) {
                throw ValidationException::withMessages(['assigned_to' => 'Chỉ phân công cho tài khoản đang hoạt động và có quyền Leads.']);
            }
        }

        DB::transaction(function () use ($lead, $data, $actor): void {
            $locked = Lead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();
            $before = [
                'status' => $locked->getRawOriginal('status'), 'notes' => $locked->getRawOriginal('notes'),
                'assigned_to' => $locked->getRawOriginal('assigned_to'), 'follow_up_at' => $locked->getRawOriginal('follow_up_at'),
            ];
            $locked->update([
                'status' => $data['status'], 'notes' => $data['notes'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null, 'follow_up_at' => $data['follow_up_at'] ?? null,
            ]);
            $updated = $locked->fresh();
            $after = [
                'status' => $updated->getRawOriginal('status'), 'notes' => $updated->getRawOriginal('notes'),
                'assigned_to' => $updated->getRawOriginal('assigned_to'), 'follow_up_at' => $updated->getRawOriginal('follow_up_at'),
            ];
            $changes = [];
            foreach (['status', 'assigned_to', 'follow_up_at'] as $field) {
                if ((string) ($before[$field] ?? '') !== (string) ($after[$field] ?? '')) {
                    $changes[$field] = ['from' => $before[$field], 'to' => $after[$field]];
                    if ($field === 'assigned_to') {
                        $changes[$field]['from_name'] = $before[$field] ? User::find($before[$field])?->name : null;
                        $changes[$field]['to_name'] = $after[$field] ? User::find($after[$field])?->name : null;
                    }
                }
            }
            if (($before['notes'] ?? null) !== ($after['notes'] ?? null)) {
                $changes['notes_changed'] = true;
            }
            if ($changes !== []) {
                $locked->events()->create(['actor_id' => $actor->id, 'changes' => $changes, 'created_at' => now()]);
            }
        });
    }
}
