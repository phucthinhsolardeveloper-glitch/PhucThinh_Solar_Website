<?php

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Models\Post;
use App\Support\RichText;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

it('imports bundled knowledge articles as hidden drafts, preserves edits and is repeatable', function () {
    Storage::fake('upload_news');
    $command = ['--apply' => true];
    expect(Artisan::call('pts:editorial-batch', $command))->toBe(0);

    $slugs = [
        'dien-mat-troi-hoat-dong-nhu-the-nao',
        'cach-doc-datasheet-tam-pin-nang-luong-mat-troi',
        'thi-truong-dien-mat-troi-toan-cau-2026-iea-pvps',
    ];
    foreach ($slugs as $slug) {
        $post = Post::query()->where('kind', 'article')->where('slug', $slug)->sole();
        expect($post->is_visible)->toBeFalse()
            ->and($post->category?->kind)->toBe('article')
            ->and(mb_strlen(strip_tags((string) $post->body)))->toBeGreaterThan(900)
            ->and($post->seo?->title)->not->toBeEmpty()
            ->and(Storage::disk('upload_news')->exists($post->image))->toBeTrue();
        $source = (array) collect(json_decode(file_get_contents(base_path('resources/editorial/2026-11-11/manifest.json')), true)['articles'])
            ->firstWhere('slug', $slug);
        foreach ($source['inline_images'] as $image) {
            expect(Storage::disk('upload_news')->exists($image))->toBeTrue()
                ->and(str_contains($post->body, '/upload/news/'.$image))->toBeTrue()
                ->and(str_contains(RichText::sanitize((string) $post->body), '/upload/news/'.$image))->toBeTrue();
        }
        $this->getJson('/api/v1/news-detail?slug='.$slug)->assertNotFound();
    }

    $first = Post::query()->where('slug', $slugs[0])->sole();
    $first->update(['title' => 'Bản đã chỉnh tay trong CMS', 'body' => '<p>Nội dung đã sửa thủ công</p>']);
    expect(Artisan::call('pts:editorial-batch', $command + ['--refresh-unchanged-drafts' => true]))->toBe(0);
    expect(Post::query()->whereIn('slug', $slugs)->count())->toBe(3)
        ->and($first->fresh()->title)->toBe('Bản đã chỉnh tay trong CMS')
        ->and($first->fresh()->body)->toBe('<p>Nội dung đã sửa thủ công</p>');
});

it('serves source-controlled editorial photos when the legacy upload volume has no copy', function () {
    $urls = app(ProductMediaUrls::class);
    $path = 'upload/news/pts-solar-how-it-works.webp';
    expect($urls->exists($path))->toBeTrue()
        ->and($urls->absolute($path))->toContain('images/editorial/');
    $this->get('/'.$path)->assertOk()->assertHeader('Content-Type', 'image/webp');
});
