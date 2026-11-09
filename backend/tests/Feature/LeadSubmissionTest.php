<?php

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\ProductDetail\ProductSpecs;
use App\Domain\Leads\LeadSource;
use App\Mail\AdminNotification;
use App\Models\User;
use App\Support\FormToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

// Every insert is rolled back after the test.
beforeEach(function () {
    Mail::fake();
    RateLimiter::clear('form:contact|127.0.0.1');
    RateLimiter::clear('form:newsletter-dealer|127.0.0.1');
});

function lead(array $body)
{
    return test()->postJson('/api/v1/lead-form', $body);
}

it('refuses a missing or forged form token with 419', function () {
    lead(['kind' => 'contact', 'csrfToken' => 'x', 'data' => []])->assertStatus(419)->assertJsonPath('error.code', 'INVALID_SESSION');
    lead(['kind' => 'contact', 'csrfToken' => str_pad(dechex(time()), 8, '0', STR_PAD_LEFT).str_repeat('a', 56), 'data' => []])->assertStatus(419);
});

it('refuses unknown kinds and broken bodies', function () {
    lead(['kind' => 'spam'])->assertStatus(400)->assertJsonPath('error.code', 'BAD_KIND');
    test()->call('POST', '/api/v1/lead-form', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'not json')->assertStatus(400);
});

it('lists every validation problem in Vietnamese', function () {
    lead(['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => ['phone' => '123', 'email' => 'x']])
        ->assertStatus(422)->assertJsonPath('error.message', 'Họ tên không được trống. Số điện thoại không hợp lệ. Email không hợp lệ. Nội dung không được trống.');
});

it('saves a contact request as a lead and mails the admin', function () {
    $before = DB::table('leads')->count();
    lead(['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => ['fullname' => 'Khách <Thử>', 'phone' => '0912345678', 'content' => 'Cần tư vấn']])
        ->assertOk()->assertJsonPath('ok', true);
    $row = DB::table('leads')->orderByDesc('id')->first();
    expect(DB::table('leads')->count())->toBe($before + 1)
        ->and($row->kind)->toBe('contact')
        ->and($row->name)->toBe('Khách <Thử>')
        ->and($row->status)->toBe('new')
        ->and($row->subject)->toBe('Yêu cầu tư vấn từ website');
    Mail::assertSent(AdminNotification::class, fn ($mail) => $mail->copy['subject'] === '[Liên hệ mới] Yêu cầu tư vấn từ website');
});

it('keeps safe page attribution and connects product-page leads to the product', function () {
    $product = DB::table('products')->where('is_visible', true)->first();
    $source = '/san-pham/'.$product->slug.'/?utm_source=google&utm_campaign=solar-2026&email=private%40example.vn';
    lead(['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => [
        'fullname' => 'Khách thử', 'phone' => '0912345678', 'content' => 'Cần tư vấn', 'source_url' => $source,
    ]])->assertOk();
    $saved = DB::table('leads')->orderByDesc('id')->first();
    expect($saved->source_url)->toBe('/san-pham/'.$product->slug.'/?utm_source=google&utm_campaign=solar-2026')
        ->and((int) $saved->product_id)->toBe((int) $product->id);

    lead(['kind' => 'contact', 'csrfToken' => FormToken::issue(), 'data' => [
        'fullname' => 'Khách thử', 'phone' => '0912345678', 'content' => 'Cần tư vấn', 'source_url' => 'https://other.example/san-pham/'.$product->slug,
    ]])->assertOk();
    $external = DB::table('leads')->orderByDesc('id')->first();
    expect($external->source_url)->toBeNull()->and($external->product_id)->toBeNull();
    expect(LeadSource::normalize('/admin/users'))->toBeNull()
        ->and(LeadSource::normalize('/san-pham/%2E%2E/'))->toBeNull();

    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'lead-source-owner', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin')->get('/admin/leads')->assertOk()
        ->assertSee('Trang nguồn')->assertSee('/san-pham/'.$product->slug);
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

it('issues tokens in the 64-hex shape the Nuxt contracts accept', function () {
    expect(FormToken::issue())->toMatch('/^[a-f0-9]{64}$/');
});

it('expires form tokens after two hours', function () {
    expect(FormToken::valid(FormToken::issue(time() - 7201)))->toBeFalse()
        ->and(FormToken::valid(FormToken::issue(time() - 60)))->toBeTrue();
});

it('saves a product consultation with the profile the product page sent', function () {
    RateLimiter::clear('form:newsletter-consulting|127.0.0.1');
    $product = DB::table('products')->where('is_visible', true)->whereNotNull('category_id')->first();
    $root = app(CategoryTree::class)->atDepth((int) $product->category_id, 1);
    $profile = app(ProductSpecs::class)->profileKey(['pdp_profile' => $product->pdp_profile], ['pdp_profile' => $root['pdp_profile'] ?? '', 'slug' => $root['slug'] ?? '']);
    $data = ['fullname' => 'Khách thử', 'phone' => '0912345678', 'product_id' => (string) $product->id, 'profile_key' => $profile,
        'source_url' => '/san-pham/'.$product->slug];

    lead(['kind' => 'product', 'csrfToken' => FormToken::issue(), 'data' => ['profile_key' => 'sai'] + $data])->assertStatus(422);
    lead(['kind' => 'product', 'csrfToken' => FormToken::issue(), 'data' => $data])->assertOk();
    $row = DB::table('leads')->orderByDesc('id')->first();
    expect($row->kind)->toBe('consultation')->and((int) $row->product_id)->toBe((int) $product->id)->and($row->profile_key)->toBe($profile)
        ->and($row->source_url)->toBe('/san-pham/'.$product->slug);
});
