<?php

namespace App\Domain\Outbox;

use App\Domain\Leads\CrmLeadForwarding;
use App\Jobs\SendOutboxMessage;
use App\Mail\AdminNotification;
use App\Mail\OrderNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;

/**
 * Durable outgoing messages: mail, and website leads for the CRM. Call queue() inside the transaction that saves the business row: the
 * message commits or rolls back with it, and its job is dispatched only after commit. A repeated
 * dedupe key is ignored, so a second callback or retry never sends a second notification.
 */
final class Outbox
{
    /** Mailables the outbox may rebuild; nothing else is unserialized. */
    public const MAILABLES = [OrderNotification::class, AdminNotification::class];

    public static function queue(string $dedupeKey, string $kind, string $recipient, Mailable $mail): bool
    {
        return self::store($dedupeKey, $kind, $recipient, serialize($mail));
    }

    /** A message that is plain data, delivered by the job according to its kind. */
    public static function queueData(string $dedupeKey, string $kind, string $recipient, array $data): bool
    {
        return self::store($dedupeKey, $kind, $recipient, (string) json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    private static function store(string $dedupeKey, string $kind, string $recipient, string $body): bool
    {
        $now = now();
        $inserted = DB::table('outbox_messages')->insertOrIgnore([
            'dedupe_key' => $dedupeKey, 'kind' => $kind, 'recipient' => $recipient,
            'payload' => base64_encode($body), 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
        ]);
        if ($inserted === 0) {
            return false;
        }
        $id = (int) DB::table('outbox_messages')->where('dedupe_key', $dedupeKey)->value('id');
        SendOutboxMessage::dispatch($id)->afterCommit();

        return true;
    }

    public static function mailable(string $payload): ?Mailable
    {
        $mail = @unserialize(base64_decode($payload, true) ?: '', ['allowed_classes' => self::MAILABLES]);

        return $mail instanceof Mailable ? $mail : null;
    }

    /**
     * Mail counts, with failed CRM hand-overs apart: they need a different fix.
     *
     * @return array{pending: int, retrying: int, failed: int, failed_jobs: int, crm_failed: int}
     */
    public static function health(): array
    {
        $counts = DB::table('outbox_messages')->whereIn('status', ['pending', 'sending', 'retrying', 'failed'])
            ->where('kind', '!=', CrmLeadForwarding::KIND)
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0) + (int) ($counts['sending'] ?? 0),
            'retrying' => (int) ($counts['retrying'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'crm_failed' => DB::table('outbox_messages')->where('kind', CrmLeadForwarding::KIND)->where('status', 'failed')->count(),
        ];
    }

    /**
     * Re-dispatches messages whose job was lost (worker crash, queue cleared) and frees messages
     * stuck in "sending". Safe to run often: the job itself claims a message before sending.
     */
    public static function sweep(int $staleMinutes = 10): int
    {
        $stale = now()->subMinutes($staleMinutes);
        $ids = DB::table('outbox_messages')->whereIn('status', ['pending', 'retrying', 'sending'])->where('updated_at', '<', $stale)
            ->where('attempts', '<', SendOutboxMessage::MAX_ATTEMPTS)->pluck('id');
        DB::table('outbox_messages')->whereIn('id', $ids)->where('status', 'sending')->update(['status' => 'retrying', 'updated_at' => now()]);
        foreach ($ids as $id) {
            SendOutboxMessage::dispatch((int) $id);
        }

        return $ids->count();
    }
}
