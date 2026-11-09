<?php

use App\Domain\Media\MediaInventory;
use App\Domain\Media\MediaReferences;
use App\Models\MediaFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// The inventory describes uploads and who uses them; it never touches a published file.

function png(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAADCAIAAAA2iEnWAAAAEklEQVR4nGP8z8DAwMDAxAADAA8BAQTcV6lmAAAAAElFTkSuQmCC');
}

beforeEach(function () {
    $this->disk = Storage::fake('uploads');
    MediaFile::query()->delete();
});

it('records type, dimensions, hash and the places using each file', function () {
    $this->disk->put('seopage/share.png', png());
    $this->disk->put('seopage/share.png.webp', 'webp copy');
    $this->disk->put('news/inline.png', png());
    $this->disk->put('news/orphan.png', png());
    DB::table('seo_metas')->insert(['page' => 'media-test', 'image' => 'share.png']);
    DB::table('settings')->insert(['key' => 'media_test', 'value' => json_encode('<img src="/upload/news/inline.png">')]);

    $stats = (new MediaInventory($this->disk))->scan();

    $share = MediaFile::query()->where('path', 'seopage/share.png')->firstOrFail();
    expect($share->mime)->toBe('image/png')->and([$share->width, $share->height])->toBe([2, 3])
        ->and($share->sha256)->toBe(hash('sha256', png()))->and($share->usage_count)->toBe(1)
        ->and($share->usages[0])->toStartWith('seo_metas#')
        ->and(MediaFile::query()->where('path', 'seopage/share.png.webp')->value('usage_count'))->toBe(1)
        ->and(MediaFile::query()->where('path', 'news/inline.png')->value('usage_count'))->toBe(1)
        ->and(MediaFile::query()->where('path', 'news/orphan.png')->value('usage_count'))->toBe(0)
        ->and($stats['new'])->toBe(4);
    expect($this->disk->allFiles())->toHaveCount(4);
});

it('lists a referenced file that is missing and forgets it once nothing points at it', function () {
    DB::table('seo_metas')->insert(['page' => 'media-test', 'image' => 'gone.png']);
    (new MediaInventory($this->disk))->scan();

    $row = MediaFile::query()->where('path', 'seopage/gone.png')->firstOrFail();
    expect($row->on_disk)->toBeFalse()->and($row->usage_count)->toBe(1);

    DB::table('seo_metas')->where('page', 'media-test')->delete();
    $stats = (new MediaInventory($this->disk))->scan();
    expect(MediaFile::query()->where('path', 'seopage/gone.png')->exists())->toBeFalse()->and($stats['gone'])->toBeGreaterThanOrEqual(1);
});

it('re-reads only files whose size or date changed', function () {
    $this->disk->put('news/a.png', png());
    $inventory = new MediaInventory($this->disk);
    $inventory->scan();
    expect($inventory->scan()['changed'])->toBe(0);

    $this->disk->put('news/a.png', png().'x');
    touch($this->disk->path('news/a.png'), time() + 5);
    expect($inventory->scan()['changed'])->toBe(1);
});

it('removes only review upload batches older than the limit', function () {
    $this->disk->put('.review-staging/old/a.jpg', 'x');
    $this->disk->put('.review-staging/new/b.jpg', 'x');
    touch($this->disk->path('.review-staging/old/a.jpg'), time() - 3 * 86400);

    expect((new MediaInventory($this->disk))->pruneStaging(24))->toBe(1)
        ->and($this->disk->exists('.review-staging/old/a.jpg'))->toBeFalse()
        ->and($this->disk->exists('.review-staging/new/b.jpg'))->toBeTrue();
});

it('reads bare names, upload links and outside links the way the site does', function () {
    $this->disk->put('product/a.jpg', 'x');
    $refs = new MediaReferences($this->disk);

    expect($refs->resolve('a.jpg', ['news', 'product']))->toBe('product/a.jpg')
        ->and($refs->resolve('/upload/news/b.jpg', ['product']))->toBe('news/b.jpg')
        ->and($refs->resolve('upload/filemanager/c.jpg', ['product']))->toBe('filemanager/c.jpg')
        ->and($refs->resolve('https://phucthinhsolar.com/upload/photo/d.png', ['product']))->toBe('photo/d.png')
        ->and($refs->resolve('https://example.com/upload/photo/d.png', ['product']))->toBeNull()
        ->and($refs->resolve('../../etc/passwd', ['product']))->toBeNull()
        ->and(MediaReferences::links('{"src":"\/upload\/news\/x%20y.jpg"}'))->toBe(['news/x y.jpg']);
});
