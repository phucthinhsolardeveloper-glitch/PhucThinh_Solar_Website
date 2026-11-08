<?php

use App\Filament\Resources\Albums\Pages\CreateAlbum;
use App\Filament\Resources\Albums\Pages\EditAlbum;
use App\Models\Album;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('uploads, reorders and removes album photos through Filament', function () {
    Storage::fake('upload_product');
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'album-media-owner', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $slug = 'album-media-'.bin2hex(random_bytes(4));

    Livewire::test(CreateAlbum::class)->fillForm([
        'title' => 'Album thử media', 'slug' => $slug, 'is_visible' => true,
        'image' => [UploadedFile::fake()->image('cover.jpg')],
        'images' => [
            ['path' => [UploadedFile::fake()->image('first.jpg')], 'caption' => 'Ảnh đầu', 'is_visible' => true],
            ['path' => [UploadedFile::fake()->image('last.jpg')], 'caption' => 'Ảnh cuối', 'is_visible' => true],
        ],
    ])->call('create')->assertHasNoFormErrors();

    $album = Album::query()->where('slug', $slug)->firstOrFail();
    expect($album->images)->toHaveCount(2);
    Storage::disk('upload_product')->assertExists($album->image);
    $rows = $album->images->map(fn ($image) => [
        'id' => $image->id, 'path' => [$image->path], 'caption' => $image->caption, 'is_visible' => true,
    ])->all();
    foreach ($rows as $row) {
        Storage::disk('upload_product')->assertExists($row['path'][0]);
    }

    Livewire::test(EditAlbum::class, ['record' => $album->id])
        ->fillForm(['images' => array_reverse($rows)])->call('save')->assertHasNoFormErrors();
    expect($album->fresh()->images->pluck('caption')->all())->toBe(['Ảnh cuối', 'Ảnh đầu']);

    Livewire::test(EditAlbum::class, ['record' => $album->id])
        ->fillForm(['images' => [$rows[1]]])->call('save')->assertHasNoFormErrors();
    expect($album->fresh()->images->pluck('caption')->all())->toBe(['Ảnh cuối']);
    Storage::disk('upload_product')->assertExists($rows[0]['path'][0]);
});
