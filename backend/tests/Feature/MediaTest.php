<?php

// Uses a real picture from the mounted upload volume; generated thumbnails go to a temporary disk.
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('thumbs');
    $files = glob(rtrim((string) config('legacy.upload_path'), '/').'/product/*.{png,jpg}', GLOB_BRACE) ?: [];
    $this->file = $files ? basename($files[0]) : null;
    if ($this->file === null) {
        $this->markTestSkipped('No product picture in the upload volume.');
    }
});

it('serves uploaded files with a long cache', function () {
    $this->get('/upload/product/'.$this->file)->assertOk()->assertHeader('Cache-Control', 'max-age=2592000, public');
});

it('makes a fitted thumbnail that keeps the picture proportions, and a cropped WebP copy', function () {
    $fit = $this->get('/thumbs/400x300x3/upload/product/'.$this->file)->assertOk();
    [$w, $h] = getimagesize($fit->baseResponse->getFile()->getPathname());
    expect($w <= 400 && $h <= 300 && ($w === 400 || $h === 300))->toBeTrue();

    $crop = $this->get('/thumbs/200x200x1/upload/product/'.$this->file.'.webp')->assertOk()->assertHeader('Content-Type', 'image/webp');
    expect(array_slice(getimagesize($crop->baseResponse->getFile()->getPathname()), 0, 2))->toBe([200, 200]);
});

it('refuses sizes over 2000px, unknown folders and path tricks', function () {
    $this->get('/thumbs/2400x300x1/upload/product/'.$this->file)->assertNotFound();
    $this->get('/thumbs/200x200x1/upload/secret/'.$this->file)->assertNotFound();
    $this->get('/upload/product/..%2F..%2F.env')->assertNotFound();
});
