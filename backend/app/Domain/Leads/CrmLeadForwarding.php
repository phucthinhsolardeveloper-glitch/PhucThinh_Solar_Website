<?php

namespace App\Domain\Leads;

use App\Domain\Outbox\Outbox;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Hands a saved website lead to the CRM through the outbox (contract:
 * docs/10-audits/2026-11-25-lead-zcrm-handoff.md). Off unless it is switched on and has an address, a key
 * and a start date. Only what sales needs to call back is sent: no IP address, message text, cart or ad click id.
 */
final class CrmLeadForwarding
{
    public const KIND = 'crm_lead';

    private const NEEDS = ['contact' => 'Liên hệ từ website', 'consultation' => 'Tư vấn sản phẩm', 'dealer' => 'Đăng ký đại lý'];

    public static function ready(): bool
    {
        $url = (string) config('site.crm_leads.url');
        // Plain HTTP only for a local or test endpoint: the key travels with every request.
        $secure = str_starts_with($url, 'https://') || (str_starts_with($url, 'http://') && app()->environment('local', 'testing'));

        return (bool) config('site.crm_leads.enabled') && $secure && (string) config('site.crm_leads.key') !== '' && self::since() !== null;
    }

    /** Leads saved before this moment are never sent (CRM_LEADS_SINCE); null when unset or unreadable. */
    private static function since(): ?CarbonImmutable
    {
        $value = trim((string) config('site.crm_leads.since'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2}(:\d{2})?(Z|[+-]\d{2}:?\d{2})?)?$/', $value)) {
            return null;
        }
        try {
            $zone = (string) config('app.timezone');

            return CarbonImmutable::parse($value, $zone)->setTimezone($zone);
        } catch (Throwable) {
            return null;
        }
    }

    /** Queued once per lead: the CRM receives the same externalId on every retry. */
    public static function queue(int $leadId): bool
    {
        if (! self::ready()) {
            return false;
        }
        $lead = DB::table('leads')->where('id', $leadId)->first(['id', 'kind', 'name', 'phone', 'email', 'subject', 'product_id', 'source_url', 'created_at']);
        if ($lead === null || CarbonImmutable::parse($lead->created_at)->lt(self::since())) {
            return false;
        }

        return Outbox::queueData('crm:lead:'.$leadId, self::KIND, 'crm', self::payload($lead));
    }

    /**
     * Queues leads that have no message although they should: the request died after saving the lead, or the
     * outbox could not be written then. Run by the outbox sweep. Returns how many were queued.
     */
    public static function reconcile(int $limit = 100): int
    {
        if (! self::ready()) {
            return 0;
        }
        $ids = DB::table('leads')->where('created_at', '>=', self::since())->where('status', '!=', 'spam')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('outbox_messages')
                ->whereRaw("outbox_messages.dedupe_key = concat('crm:lead:', leads.id)"))
            ->orderBy('id')->limit($limit)->pluck('id');
        $queued = 0;
        foreach ($ids as $id) {
            try {
                $queued += (int) self::queue((int) $id);
            } catch (Throwable $e) {
                Log::warning('Lead could not be handed to the CRM', ['lead' => (int) $id, 'error' => $e->getMessage()]);
            }
        }

        return $queued;
    }

    /** @return array<string, string> */
    private static function payload(object $lead): array
    {
        $need = trim((string) $lead->subject);
        if ($need === '' && $lead->kind === 'consultation' && $lead->product_id) {
            $product = trim(strip_tags((string) DB::table('products')->where('id', $lead->product_id)->value('name')));
            $need = $product !== '' ? self::NEEDS['consultation'].': '.$product : '';
        }
        $path = (string) (parse_url((string) $lead->source_url, PHP_URL_PATH) ?: '');

        return array_filter([
            'externalId' => 'pts-web:'.$lead->id,
            'fullName' => (string) $lead->name,
            'phone' => (string) $lead->phone,
            'email' => (string) $lead->email,
            'need' => mb_substr($need !== '' ? $need : (self::NEEDS[$lead->kind] ?? self::NEEDS['contact']), 0, 200),
            'sourcePath' => $path,
            'kind' => (string) $lead->kind,
            'createdAt' => CarbonImmutable::parse($lead->created_at)->toIso8601String(),
        ], fn (string $value) => $value !== '');
    }

    /**
     * One delivery attempt. "retry" is worth repeating (CRM down or busy); "rejected" is not, until a person
     * fixes the key or the contract. The answer body is never kept: it may describe a customer.
     *
     * @param  array<string, mixed>  $payload
     * @return array{0: 'sent'|'retry'|'rejected', 1: string}
     */
    public static function deliver(array $payload): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => (string) config('site.crm_leads.key')])
                ->acceptJson()->connectTimeout(4)->timeout(8)->withoutRedirecting()
                ->post((string) config('site.crm_leads.url'), $payload);
        } catch (ConnectionException) {
            return ['retry', 'CRM unreachable'];
        }
        $status = $response->status();
        if ($response->successful()) {
            return ['sent', ''];
        }
        $temporary = $status >= 500 || in_array($status, [408, 425, 429], true);

        return [$temporary ? 'retry' : 'rejected', 'CRM answered HTTP '.$status];
    }
}
