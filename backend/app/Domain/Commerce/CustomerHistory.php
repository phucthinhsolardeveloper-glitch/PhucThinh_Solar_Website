<?php

namespace App\Domain\Commerce;

use Illuminate\Support\Facades\DB;

/**
 * "Khách cũ": what we already know about a phone number (earlier orders and enquiries), so whoever calls back
 * knows who is on the line. Phones are compared on their digits, with +84 / 84 read as 0.
 */
final class CustomerHistory
{
    /** @var array<string, array{orders: int, spent: float, leads: int}> per-request memo */
    private array $memo = [];

    public static function normalise(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        return str_starts_with($digits, '84') && strlen($digits) >= 11 ? '0'.substr($digits, 2) : $digits;
    }

    /** @return array{orders: int, spent: float, leads: int} everything on this phone except the given order / enquiry */
    public function of(?string $phone, ?int $exceptOrder = null, ?int $exceptLead = null): array
    {
        $digits = self::normalise($phone);
        if (strlen($digits) < 9) {
            return ['orders' => 0, 'spent' => 0.0, 'leads' => 0];
        }
        $key = $digits.'|'.$exceptOrder.'|'.$exceptLead;
        if (! isset($this->memo[$key])) {
            $tail = '%'.substr($digits, -9);
            $bare = "replace(replace(replace(replace(phone, ' ', ''), '.', ''), '-', ''), '+', '') like ?";
            $orders = DB::table('orders')->whereRaw($bare, [$tail])->where('status', '!=', 'cancelled')
                ->when($exceptOrder, fn ($q) => $q->where('id', '<>', $exceptOrder))->selectRaw('count(*) as n, coalesce(sum(total), 0) as spent')->first();
            $leads = DB::table('leads')->whereRaw($bare, [$tail])->where('status', '!=', 'spam')
                ->when($exceptLead, fn ($q) => $q->where('id', '<>', $exceptLead))->count();
            $this->memo[$key] = ['orders' => (int) ($orders->n ?? 0), 'spent' => (float) ($orders->spent ?? 0), 'leads' => $leads];
        }

        return $this->memo[$key];
    }

    /** "Khách cũ: 2 đơn (5.300.000đ) · 1 liên hệ trước", or '' for a first contact. */
    public function label(?string $phone, ?int $exceptOrder = null, ?int $exceptLead = null): string
    {
        $h = $this->of($phone, $exceptOrder, $exceptLead);
        $parts = [];
        if ($h['orders'] > 0) {
            $parts[] = $h['orders'].' đơn ('.number_format($h['spent'], 0, ',', '.').'đ)';
        }
        if ($h['leads'] > 0) {
            $parts[] = $h['leads'].' liên hệ trước';
        }

        return $parts === [] ? '' : 'Khách cũ: '.implode(' · ', $parts);
    }
}
