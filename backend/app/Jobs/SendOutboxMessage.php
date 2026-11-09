<?php

namespace App\Jobs;

use App\Domain\Outbox\Outbox;
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
        /** @var object{payload: string, recipient: string, attempts: int} $message */
        $message = DB::table('outbox_messages')->where('id', $this->messageId)->first();
        $mail = Outbox::mailable((string) $message->payload);
        if ($mail === null) {
            $this->finish('failed', 'Payload could not be rebuilt');

            return;
        }
        try {
            Mail::to($message->recipient)->send($mail);
        } catch (Throwable $e) {
            $giveUp = (int) $message->attempts >= self::MAX_ATTEMPTS;
            $this->finish($giveUp ? 'failed' : 'retrying', $e->getMessage());
            if (! $giveUp) {
                throw new RuntimeException('Outbox mail '.$this->messageId.' failed: '.Str::limit($e->getMessage(), 200), 0, $e);
            }

            return;
        }
        DB::table('outbox_messages')->where('id', $this->messageId)->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null, 'updated_at' => now()]);
    }

    public function failed(?Throwable $e): void
    {
        $this->finish('failed', $e?->getMessage() ?? 'Job failed');
    }

    private function finish(string $status, string $error): void
    {
        DB::table('outbox_messages')->where('id', $this->messageId)->where('status', '!=', 'sent')
            ->update(['status' => $status, 'last_error' => Str::limit($error, 1000), 'updated_at' => now()]);
    }
}
