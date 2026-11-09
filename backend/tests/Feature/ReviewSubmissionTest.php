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

it('rejects a renamed video and stores only a validated video with its poster', function () {
    $poster = UploadedFile::fake()->image('cover.jpg');
    $fake = UploadedFile::fake()->createWithContent('clip.mp4', 'not a video');
    $this->post('/api/v1/review', review(['review-file-video' => $fake, 'review-poster-video' => $poster]), ['Content-Type' => 'multipart/form-data'])->assertStatus(422);
    expect(Storage::disk('uploads')->allFiles())->toBe([]);

    $box = fn (string $type, string $data) => pack('N', 8 + strlen($data)).$type.$data;
    $content = $box('ftyp', 'isom'.pack('N', 0).'isomiso2').$box('moov', 'movie-metadata').$box('mdat', 'media-data');
    $video = UploadedFile::fake()->createWithContent('clip.mp4', $content);
    $this->post('/api/v1/review', review(['review-file-video' => $video, 'review-poster-video' => $poster]), ['Content-Type' => 'multipart/form-data'])->assertOk();
    $id = DB::table('reviews')->max('id');
    $media = DB::table('review_media')->where('review_id', $id)->first();
    expect($media->kind)->toBe('video')->and($media->path)->not->toBe('')->and($media->poster)->not->toBeNull();
    Storage::disk('uploads')->assertExists('video/'.$media->path);
    Storage::disk('uploads')->assertExists('photo/'.$media->poster);
    expect(Storage::disk('uploads')->allFiles('.review-staging'))->toBe([]);
});

it('removes staged and promoted files when saving review media rolls back', function () {
    $before = DB::table('reviews')->count();
    DB::connection()->beforeExecuting(function (string $query): void {
        if (preg_match('/insert into [`"]?review_media/i', $query)) {
            throw new RuntimeException('Injected media insert failure');
        }
    });
    $photo = UploadedFile::fake()->image('rollback.jpg');
    $this->post('/api/v1/review', review(['review-file-photo' => [$photo]]), ['Content-Type' => 'multipart/form-data'])->assertStatus(500);

    expect(DB::table('reviews')->count())->toBe($before)
        ->and(Storage::disk('uploads')->allFiles('photo'))->toBe([])
        ->and(Storage::disk('uploads')->allFiles('.review-staging'))->toBe([]);
});
