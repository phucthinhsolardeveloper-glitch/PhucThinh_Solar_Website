<?php

namespace App\Domain\Leads;

use App\Models\Lead;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * "Tự chia liên hệ mới" (Thông tin website → Bán hàng, off by default): a new enquiry goes to the active staff
 * member with the leads permission who has the fewest open enquiries (owners only when nobody else can take it),
 * with a call-back time two working hours later. Written as an enquiry event so the history shows who decided.
 */
final class LeadRouting
{
    public const SETTING = 'lead_auto_assign';

    public function __construct(private readonly SiteSettings $settings) {}

    public function enabled(): bool
    {
        return (string) ($this->settings->options()[self::SETTING] ?? '0') === '1';
    }

    public function assign(int $leadId): ?User
    {
        if (! $this->enabled()) {
            return null;
        }
        try {
            $lead = Lead::query()->find($leadId);
            if (! $lead || $lead->assigned_to) {
                return null;
            }
            $staff = User::query()->where('is_active', true)->get()->filter(fn (User $u) => $u->hasPermission('leads'));
            $pool = $staff->reject(fn (User $u) => $u->isOwner());
            $pool = $pool->isEmpty() ? $staff : $pool;
            if ($pool->isEmpty()) {
                return null;
            }
            $open = DB::table('leads')->whereIn('status', ['new', 'contacted'])->whereIn('assigned_to', $pool->pluck('id'))
                ->selectRaw('assigned_to, count(*) as n')->groupBy('assigned_to')->pluck('n', 'assigned_to');
            $chosen = $pool->sortBy(fn (User $u) => [(int) ($open[$u->id] ?? 0), $u->id])->first();
            $due = self::callBackAt(now());
            $lead->forceFill(['assigned_to' => $chosen->id, 'follow_up_at' => $due])->save();
            $lead->events()->create(['actor_id' => null, 'changes' => ['assigned_to' => ['from' => null, 'to' => $chosen->id, 'to_name' => $chosen->name],
                'follow_up_at' => ['from' => null, 'to' => $due->toDateTimeString()], 'auto' => true], 'created_at' => now()]);

            return $chosen;
        } catch (Throwable $e) {
            Log::warning('Lead auto-assign failed', ['lead' => $leadId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /** Two working hours later (Mon–Sat 8:00–17:00); outside them, 9:00 of the next working day. */
    public static function callBackAt(Carbon $at): Carbon
    {
        $at = $at->copy();
        $due = $at->copy()->addHours(2);
        $working = fn (Carbon $t) => ! $t->isSunday() && $t->hour >= 8 && ($t->hour < 17 || ($t->hour === 17 && $t->minute === 0));
        if ($working($at) && $working($due)) {
            return $due->second(0);
        }
        $next = $at->copy()->hour < 8 && ! $at->isSunday() ? $at->copy() : $at->copy()->addDay();
        while ($next->isSunday()) {
            $next->addDay();
        }

        return $next->setTime(9, 0);
    }
}
