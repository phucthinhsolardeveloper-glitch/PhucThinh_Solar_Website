<?php

use App\Domain\Commerce\LeadTriage;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function triageUser(array $permissions): User
{
    return User::query()->create(['name' => 'Nhân viên thử', 'username' => 'triage-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => $permissions, 'is_active' => true]);
}

it('assigns and schedules a lead from Filament, logging changes without copying customer notes', function () {
    $editor = triageUser(['leads']);
    $assignee = triageUser(['leads']);
    $lead = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách thử', 'phone' => '0900000000', 'status' => 'new']);
    $due = now()->addDay()->startOfMinute()->format('Y-m-d H:i:s');
    $this->actingAs($editor, 'admin');

    Livewire\Livewire::test(ManageLeads::class)->callTableAction('edit', $lead, [
        'status' => 'contacted', 'assigned_to' => $assignee->id, 'follow_up_at' => $due,
        'notes' => 'Đã gọi; hẹn gửi báo giá', 'name' => 'Không được sửa khách',
    ])->assertHasNoActionErrors();

    $saved = $lead->fresh();
    $event = $saved->events()->firstOrFail();
    expect($saved->status)->toBe('contacted')->and($saved->assigned_to)->toBe($assignee->id)
        ->and($saved->follow_up_at->format('Y-m-d H:i:s'))->toBe($due)
        ->and($saved->name)->toBe('Khách thử')->and($event->actor_id)->toBe($editor->id)
        ->and($event->changes['status']['to'])->toBe('contacted')
        ->and($event->changes['notes_changed'])->toBeTrue()
        ->and(json_encode($event->changes))->not->toContain('báo giá');
    $history = Livewire\Livewire::test(ManageLeads::class)->assertTableActionExists('history', null, $saved)
        ->mountTableAction('history', $saved);
    expect($history->instance()->mountedActions[0]['name'])->toBe('history');
    expect(view('filament.lead-history', ['events' => $saved->events()->with('actor')->get()])->render())
        ->toContain('Trạng thái')->toContain($assignee->name);

    app(LeadTriage::class)->update($saved, ['status' => 'done', 'assigned_to' => $assignee->id,
        'follow_up_at' => $due, 'notes' => 'Đã gọi; hẹn gửi báo giá'], $editor);
    expect($lead->fresh()->follow_up_at)->toBeNull()->and($lead->fresh()->events()->count())->toBe(2);
});

it('rejects assignment to an account without lead permission', function () {
    $editor = triageUser(['leads']);
    $other = triageUser(['content']);
    $lead = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách thử', 'phone' => '0900000000', 'status' => 'new']);

    expect(fn () => app(LeadTriage::class)->update($lead, ['status' => 'contacted', 'assigned_to' => $other->id], $editor))
        ->toThrow(ValidationException::class);
    expect($lead->fresh()->status)->toBe('new')->and($lead->events()->count())->toBe(0);
});
