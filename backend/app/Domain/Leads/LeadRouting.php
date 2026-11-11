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
 * with a call-back time two working hours later (callBackAt). Written as an enquiry event so the history shows who decided.
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
            // Conditional write: someone who pressed "Nhận xử lý" in the meantime keeps the enquiry.
            $won = Lead::query()->whereKey($lead->id)->whereNull('assigned_to')->whereNotIn('status', ['done', 'spam'])
                ->update(['assigned_to' => $chosen->id, 'follow_up_at' => $lead->follow_up_at ?? $due, 'updated_at' => now()]);
            if ($won !== 1) {
                return null;
            }
            $lead->events()->create(['actor_id' => null, 'changes' => ['assigned_to' => ['from' => null, 'to' => $chosen->id, 'to_name' => $chosen->name],
                'follow_up_at' => ['from' => null, 'to' => ($lead->follow_up_at ?? $due)->toDateTimeString()], 'auto' => true], 'created_at' => now()]);

            return $chosen;
        } catch (Throwable $e) {
            Log::warning('Lead auto-assign failed', ['lead' => $leadId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public const DAY_START = 8;

    public const DAY_END = 17;

    /**
     * Two working hours later: 120 minutes counted only inside working time (Mon–Sat 08:00–17:00, public holidays
     * not known here). 16:30 → 09:30 next working day; 17:00 or later, Sunday, or before 08:00 → 10:00 of the
     * next working morning.
     */
    public static function callBackAt(Carbon $at, int $minutes = 120): Carbon
    {
        $t = $at->copy()->second(0);
        $left = $minutes;
        while (true) {
            if ($t->isSunday() || $t->hour >= self::DAY_END) {
                $t = $t->addDay()->setTime(self::DAY_START, 0);

                continue;
            }
            if ($t->hour < self::DAY_START) {
                $t = $t->setTime(self::DAY_START, 0);
            }
            $close = $t->copy()->setTime(self::DAY_END, 0);
            $available = (int) $t->diffInMinutes($close);
            if ($left <= $available) {
                return $t->addMinutes($left);
            }
            $left -= $available;
            $t = $close;
        }
    }
}
