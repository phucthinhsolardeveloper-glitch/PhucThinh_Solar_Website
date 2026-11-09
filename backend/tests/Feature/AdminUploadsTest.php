<?php

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Livewire;

/** Points every upload disk at a fresh folder, so files are really written and read back. */
function uploadRoot(): string
{
    $root = storage_path('framework/testing/uploads-'.Str::random(8));
    File::ensureDirectoryExists($root);
    config(['site.upload_path' => $root, 'filesystems.disks.uploads.root' => $root]);
    foreach (['product', 'news', 'photo', 'seopage', 'video', 'file'] as $folder) {
        config(['filesystems.disks.upload_'.$folder.'.root' => $root.'/'.$folder]);
    }

    return $root;
}

function uploader(array $permissions = []): User
{
    return User::query()->create(['name' => 'Thử upload', 'username' => 'up-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => $permissions === [] ? User::ROLE_OWNER : User::ROLE_EDITOR, 'permissions' => $permissions, 'is_active' => true]);
}

it('uploads a banner image that the site then serves', function () {
    $root = uploadRoot();
    try {
        $this->actingAs(uploader(['media']), 'admin');
        $placement = array_key_first(BannerResource::PLACEMENTS);
        Livewire::test(ManageBanners::class)->callAction('create', data: [
            'placement' => $placement, 'title' => 'Banner thử upload', 'image' => [UploadedFile::fake()->image('Banner Mùa Hè.jpg', 1200, 400)], 'is_visible' => false,
        ])->assertHasNoActionErrors();

        $file = (string) DB::table('banners')->where('title', 'Banner thử upload')->value('image');
        expect($file)->toMatch('/^banner-mua-he-[a-z0-9]{6}\.jpg$/');
        expect(File::exists($root.'/photo/'.$file))->toBeTrue();
        $this->get('/upload/photo/'.$file)->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        Livewire::test(ManageBanners::class)->callAction('create', data: [
            'placement' => $placement, 'title' => 'Không phải ảnh', 'image' => [UploadedFile::fake()->create('trang.html', 2, 'text/html')],
        ])->assertHasActionErrors(['image']);
    } finally {
        File::deleteDirectory($root);
    }
});

it('stores a picture inserted in the editor when the article is saved, and refuses other files', function () {
    $root = uploadRoot();
    $doc = fn (string $id) => ['type' => 'doc', 'content' => [
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Sơ đồ đấu nối']]],
        ['type' => 'image', 'attrs' => ['id' => $id, 'src' => 'blob:local', 'alt' => 'Sơ đồ']],
    ]];
    try {
        $this->actingAs(uploader(), 'admin');
        $slug = 'bai-thu-anh-'.bin2hex(random_bytes(3));
        Livewire::test(CreateArticle::class)
            ->set('componentFileAttachments.data.body.k1', UploadedFile::fake()->image('so-do.png', 300, 200))
            ->fillForm(['title' => 'Bài thử ảnh trong editor', 'slug' => $slug, 'body' => $doc('k1')])
            ->call('create')->assertHasNoFormErrors();

        $body = (string) DB::table('posts')->where('slug', $slug)->value('body');
        expect($body)->toMatch('#<img[^>]+src="/upload/filemanager/editor/[^"]+\.png"#');
        preg_match('#/upload/(filemanager/editor/[^"]+)#', $body, $m);
        expect(File::exists($root.'/'.$m[1]))->toBeTrue();
        $this->get('/upload/'.$m[1])->assertOk()->assertHeader('Content-Type', 'image/png');
        $id = (int) DB::table('posts')->where('slug', $slug)->value('id');
        Livewire::test(EditArticle::class, ['record' => $id])->call('save')->assertHasNoFormErrors();
        expect((string) DB::table('posts')->where('id', $id)->value('body'))->toBe($body);

        Livewire::test(CreateArticle::class)
            ->set('componentFileAttachments.data.body.k2', UploadedFile::fake()->create('ma-doc.svg', 2, 'image/svg+xml'))
            ->fillForm(['title' => 'Bài thử file lạ', 'slug' => $slug.'-2', 'body' => $doc('k2')])
            ->call('create')->assertHasFormErrors(['body']);
        expect(glob($root.'/filemanager/editor/*'))->toHaveCount(1)->and(DB::table('posts')->where('slug', $slug.'-2')->exists())->toBeFalse();
    } finally {
        File::deleteDirectory($root);
    }
});

it('shows and hides ticked products only for people with the bulk permission', function () {
    $products = Product::query()->where('is_visible', true)->orderBy('id')->limit(2)->get();
    $this->actingAs(uploader(), 'admin');
    Livewire::test(ListProducts::class)->callTableBulkAction('hide', $products);
    expect($products->map(fn ($p) => $p->fresh()->is_visible)->unique()->all())->toBe([false]);
    Livewire::test(ListProducts::class)->callTableBulkAction('show', $products);
    expect($products->map(fn ($p) => $p->fresh()->is_visible)->unique()->all())->toBe([true]);

    $this->flushSession();
    $this->actingAs(uploader(['catalog']), 'admin');
    Livewire::test(ListProducts::class)->assertTableBulkActionHidden('hide')->assertTableBulkActionHidden('show');
});
