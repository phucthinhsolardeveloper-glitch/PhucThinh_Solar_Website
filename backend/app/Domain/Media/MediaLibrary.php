<?php

namespace App\Domain\Media;

use App\Domain\Pages\PageSupport;
use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\SeoMeta;
use App\Models\Video;
use App\Support\Text;

/** Photo albums with their pictures, and YouTube videos. */
class MediaLibrary extends PageSupport
{
    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** @return array{0: int, 1: array<string, mixed>} */
    public function albums(array $get): array
    {
        $page = $this->page($get);
        if ($page === null) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $listed = Album::query()->where('is_visible', true)->where('slug', '<>', '');
        $total = (clone $listed)->count();
        $albums = $listed->orderBy('sort_order')->orderByDesc('id')->forPage($page, 20)->get()
            ->map(fn (Album $album) => $this->albumDto($album))->filter()->values()->all();
        $seo = SeoMeta::query()->where('page', 'thu-vien-anh')->first();

        return [200, ['schemaVersion' => 1, 'locale' => 'vi', 'path' => '/thu-vien-anh/', 'title' => 'Thư viện ảnh',
            'seo' => self::seo($seo, 'Thư viện ảnh', 'Thư viện ảnh', '/thu-vien-anh/', $this->urls->visual((string) $seo?->image, 'upload/seopage/')),
            'albums' => $albums, 'pagination' => ['page' => $page, 'perPage' => 20, 'total' => $total]]];
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function albumPage(array $get): array
    {
        if (count($get) !== 1 || ! isset($get['slug']) || ! is_string($get['slug']) || ! preg_match(self::SLUG, $slug = trim($get['slug'], '/'))) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $model = Album::query()->where('slug', $slug)->where('is_visible', true)->first();
        $album = $model ? $this->albumDto($model) : null;
        if ($album === null) {
            return [404, ['error' => ['code' => 'GALLERY_NOT_FOUND']]];
        }
        $images = $model->images()->where('is_visible', true)->get()
            ->map(fn (AlbumImage $image) => ['id' => (int) $image->id, 'image' => $this->productImage($image->path), 'caption' => Text::cms((string) $image->caption)])
            ->filter(fn ($image) => $image['image'] !== '')->take(120)->values()->all();

        return [200, ['schemaVersion' => 1, 'locale' => 'vi', 'album' => $album, 'images' => $images,
            'seo' => self::seo($model->seo, $album['title'], $album['description'], '/'.$album['slug'].'/')]];
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function videos(array $get): array
    {
        $page = $this->page($get);
        if ($page === null) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $videos = [];
        foreach (Video::query()->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')->get() as $video) {
            $title = Text::cms($video->title);
            $youtube = self::youtubeId($video->url);
            if ($title !== '' && $youtube !== '') {
                $videos[] = ['id' => (int) $video->id, 'title' => $title, 'youtubeId' => $youtube, 'embedUrl' => 'https://www.youtube-nocookie.com/embed/'.$youtube, 'thumbnail' => 'https://img.youtube.com/vi/'.$youtube.'/0.jpg'];
            }
        }
        $seo = SeoMeta::query()->where('page', 'video')->first();

        return [200, ['schemaVersion' => 1, 'locale' => 'vi', 'path' => '/video/', 'title' => 'Video',
            'seo' => self::seo($seo, 'Video', 'Video', '/video/', $this->urls->visual((string) $seo?->image, 'upload/seopage/')),
            'videos' => array_slice($videos, ($page - 1) * 10, 10), 'pagination' => ['page' => $page, 'perPage' => 10, 'total' => count($videos)]]];
    }

    private function albumDto(Album $album): ?array
    {
        $title = Text::cms($album->title);
        $slug = Text::slug($album->slug);
        if ($title === '' || $slug === '') {
            return null;
        }

        return ['id' => (int) $album->id, 'title' => $title, 'slug' => $slug, 'url' => '/'.$slug.'/', 'description' => Text::cms((string) $album->summary), 'image' => $this->productImage($album->image)];
    }

    private function productImage(mixed $file): string
    {
        $file = is_scalar($file) ? trim((string) $file) : '';
        if ($file === '' || basename($file) !== $file || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $file)) {
            return '';
        }

        return $this->urls->exists('upload/product/'.$file) ? '/upload/product/'.$file : '';
    }

    private function page(array $get): ?int
    {
        if (array_diff(array_keys($get), ['p']) || (isset($get['p']) && (! is_string($get['p']) || ! preg_match('/^[1-9][0-9]*$/', $get['p'])))) {
            return null;
        }
        $page = (int) ($get['p'] ?? 1);

        return $page > 1000000 ? null : $page;
    }

    private static function seo(?SeoMeta $row, string $title, string $description, string $canonical, ?string $image = null): array
    {
        $seo = ['title' => Text::cms((string) $row?->title) ?: Text::cms($title), 'description' => Text::cms((string) $row?->description) ?: Text::cms($description),
            'keywords' => Text::cms((string) $row?->keywords), 'canonical' => $canonical];
        if ($image !== null) {
            $seo['image'] = $image;
        }

        return $seo;
    }

    /** The 11-character id of a YouTube watch, short, embed or shorts link. */
    public static function youtubeId(mixed $value, bool $requireScheme = true): string
    {
        $raw = is_scalar($value) ? trim(htmlspecialchars_decode((string) $value, ENT_QUOTES)) : '';
        $parts = $raw === '' ? false : parse_url($raw);
        if (! is_array($parts) || empty($parts['host']) || ($requireScheme && (empty($parts['scheme']) || ! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)))) {
            return '';
        }
        $host = strtolower((string) $parts['host']);
        $id = '';
        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $id = trim((string) ($parts['path'] ?? ''), '/');
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            if (! empty($parts['query'])) {
                parse_str((string) $parts['query'], $query);
                if (isset($query['v']) && is_scalar($query['v'])) {
                    $id = (string) $query['v'];
                }
            }
            if ($id === '' && ! empty($parts['path']) && preg_match('~/(?:embed|shorts)/([A-Za-z0-9_-]{11})(?:/|$)~', (string) $parts['path'], $match)) {
                $id = $match[1];
            }
        }

        return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : '';
    }
}
