<?php

namespace App\Domain\Media;

use App\Domain\Pages\PageSupport;
use App\Models\Album;
use App\Support\Text;

/**
 * Photo albums and videos (api/pts_v1_gallery.php, pts_v1_gallery_detail.php, pts_v1_video.php).
 * Albums with their pictures; videos are YouTube links only.
 */
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
        $where = "is_visible=1 and slug<>''";
        $total = max(0, (int) ($this->row("select count(*) as num from albums where {$where}")['num'] ?? 0));
        $albums = [];
        foreach ($this->rows("select id, title as namevi, slug as slugvi, coalesce(image,'') as photo, coalesce(summary,'') as descvi, 'hienthi' as status from albums where {$where} order by sort_order,id desc limit ".(($page - 1) * 20).',20') as $row) {
            if ($dto = $this->albumDto($row)) {
                $albums[] = $dto;
            }
        }
        $seo = $this->row("select coalesce(title,'') as titlevi, coalesce(description,'') as descriptionvi, coalesce(keywords,'') as keywordsvi, coalesce(image,'') as photo from seo_metas where page='thu-vien-anh' limit 1");

        return [200, ['schemaVersion' => 1, 'locale' => 'vi', 'path' => '/thu-vien-anh/', 'title' => 'Thư viện ảnh',
            'seo' => self::seo($seo, 'Thư viện ảnh', 'Thư viện ảnh', '/thu-vien-anh/', $this->urls->visual($seo['photo'] ?? '', 'upload/seopage/')),
            'albums' => $albums, 'pagination' => ['page' => $page, 'perPage' => 20, 'total' => $total]]];
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function albumPage(array $get): array
    {
        if (count($get) !== 1 || ! isset($get['slug']) || ! is_string($get['slug']) || ! preg_match(self::SLUG, $slug = trim($get['slug'], '/'))) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $row = $this->row("select id, title as namevi, slug as slugvi, coalesce(image,'') as photo, coalesce(summary,'') as descvi, 'hienthi' as status from albums where slug=? and is_visible=1 limit 1", [$slug]);
        $album = $row ? $this->albumDto($row) : null;
        if ($album === null) {
            return [404, ['error' => ['code' => 'GALLERY_NOT_FOUND']]];
        }
        $images = [];
        foreach ($this->rows("select id, path as photo, coalesce(caption,'') as namevi, 'hienthi' as status from album_images where album_id=? and is_visible=1 order by sort_order,id desc", [(int) $row['id']]) as $image) {
            $url = $this->productImage($image['photo']);
            if (self::visible($image) && $url !== '') {
                $images[] = ['id' => max(0, (int) $image['id']), 'image' => $url, 'caption' => Text::cms($image['namevi'])];
            }
            if (count($images) >= 120) {
                break;
            }
        }
        $seo = $this->row("select coalesce(title,'') as titlevi, coalesce(description,'') as descriptionvi, coalesce(keywords,'') as keywordsvi from seo_metas where owner_type=? and owner_id=? limit 1", [Album::class, (int) $row['id']]);

        return [200, ['schemaVersion' => 1, 'locale' => 'vi', 'album' => $album, 'images' => $images,
            'seo' => self::seo($seo, $album['title'], $album['description'], '/'.$album['slug'].'/')]];
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function videos(array $get): array
    {
        $page = $this->page($get);
        if ($page === null) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $videos = [];
        foreach ($this->rows("select id, title as namevi, url as link_video, 'hienthi' as status from videos where is_visible=1 order by sort_order,id desc") as $row) {
            $title = Text::cms($row['namevi']);
            $youtube = self::youtubeId($row['link_video']);
            if (self::visible($row) && (int) $row['id'] > 0 && $title !== '' && $youtube !== '') {
                $videos[] = ['id' => (int) $row['id'], 'title' => $title, 'youtubeId' => $youtube, 'embedUrl' => 'https://www.youtube-nocookie.com/embed/'.$youtube, 'thumbnail' => 'https://img.youtube.com/vi/'.$youtube.'/0.jpg'];
            }
        }
        $seo = $this->row("select coalesce(title,'') as titlevi, coalesce(description,'') as descriptionvi, coalesce(keywords,'') as keywordsvi, coalesce(image,'') as photo from seo_metas where page='video' limit 1");

        return [200, ['schemaVersion' => 1, 'locale' => 'vi', 'path' => '/video/', 'title' => 'Video',
            'seo' => self::seo($seo, 'Video', 'Video', '/video/', $this->urls->visual($seo['photo'] ?? '', 'upload/seopage/')),
            'videos' => array_slice($videos, ($page - 1) * 10, 10), 'pagination' => ['page' => $page, 'perPage' => 10, 'total' => count($videos)]]];
    }

    private function albumDto(array $row): ?array
    {
        $id = (int) $row['id'];
        $title = Text::cms($row['namevi']);
        $slug = Text::slug($row['slugvi']);
        if (! self::visible($row) || $id <= 0 || $title === '' || $slug === '') {
            return null;
        }

        return ['id' => $id, 'title' => $title, 'slug' => $slug, 'url' => '/'.$slug.'/', 'description' => Text::cms($row['descvi']), 'image' => $this->productImage($row['photo'])];
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

    private static function seo(array $row, string $title, string $description, string $canonical, ?string $image = null): array
    {
        $seo = ['title' => Text::cms($row['titlevi'] ?? '') ?: Text::cms($title), 'description' => Text::cms($row['descriptionvi'] ?? '') ?: Text::cms($description),
            'keywords' => Text::cms($row['keywordsvi'] ?? ''), 'canonical' => $canonical];
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
