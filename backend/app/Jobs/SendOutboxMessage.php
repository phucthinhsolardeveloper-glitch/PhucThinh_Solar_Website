<?php

namespace App\Jobs;

use App\Domain\Leads\CrmLeadForwarding;
use App\Domain\Outbox\Outbox;
use App\Support\LogSafe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Sends one outbox message. Claiming the row first makes a duplicate job a no-op. */
class SendOutboxMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const MAX_ATTEMPTS = 6;

    public int $tries = self::MAX_ATTEMPTS;

    public function __construct(public readonly int $messageId) {}

    /** @return list<int> seconds before attempt 2, 3, … */
    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }

    public function handle(): void
    {
        $claimed = DB::table('outbox_messages')->where('id', $this->messageId)->whereIn('status', ['pending', 'retrying'])
            ->update(['status' => 'sending', 'attempts' => DB::raw('attempts + 1'), 'updated_at' => now()]);
        if ($claimed === 0) {
            return; // already sent, being sent, or given up
        }
        /** @var object{kind: string, payload: string, recipient: string, attempts: int} $message */
        $message = DB::table('outbox_messages')->where('id', $this->messageId)->first();
        if ($message->kind === CrmLeadForwarding::KIND) {
            $this->forwardLead($message->payload, (int) $message->attempts);

            return;
        }
        $mail = Outbox::mailable((string) $message->payload);
        if ($mail === null) {
            $this->finish('failed', 'Payload could not be rebuilt');

            return;
        }
        try {
            Mail::to($message->recipient)->send($mail);
        } catch (Throwable $e) {
            $giveUp = (int) $message->attempts >= self::MAX_ATTEMPTS;
            // A mail server error may name the recipient: neither the row nor the worker log keeps its text.
            $error = LogSafe::error($e);
            $this->finish($giveUp ? 'failed' : 'retrying', $error);
            if (! $giveUp) {
                throw new RuntimeException('Outbox mail '.$this->messageId.' failed: '.$error);
            }

            return;
        }
        $this->sent();
    }

    private function forwardLead(string $payload, int $attempts): void
    {
        $data = json_decode((string) base64_decode($payload, true), true);
        if (! is_array($data)) {
            $this->finish('failed', 'Payload could not be rebuilt');

            return;
        }
        if (! CrmLeadForwarding::ready()) {
            $this->finish('cancelled', 'CRM hand-over is switched off');

            return;
        }
        [$result, $error] = CrmLeadForwarding::deliver($data);
        if ($result === 'sent') {
            $this->sent();

            return;
        }
        $giveUp = $result === 'rejected' || $attempts >= self::MAX_ATTEMPTS;
        $this->finish($giveUp ? 'failed' : 'retrying', $error);
        if (! $giveUp) {
            throw new RuntimeException('Outbox lead '.$this->messageId.' failed: '.$error);
        }
    }

    private function sent(): void
    {
        DB::table('outbox_messages')->where('id', $this->messageId)->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null, 'updated_at' => now()]);
    }

    public function failed(?Throwable $e): void
    {
        $this->finish('failed', $e !== null ? LogSafe::error($e) : 'Job failed');
    }

    private function finish(string $status, string $error): void
    {
        DB::table('outbox_messages')->where('id', $this->messageId)->where('status', '!=', 'sent')
            ->update(['status' => $status, 'last_error' => Str::limit($error, 1000), 'updated_at' => now()]);
    }
}
