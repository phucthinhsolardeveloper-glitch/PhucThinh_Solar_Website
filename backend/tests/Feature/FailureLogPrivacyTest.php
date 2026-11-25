<?php

use App\Domain\Leads\CrmLeadForwarding;
use App\Models\User;
use App\Support\FormToken;
use Illuminate\Database\QueryException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

// A write that fails must not copy the customer into the log: a database error repeats the statement with its values.

beforeEach(function () {
    Mail::fake();
    Queue::fake();
    RateLimiter::clear('form:contact|127.0.0.1');
    RateLimiter::clear('form:checkout|127.0.0.1');
    config(['session.driver' => 'file', 'mail.admin_to' => 'sales@example.test']);
    $GLOBALS['shopCookies'] = [];
    $GLOBALS['logged'] = [];
    Event::listen(MessageLogged::class, function (MessageLogged $event) {
        $GLOBALS['logged'][] = ['message' => $event->message, 'context' => $event->context];
    });
});

const PRIVATE_NAME = 'Khach Rieng Tu';
const PRIVATE_PHONE = '0944555957';
const PRIVATE_EMAIL = 'rieng-tu@example.test';

/** Every insert into the table fails the way the database reports it, statement and values included. */
function failInsertsInto(string $table): void
{
    DB::connection()->beforeExecuting(function (string $query, array $bindings, $connection) use ($table) {
        if (preg_match('/^insert (ignore )?into `'.$table.'`/i', $query)) {
            throw new QueryException((string) $connection->getName(), $query, $bindings, new PDOException('simulated write failure'));
        }
    });
}

/** The messages logged, after checking that none of them carries the customer or the failed statement. */
function privateLogMessages(): array
{
    $text = (string) json_encode($GLOBALS['logged'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    foreach ([PRIVATE_NAME, PRIVATE_PHONE, PRIVATE_EMAIL, 'insert into', 'insert ignore', base64_encode('{"externalId"')] as $secret) {
        expect(str_contains($text, $secret))->toBeFalse("The log repeats: {$secret}");
    }

    return array_column($GLOBALS['logged'], 'message');
}

function sendPrivateContact()
{
    return test()->postJson('/api/v1/lead-form', ['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => [
        'fullname' => PRIVATE_NAME, 'phone' => PRIVATE_PHONE, 'email' => PRIVATE_EMAIL, 'content' => 'Noi dung rieng', 'source_url' => '/lien-he/',
    ]]);
}

it('logs a lead that cannot be saved without the customer', function () {
    failInsertsInto('leads');

    sendPrivateContact()->assertStatus(500);

    expect(privateLogMessages())->toContain('Lead insert failed');
    expect($GLOBALS['logged'][0]['context']['error'])->toStartWith(QueryException::class);
});

it('logs messages that cannot be queued without the customer, and still saves the lead', function () {
    config(['site.crm_leads' => ['enabled' => true, 'url' => 'https://crm.example.test/api/public/leads', 'key' => 'test-key',
        'since' => now()->subMinute()->toIso8601String()]]);
    failInsertsInto('outbox_messages');

    sendPrivateContact()->assertOk();
    expect(CrmLeadForwarding::reconcile())->toBe(0);

    expect(privateLogMessages())->toContain('Lead could not be handed to the CRM', 'Lead notification could not be queued', 'Customer acknowledgement could not be queued');
});

it('logs an Admin bell that cannot be written without the customer', function () {
    failInsertsInto('notifications');
    User::query()->create(['name' => 'Chu', 'username' => 'chu-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);

    sendPrivateContact()->assertOk();

    expect(privateLogMessages())->toContain('Admin bell could not ring');
});

it('logs an order that cannot be saved without the customer', function () {
    $product = DB::table('products')->where('is_visible', true)->where('quote_only', false)
        ->where(fn ($q) => $q->where('regular_price', '>', 0)->orWhere('sale_price', '>', 0))->orderBy('id')->first();
    $ward = DB::table('wards as w')->join('provinces as p', 'p.id', '=', 'w.province_id')
        ->where('w.is_visible', true)->where('p.is_visible', true)->select('w.id', 'w.province_id as id_city')->first();
    $payment = DB::table('payment_methods')->where('is_visible', true)->value('id');
    shop('POST', '/api/v1/cart', ['action' => 'add', 'csrfToken' => FormToken::issue(), 'productId' => $product->id, 'quantity' => 1])->assertOk();
    $checkout = shop('GET', '/api/v1/checkout')->assertOk();
    failInsertsInto('orders');

    shop('POST', '/api/v1/checkout', ['csrfToken' => $checkout->json('csrfToken'), 'checkoutToken' => $checkout->json('checkoutToken'), 'data' => [
        'fullname' => PRIVATE_NAME, 'phone' => PRIVATE_PHONE, 'email' => PRIVATE_EMAIL, 'city' => $ward->id_city, 'ward' => $ward->id, 'address' => '1 Duong Thu', 'payments' => $payment,
    ]])->assertStatus(500);

    expect(privateLogMessages())->toContain('Checkout failed to save the order');
});
