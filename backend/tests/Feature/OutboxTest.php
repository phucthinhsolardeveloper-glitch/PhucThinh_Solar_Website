<?php

use App\Domain\Outbox\Outbox;
use App\Jobs\SendOutboxMessage;
use App\Mail\AdminNotification;
use App\Support\FormToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

// Notifications are written to the outbox with the business row and sent once by the queue.

function alert(string $subject = 'Thử'): AdminNotification
{
    return new AdminNotification(['subject' => $subject, 'heading' => $subject, 'intro' => '', 'closing' => ''], ['Họ tên' => 'Khách']);
}

it('sends a queued message once and records it as sent', function () {
    Mail::fake();
    expect(Outbox::queue('test:1', 'contact_admin', 'admin@example.test', alert()))->toBeTrue();

    $row = DB::table('outbox_messages')->where('dedupe_key', 'test:1')->first();
    expect($row->status)->toBe('sent')->and((int) $row->attempts)->toBe(1)->and($row->sent_at)->not->toBeNull();
    Mail::assertSent(AdminNotification::class, 1);

    (new SendOutboxMessage((int) $row->id))->handle();
    Mail::assertSent(AdminNotification::class, 1);
});

it('ignores a repeated dedupe key', function () {
    Queue::fake();
    expect(Outbox::queue('test:2', 'contact_admin', 'a@example.test', alert()))->toBeTrue()
        ->and(Outbox::queue('test:2', 'contact_admin', 'a@example.test', alert()))->toBeFalse()
        ->and(DB::table('outbox_messages')->where('dedupe_key', 'test:2')->count())->toBe(1);
    Queue::assertPushed(SendOutboxMessage::class, 1);
});

it('keeps a message for retry when the mail server fails, and gives up after the last attempt', function () {
    Queue::fake();
    Outbox::queue('test:3', 'contact_admin', 'a@example.test', alert());
    $id = (int) DB::table('outbox_messages')->where('dedupe_key', 'test:3')->value('id');
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    expect(fn () => (new SendOutboxMessage($id))->handle())->toThrow(RuntimeException::class);
    $row = DB::table('outbox_messages')->find($id);
    expect($row->status)->toBe('retrying')->and($row->last_error)->toContain('SMTP down');

    DB::table('outbox_messages')->where('id', $id)->update(['attempts' => SendOutboxMessage::MAX_ATTEMPTS - 1]);
    (new SendOutboxMessage($id))->handle();
    expect(DB::table('outbox_messages')->where('id', $id)->value('status'))->toBe('failed')
        ->and(Outbox::health()['failed'])->toBeGreaterThanOrEqual(1);
});

it('rolls the message back with the transaction that wrote it', function () {
    Mail::fake();
    try {
        DB::transaction(function () {
            Outbox::queue('test:4', 'contact_admin', 'a@example.test', alert());
            throw new RuntimeException('order failed');
        });
    } catch (RuntimeException) {
    }
    expect(DB::table('outbox_messages')->where('dedupe_key', 'test:4')->exists())->toBeFalse();
    Mail::assertNothingSent();
});

it('re-dispatches stalled messages and frees ones stuck while sending', function () {
    Queue::fake();
    Outbox::queue('test:5', 'contact_admin', 'a@example.test', alert());
    DB::table('outbox_messages')->where('dedupe_key', 'test:5')->update(['status' => 'sending', 'updated_at' => now()->subHour()]);

    expect(Outbox::sweep())->toBeGreaterThanOrEqual(1)
        ->and(DB::table('outbox_messages')->where('dedupe_key', 'test:5')->value('status'))->toBe('retrying');
    Queue::assertPushed(SendOutboxMessage::class, 2);
});

it('never rebuilds anything but the allowed mailables', function () {
    expect(Outbox::mailable(base64_encode(serialize(new ArrayObject([1])))))->toBeNull()
        ->and(Outbox::mailable(base64_encode(serialize(alert()))))->toBeInstanceOf(AdminNotification::class);
});

it('queues a lead alert with the saved lead', function () {
    Mail::fake();
    config(['mail.admin_to' => 'sales@example.test']);
    RateLimiter::clear('form:contact|127.0.0.1');
    $this->postJson('/api/v1/lead-form', ['kind' => 'contact', 'csrfToken' => FormToken::issue(),
        'data' => ['fullname' => 'Khách thử', 'phone' => '0912345678', 'content' => 'Cần tư vấn']])->assertOk();
    $lead = DB::table('leads')->orderByDesc('id')->value('id');
    expect(DB::table('outbox_messages')->where('dedupe_key', 'lead:'.$lead.':contact_admin')->value('status'))->toBe('sent');
    Mail::assertSent(AdminNotification::class, 1);
});
