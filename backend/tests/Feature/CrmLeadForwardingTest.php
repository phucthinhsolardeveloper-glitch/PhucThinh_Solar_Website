<?php

use App\Domain\Leads\CrmLeadForwarding;
use App\Domain\Outbox\Outbox;
use App\Jobs\SendOutboxMessage;
use App\Support\FormToken;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

// Website lead → CRM, against a faked endpoint only.

beforeEach(function () {
    Mail::fake();
    Http::preventStrayRequests();
    RateLimiter::clear('form:contact|127.0.0.1');
});

function crmOn(): void
{
    config(['site.crm_leads' => ['enabled' => true, 'url' => 'https://crm.example.test/api/public/leads', 'key' => 'test-key']]);
}

function sendContact(array $data = [])
{
    return test()->postJson('/api/v1/lead-form', ['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => $data + [
        'fullname' => 'Khách thử', 'phone' => '0944 555 957', 'content' => 'Nội dung riêng của khách',
        'source_url' => '/lien-he/?gclid=abc123&utm_source=google',
    ]]);
}

function crmRow(): ?object
{
    $id = (int) DB::table('leads')->orderByDesc('id')->value('id');

    return DB::table('outbox_messages')->where('dedupe_key', 'crm:lead:'.$id)->first();
}

/** A lead whose CRM message waits for the queue, so the test drives each attempt. */
function waitingCrmMessage(): int
{
    Queue::fake();
    crmOn();
    sendContact()->assertOk();

    return (int) crmRow()->id;
}

it('sends nothing while the connection is off or incomplete', function (array $settings) {
    Http::fake();
    config(['site.crm_leads' => $settings]);

    sendContact()->assertOk();

    expect(crmRow())->toBeNull()->and(CrmLeadForwarding::ready())->toBeFalse();
    Http::assertNothingSent();
})->with([
    'switched off' => [['enabled' => false, 'url' => 'https://crm.example.test/leads', 'key' => 'k']],
    'no key' => [['enabled' => true, 'url' => 'https://crm.example.test/leads', 'key' => '']],
    'no address' => [['enabled' => true, 'url' => '', 'key' => 'k']],
]);

it('is off in a default installation', function () {
    expect(config('site.crm_leads.enabled'))->toBeFalse()->and(CrmLeadForwarding::ready())->toBeFalse();
});

it('sends a saved lead once, with only what sales needs to call back', function () {
    Http::fake(['crm.example.test/*' => Http::response(['contactId' => 'c1', 'created' => true], 201)]);
    crmOn();

    sendContact()->assertOk();

    $lead = DB::table('leads')->orderByDesc('id')->first();
    expect(crmRow()->status)->toBe('sent');
    expect(CrmLeadForwarding::queue((int) $lead->id))->toBeFalse();
    (new SendOutboxMessage((int) crmRow()->id))->handle();
    Http::assertSentCount(1);
    Http::assertSent(function (Request $request) use ($lead) {
        $body = $request->data();

        return $request->hasHeader('X-Api-Key', 'test-key') && $body['externalId'] === 'pts-web:'.$lead->id
            && $body['phone'] === '0944555957' && $body['kind'] === 'contact' && $body['sourcePath'] === '/lien-he/'
            && array_diff(array_keys($body), ['externalId', 'fullName', 'phone', 'email', 'need', 'sourcePath', 'kind', 'createdAt']) === []
            && ! str_contains($request->body(), 'gclid') && ! str_contains($request->body(), 'Nội dung riêng');
    });
});

// The test queue runs inside the request, the worst case: the production queue is a separate worker.
it('saves the lead and answers the visitor when the CRM is down', function () {
    Http::fake(['crm.example.test/*' => Http::response('', 503)]);
    crmOn();
    $before = DB::table('leads')->count();

    sendContact()->assertOk()->assertJsonPath('ok', true);

    expect(DB::table('leads')->count())->toBe($before + 1)->and(crmRow()->status)->not->toBe('sent');
});

it('keeps a busy or unreachable CRM for retry and gives up after the last attempt', function (int $status) {
    $id = waitingCrmMessage();
    Http::fake(['crm.example.test/*' => Http::response('secret customer text', $status)]);

    expect(fn () => (new SendOutboxMessage($id))->handle())->toThrow(RuntimeException::class);
    $row = DB::table('outbox_messages')->find($id);
    expect($row->status)->toBe('retrying')->and($row->last_error)->toBe('CRM answered HTTP '.$status);

    DB::table('outbox_messages')->where('id', $id)->update(['attempts' => SendOutboxMessage::MAX_ATTEMPTS - 1]);
    (new SendOutboxMessage($id))->handle();
    expect(DB::table('outbox_messages')->find($id)->status)->toBe('failed');
})->with([429, 500, 503]);

it('treats a lost connection as temporary', function () {
    $id = waitingCrmMessage();
    Http::fake(['crm.example.test/*' => Http::failedConnection()]);

    expect(fn () => (new SendOutboxMessage($id))->handle())->toThrow(RuntimeException::class);
    expect(DB::table('outbox_messages')->find($id)->last_error)->toBe('CRM unreachable');
});

it('does not repeat a refused request, and counts it apart from failed mail', function (int $status) {
    $id = waitingCrmMessage();
    Http::fake(['crm.example.test/*' => Http::response('', $status)]);
    $before = Outbox::health();

    (new SendOutboxMessage($id))->handle();

    expect(DB::table('outbox_messages')->find($id)->status)->toBe('failed')
        ->and(Outbox::health()['crm_failed'])->toBe($before['crm_failed'] + 1)
        ->and(Outbox::health()['failed'])->toBe($before['failed']);
    Http::assertSentCount(1);
})->with([301, 401, 403, 422]);

it('cancels a waiting lead when the connection is switched off', function () {
    $id = waitingCrmMessage();
    Http::fake();
    config(['site.crm_leads.enabled' => false]);

    (new SendOutboxMessage($id))->handle();

    expect(DB::table('outbox_messages')->find($id)->status)->toBe('cancelled');
    Http::assertNothingSent();
});
