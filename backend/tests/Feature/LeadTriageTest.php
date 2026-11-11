<?php

use App\Domain\Commerce\LeadTriage;
use App\Domain\Leads\LeadRouting;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Models\Lead;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
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

it('lets only the first of two people take an enquiry, without touching what the other typed', function () {
    $a = triageUser(['leads']);
    $b = triageUser(['leads']);
    $lead = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách tranh', 'phone' => '0900000300', 'status' => 'new']);
    $staleForB = Lead::query()->findOrFail($lead->id); // B's list was loaded before A pressed the button

    expect(app(LeadTriage::class)->take($lead, $a, now()->addHours(2)))->toBeNull();
    Lead::query()->whereKey($lead->id)->update(['status' => 'contacted', 'notes' => 'A đã gọi']);
    $holder = app(LeadTriage::class)->take($staleForB, $b, now()->addHours(2));

    $saved = $lead->fresh();
    expect($holder?->id)->toBe($a->id)->and($saved->assigned_to)->toBe($a->id)
        ->and($saved->status)->toBe('contacted')->and($saved->notes)->toBe('A đã gọi')
        ->and($saved->events()->count())->toBe(1)->and($saved->events()->first()->actor_id)->toBe($a->id);
});

it('takes an enquiry from the list and hides the button once someone has it', function () {
    $a = triageUser(['leads']);
    $b = triageUser(['leads']);
    $lead = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách nút', 'phone' => '0900000301', 'status' => 'new']);

    $this->actingAs($b, 'admin');
    $page = Livewire\Livewire::test(ManageLeads::class)->assertTableActionVisible('takeLead', $lead);
    app(LeadTriage::class)->take($lead, $a, now()->addHour());
    // The button a stale list still shows runs take(), which refuses (covered above); on the next render it is gone.
    $page->assertTableActionHidden('takeLead', $lead);
    expect($lead->fresh()->assigned_to)->toBe($a->id);

    $free = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách trống', 'phone' => '0900000302', 'status' => 'new']);
    Livewire\Livewire::test(ManageLeads::class)->callTableAction('takeLead', $free)->assertNotified('Bạn đang phụ trách Khách trống');
    expect($free->fresh()->assigned_to)->toBe($b->id)->and($free->fresh()->follow_up_at)->not->toBeNull();
});

it('keeps the assignee when the status is changed from a list loaded earlier', function () {
    $a = triageUser(['leads']);
    $b = triageUser(['leads']);
    $lead = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách cũ', 'phone' => '0900000303', 'status' => 'new']);
    $this->actingAs($b, 'admin');
    $page = Livewire\Livewire::test(ManageLeads::class);
    app(LeadTriage::class)->take($lead, $a);

    $page->callTableAction('quickStatus', $lead, ['status' => 'contacted', 'notes' => 'B ghi chú']);
    expect($lead->fresh()->assigned_to)->toBe($a->id)->and($lead->fresh()->status)->toBe('contacted');
});

it('does not auto-assign an enquiry someone already took', function () {
    $a = triageUser(['leads']);
    triageUser(['leads']);
    DB::table('settings')->updateOrInsert(['key' => LeadRouting::SETTING], ['value' => json_encode('1')]);
    app(SiteSettings::class)->clear();
    $lead = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách đã nhận', 'phone' => '0900000304', 'status' => 'new']);
    app(LeadTriage::class)->take($lead, $a);

    expect(app(LeadRouting::class)->assign($lead->id))->toBeNull()
        ->and($lead->fresh()->assigned_to)->toBe($a->id)->and($lead->events()->count())->toBe(1);
});
