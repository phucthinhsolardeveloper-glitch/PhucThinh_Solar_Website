<?php

namespace App\Domain\Commerce;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeadTriage
{
    /**
     * "Nhận xử lý": the actor takes an enquiry nobody has. Decided on the locked row, so of two people pressing
     * at once only the first gets it; the other is told who has it. Only the assignee and, when empty, the
     * call-back time change: status and notes typed by others are never overwritten from a stale list.
     *
     * @return User|null null when taken; otherwise the person who already has it
     */
    public function take(Lead $lead, User $actor, ?Carbon $callBack = null): ?User
    {
        abort_unless($actor->hasPermission('leads'), 403);

        return DB::transaction(function () use ($lead, $actor, $callBack): ?User {
            $locked = Lead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();
            if ($locked->assigned_to !== null) {
                return User::query()->find($locked->assigned_to) ?? $actor;
            }
            if (in_array($locked->status, ['done', 'spam'], true)) {
                throw ValidationException::withMessages(['assigned_to' => 'Liên hệ đã đóng, không cần nhận xử lý.']);
            }
            $changes = ['assigned_to' => ['from' => null, 'to' => $actor->id, 'from_name' => null, 'to_name' => $actor->name]];
            $fill = ['assigned_to' => $actor->id];
            if ($locked->follow_up_at === null && $callBack !== null) {
                $fill['follow_up_at'] = $callBack;
                $changes['follow_up_at'] = ['from' => null, 'to' => $callBack->toDateTimeString()];
            }
            $locked->forceFill($fill)->save();
            $locked->events()->create(['actor_id' => $actor->id, 'changes' => $changes, 'created_at' => now()]);
            $lead->setRawAttributes($locked->getAttributes(), true);

            return null;
        });
    }

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
