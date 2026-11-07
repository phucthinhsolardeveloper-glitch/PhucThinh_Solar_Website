<?php

use App\Support\FormToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
    RateLimiter::clear('form:review|127.0.0.1');
    $this->productId = DB::table('products')->where('is_visible', true)->value('id');
});

function review(array $fields): array
{
    return $fields + ['csrfToken' => FormToken::issue(), 'productId' => test()->productId, 'star' => 5, 'title' => 'Tốt', 'content' => 'Đèn sáng', 'fullname' => 'Khách', 'phone' => '0912345678'];
}

it('queues a text review for approval', function () {
    $this->postJson('/api/v1/review', review([]))->assertOk()->assertJsonPath('ok', true);
    $row = DB::table('reviews')->orderByDesc('id')->first();
    expect($row->status)->toBe('pending')->and((int) $row->product_id)->toBe((int) $this->productId);
});

it('refuses bad stars, phones and tokens', function () {
    $this->postJson('/api/v1/review', review(['star' => 0]))->assertStatus(422);
    $this->postJson('/api/v1/review', review(['phone' => '12']))->assertStatus(422)->assertJsonPath('errors.0', 'Số điện thoại không hợp lệ');
    $this->postJson('/api/v1/review', review(['csrfToken' => 'x']))->assertStatus(419);
});

it('stores up to three real photos and rejects a renamed non-image', function () {
    $photos = [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')];
    $this->post('/api/v1/review', review(['review-file-photo' => $photos]), ['Content-Type' => 'multipart/form-data'])->assertOk();
    $id = DB::table('reviews')->max('id');
    $files = DB::table('review_media')->where('review_id', $id)->where('kind', 'photo')->pluck('path');
    expect($files)->toHaveCount(2);
    Storage::disk('uploads')->assertExists('photo/'.$files[0]);

    $fake = UploadedFile::fake()->createWithContent('c.jpg', 'not an image');
    $this->post('/api/v1/review', review(['review-file-photo' => [$fake]]), ['Content-Type' => 'multipart/form-data'])->assertStatus(422);
});
