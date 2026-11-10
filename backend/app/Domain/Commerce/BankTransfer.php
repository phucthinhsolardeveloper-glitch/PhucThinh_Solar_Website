<?php

namespace App\Domain\Commerce;

use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** The receiving account set by the owner, and one fixed transfer instruction per order. */
final class BankTransfer
{
    public function __construct(private readonly SiteSettings $settings) {}

    /** @return array{bin: string, bank: string, short: string, color: string, account_no: string, account_name: string}|null */
    public function account(): ?array
    {
        $bin = trim((string) $this->settings->get('bank_bin', ''));
        $no = (string) preg_replace('/\s+/', '', (string) $this->settings->get('bank_account_no', ''));
        $name = trim((string) $this->settings->get('bank_account_name', ''));
        if (! isset(VietQr::BANKS[$bin]) || ! preg_match('/^[0-9A-Za-z]{4,19}$/', $no) || $name === '') {
            return null;
        }

        return ['bin' => $bin, 'bank' => VietQr::BANKS[$bin][0], 'short' => VietQr::BANKS[$bin][1], 'color' => VietQr::BANKS[$bin][2],
            'account_no' => $no, 'account_name' => mb_strtoupper(Str::ascii($name))];
    }

    public function ready(): bool
    {
        return $this->account() !== null;
    }

    /** Creates the order's instruction once; later calls return the one already issued for the same amount. */
    public function issue(int $orderId, string $orderCode, int $amount): ?object
    {
        $account = $this->account();
        if ($account === null || $amount <= 0) {
            return null;
        }
        $current = DB::table('payment_instructions')->where('order_id', $orderId)->where('state', 'active')->lockForUpdate()->first();
        if ($current && (int) $current->amount === $amount) {
            return $current;
        }
        $version = (int) DB::table('payment_instructions')->where('order_id', $orderId)->max('version') + 1;
        if ($current) {
            DB::table('payment_instructions')->where('id', $current->id)->update(['state' => 'superseded', 'updated_at' => now()]);
        }
        $reference = 'PTS'.strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $orderCode)).($version > 1 ? 'V'.$version : '');
        $id = DB::table('payment_instructions')->insertGetId([
            'order_id' => $orderId, 'version' => $version, 'bank_bin' => $account['bin'], 'bank_name' => $account['bank'],
            'account_no' => $account['account_no'], 'account_name' => $account['account_name'], 'amount' => $amount,
            'reference' => substr($reference, 0, 25), 'qr_text' => VietQr::payload($account['bin'], $account['account_no'], $amount, substr($reference, 0, 25)),
            'state' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('payment_instructions')->find($id);
    }

    public function active(int $orderId): ?object
    {
        return DB::table('payment_instructions')->where('order_id', $orderId)->where('state', 'active')->orderByDesc('version')->first();
    }
}
