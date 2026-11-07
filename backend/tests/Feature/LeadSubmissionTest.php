<?php

use App\Domain\Catalog\ProductDetail\ProductSpecs;
use App\Mail\AdminNotification;
use App\Support\FormToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

// Runs on the pts_laravel13_test copy only; every insert is rolled back.
beforeEach(function () {
    DB::connection('legacy')->beginTransaction();
    Mail::fake();
    RateLimiter::clear('form:contact|127.0.0.1');
    RateLimiter::clear('form:newsletter-dealer|127.0.0.1');
});
afterEach(fn () => DB::connection('legacy')->rollBack());

function lead(array $body)
{
    return test()->postJson('/api/v1/lead-form', $body);
}

it('refuses a missing or forged form token with 419', function () {
    lead(['kind' => 'contact', 'csrfToken' => 'x', 'data' => []])->assertStatus(419)->assertJsonPath('error.code', 'INVALID_SESSION');
    lead(['kind' => 'contact', 'csrfToken' => time().'.'.str_repeat('a', 64), 'data' => []])->assertStatus(419);
});

it('refuses unknown kinds and broken bodies', function () {
    lead(['kind' => 'spam'])->assertStatus(400)->assertJsonPath('error.code', 'BAD_KIND');
    test()->call('POST', '/api/v1/lead-form', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'not json')->assertStatus(400);
});

it('lists every validation problem in Vietnamese', function () {
    lead(['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => ['phone' => '123', 'email' => 'x']])
        ->assertStatus(422)->assertJsonPath('error.message', 'Họ tên không được trống. Số điện thoại không hợp lệ. Email không hợp lệ. Nội dung không được trống.');
});

it('saves a contact request where the PHP Admin lists it and mails the admin', function () {
    $before = DB::connection('legacy')->table('table_contact')->count();
    lead(['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => ['fullname' => 'Khách <Thử>', 'phone' => '0912345678', 'content' => 'Cần tư vấn']])
        ->assertOk()->assertJsonPath('ok', true);
    $row = DB::connection('legacy')->table('table_contact')->orderByDesc('id')->first();
    expect(DB::connection('legacy')->table('table_contact')->count())->toBe($before + 1)
        ->and($row->fullname)->toBe('Khách &lt;Thử&gt;')
        ->and($row->subject)->toBe('Yêu cầu tư vấn từ website');
    Mail::assertSent(AdminNotification::class, fn ($mail) => $mail->copy['subject'] === '[Liên hệ mới] Yêu cầu tư vấn từ website');
});

it('stops the sixth submission in ten minutes from one address', function () {
    foreach (range(1, 5) as $i) {
        lead(['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => []])->assertStatus(422);
    }
    lead(['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => []])->assertStatus(429);
});

it('requires a real province for dealer sign-ups', function () {
    lead(['kind' => 'dealer', 'csrfToken' => FormToken::issue(), 'data' => ['fullname' => 'A', 'phone' => '0912345678', 'email' => 'a@b.vn', 'company' => 'C', 'city_id' => '999999']])
        ->assertStatus(422)->assertJsonPath('error.message', 'Vui lòng chọn tỉnh/thành phố hợp lệ.');
});

it('expires form tokens after two hours', function () {
    expect(FormToken::valid(FormToken::issue(time() - 7201)))->toBeFalse()
        ->and(FormToken::valid(FormToken::issue(time() - 60)))->toBeTrue();
});

it('saves a product consultation with the profile the product page sent', function () {
    RateLimiter::clear('form:newsletter-consulting|127.0.0.1');
    $product = DB::connection('legacy')->table('table_product')->where('type', 'san-pham')->whereRaw("find_in_set('hienthi', status)")->first();
    $profile = app(ProductSpecs::class)->profileKey((array) $product,
        (array) DB::connection('legacy')->table('table_product_list')->where('id', $product->id_list)->first(['slugvi', 'pdp_profile']));
    $data = ['fullname' => 'Khách thử', 'phone' => '0912345678', 'product_id' => (string) $product->id, 'profile_key' => $profile];

    lead(['kind' => 'product', 'csrfToken' => FormToken::issue(), 'data' => ['profile_key' => 'sai'] + $data])->assertStatus(422);
    lead(['kind' => 'product', 'csrfToken' => FormToken::issue(), 'data' => $data])->assertOk();
    $row = DB::connection('legacy')->table('table_newsletter')->orderByDesc('id')->first();
    expect($row->type)->toBe('dangkynhantin')->and((int) $row->product_id)->toBe((int) $product->id)->and($row->profile_key)->toBe($profile);
});
