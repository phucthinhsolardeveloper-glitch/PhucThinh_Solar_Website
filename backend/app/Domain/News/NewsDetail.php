<?php

namespace App\Domain\News;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Pages\PageSupport;
use App\Legacy\Content\RichText;
use App\Legacy\Support\LegacyText;
use App\Models\Post;
use App\Support\SiteSettings;

/**
 * Article pages: knowledge articles (api/pts_v1_news_detail.php) and support/policy pages
 * (api/pts_v1_policy_detail.php). Body HTML goes through the RichText sanitiser.
 */
class NewsDetail extends PageSupport
{
    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(ProductMediaUrls $urls, private readonly SiteSettings $settings)
    {
        parent::__construct($urls);
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function article(array $get): array
    {
        $slug = isset($get['slug']) && is_string($get['slug']) ? trim($get['slug'], " /\t\n\r\0\x0B") : '';
        if (! preg_match(self::SLUG, $slug)) {
            return [400, ['error' => ['code' => 'BAD_SLUG']]];
        }
        if (array_diff(array_keys($get), ['slug'])) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $notFound = [404, ['error' => ['code' => 'ARTICLE_NOT_FOUND']]];
        $row = $this->row("select id, category_id as id_list, author_id as id_user, title as namevi, slug as slugvi, coalesce(image,'') as photo, coalesce(summary,'') as descvi, coalesce(body,'') as contentvi, coalesce(extra_body,'') as content1vi, published_at as date_created, updated_at, 'hienthi' as status, 'tin-tuc' as type from posts where slug=? and kind='article' and is_visible=1 limit 1", [$slug]);
        if (! $row) {
            return $notFound;
        }
        $id = (int) $row['id'];
        $title = self::text($row['namevi']);
        $articleSlug = LegacyText::slug($row['slugvi']);
        if (! self::visible($row) || $id <= 0 || $title === '' || $articleSlug === '') {
            return $notFound;
        }
        $category = [];
        if ((int) $row['id_list'] > 0) {
            $list = $this->row("select id, name as namevi, coalesce(summary,'') as descvi, slug as slugvi, 'hienthi' as status from post_categories where id=? and kind='article' and is_visible=1 limit 1", [(int) $row['id_list']]);
            if ($list && ($listSlug = LegacyText::slug($list['slugvi'])) !== '') {
                $category = ['id' => (int) $list['id'], 'name' => self::text($list['namevi']), 'slug' => $listSlug, 'url' => '/'.$listSlug.'/'];
            }
        }
        $media = ['gallery' => [], 'videos' => []];
        foreach ($this->rows("select coalesce(path,'') as photo, coalesce(caption,'') as namevi from post_media where post_id=? and kind='image' and is_visible=1 order by sort_order,id desc", [$id]) as $photo) {
            if (($image = NewsListing::media($photo['photo'])) !== '') {
                $media['gallery'][] = ['image' => $image, 'alt' => self::text($photo['namevi'])];
            }
        }
        foreach ($this->rows("select coalesce(caption,'') as namevi, coalesce(video_url,'') as link_video from post_media where post_id=? and kind='video' and is_visible=1 order by sort_order,id desc", [$id]) as $video) {
            if (($url = self::videoUrl($video['link_video'])) !== '') {
                $media['videos'][] = ['url' => $url, 'title' => self::text($video['namevi'])];
            }
        }
        $related = $this->rows("select n.id, n.title as namevi, n.slug as slugvi, coalesce(n.image,'') as photo, coalesce(n.summary,'') as descvi, n.published_at as date_created, 'hienthi' as status, u.name as author"
            ." from posts n left join users u on u.id=n.author_id where n.id<>? and n.category_id <=> ? and n.kind='article' and n.is_visible=1 and n.slug<>'' order by n.sort_order,n.id desc limit 12", [$id, $row['id_list']]);
        $seo = $this->seoOf($id);
        $seoTitle = self::text($seo['titlevi'] ?? '') ?: $title;
        if (stripos($seoTitle, 'phúc thịnh s') === false) {
            $seoTitle .= ' | Phúc Thịnh Solar';
        }
        $description = self::text($row['descvi']);
        $cover = NewsListing::media($this->photo($row['photo']));
        $quote = RichText::sanitize($row['content1vi']);
        $published = self::date($row['date_created']);

        return [200, [
            'article' => [
                'schemaVersion' => 1,
                'locale' => 'vi',
                'id' => $id,
                'title' => $title,
                'slug' => $articleSlug,
                'canonical' => '/'.$articleSlug.'/',
                'date' => $published,
                'datePublished' => $published,
                'dateModified' => self::date(strtotime((string) $row['updated_at']) ?: $row['date_created']),
                'author' => ['name' => $this->author($row['id_user'])],
                'description' => $description,
                'cover' => $cover,
                'showCover' => $cover !== '' && ! self::contentRepeatsCover($row['contentvi'], (string) $row['photo']),
                'content' => RichText::dto(RichText::sanitize($row['contentvi'])),
                'quote' => $quote !== '' ? RichText::dto($quote) : null,
                'category' => $category,
                'media' => $media,
                'seo' => ['title' => $seoTitle, 'description' => self::text($seo['descriptionvi'] ?? '') ?: $description, 'keywords' => self::text($seo['keywordsvi'] ?? ''), 'canonical' => '/'.$articleSlug],
                'readOnly' => true,
                'legacyViewMutation' => false,
            ],
            'related' => $this->related($related),
            'cta' => $this->cta(),
        ]];
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function policy(array $get): array
    {
        $slug = isset($get['slug']) && is_string($get['slug']) ? trim($get['slug'], " /\t\n\r\0\x0B") : '';
        if (! preg_match(self::SLUG, $slug) || count($get) !== 1) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $notFound = [404, ['error' => ['code' => 'POLICY_NOT_FOUND']]];
        $row = $this->row("select id, category_id as id_list, author_id as id_user, title as namevi, slug as slugvi, coalesce(image,'') as photo, coalesce(summary,'') as descvi, coalesce(body,'') as contentvi, published_at as date_created, 'hienthi' as status from posts where slug=? and kind='policy' and is_visible=1 limit 1", [$slug]);
        $plain = fn ($v) => trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode(is_scalar($v) ? (string) $v : '', ENT_QUOTES, 'UTF-8'))));
        if (! $row || ($title = $plain($row['namevi'])) === '') {
            return $notFound;
        }
        $description = $plain($row['descvi'] ?? '');
        $related = $this->rows("select id, title as namevi, slug as slugvi, coalesce(image,'') as photo, coalesce(summary,'') as descvi, published_at as date_created, 'hienthi' as status from posts"
            ." where id<>? and category_id <=> ? and kind='policy' and is_visible=1 and slug<>'' order by sort_order,id desc limit 12", [(int) $row['id'], $row['id_list']]);
        $seo = $this->seoOf((int) $row['id']);

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'id' => (int) $row['id'],
            'title' => $title,
            'slug' => $slug,
            'canonical' => '/ho-tro/'.$slug.'/',
            'description' => $description,
            'cover' => NewsListing::media($this->photo($row['photo'])),
            'date' => ! empty($row['date_created']) ? date('Y-m-d', (int) $row['date_created']) : '',
            'author' => ['name' => $this->author($row['id_user'])],
            'content' => RichText::dto(RichText::sanitize($row['contentvi'] ?? '')),
            'related' => $this->related($related),
            'seo' => [
                'title' => $plain($seo['titlevi'] ?? '') ?: $title,
                'description' => $plain($seo['descriptionvi'] ?? '') ?: $description,
                'keywords' => $plain($seo['keywordsvi'] ?? ''),
                'canonical' => '/ho-tro/'.$slug.'/',
            ],
        ]];
    }

    private function related(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $slug = LegacyText::slug($row['slugvi']);
            $title = self::text($row['namevi']);
            if (! self::visible($row) || (int) $row['id'] <= 0 || $slug === '' || $title === '') {
                continue;
            }
            $out[] = ['id' => (int) $row['id'], 'title' => $title, 'slug' => $slug, 'canonical' => '/'.$slug.'/',
                'date' => ! empty($row['date_created']) ? date('Y-m-d', (int) $row['date_created']) : '',
                'author' => self::authorName($row['author'] ?? ''), 'description' => self::text($row['descvi']), 'image' => NewsListing::media($this->photo($row['photo']))];
        }

        return $out;
    }

    /** Admin "news-cta": hidden on purpose returns null; never configured falls back to the standard invitation. */
    private function cta(): ?array
    {
        $rows = $this->items('news.cta');
        $cta = [];
        foreach ($rows as $row) {
            if (self::visible($row)) {
                $cta = $row;
                break;
            }
        }
        if ($rows && ! $cta) {
            return null;
        }
        $hotline = is_scalar($this->settings->options()['hotline'] ?? null) ? trim((string) $this->settings->options()['hotline']) : '';
        $tel = (string) preg_replace('/[^0-9+]/', '', $hotline);

        return [
            'title' => self::text($cta['namevi'] ?? '') ?: 'Đội ngũ Phúc Thịnh Solar luôn sẵn sàng hỗ trợ',
            'description' => self::text($cta['descvi'] ?? '') ?: 'Liên hệ ngay để được tư vấn và cập nhật những thông tin mới nhất.',
            'buttonLabel' => self::text($cta['content1vi'] ?? '') ?: 'Nhận tư vấn ngay',
            'image' => NewsListing::media($this->photo($cta['photo'] ?? '')),
            'hotline' => $hotline !== '' && $tel !== '' ? ['label' => 'Gọi ngay hotline', 'text' => self::text($hotline), 'tel' => $tel] : null,
        ];
    }

    /** @return array<string, string> SEO fields of a post */
    private function seoOf(int $postId): array
    {
        return $this->row("select coalesce(title,'') as titlevi, coalesce(description,'') as descriptionvi, coalesce(keywords,'') as keywordsvi from seo_metas where owner_type=? and owner_id=? limit 1", [Post::class, $postId]);
    }

    private function author(mixed $userId): string
    {
        $name = (int) $userId > 0 ? ($this->row('select name as fullname from users where id=? limit 1', [(int) $userId])['fullname'] ?? '') : '';

        return self::authorName($name);
    }

    private static function authorName(mixed $value): string
    {
        return self::text($value) ?: 'Phúc Thịnh Solar';
    }

    /** The photo name when the file exists in upload/news. */
    private function photo(mixed $photo): string
    {
        return is_scalar($photo) && (string) $photo !== '' && ! str_contains((string) $photo, '..') && $this->urls->exists('upload/news/'.$photo) ? (string) $photo : '';
    }

    /** Article text keeping line breaks from block tags (ptsNewsDetailText). */
    private static function text(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $value = (string) preg_replace('~<(?:br\s*/?|/p|/li|/h[1-6])>~i', "\n", html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'));

        return trim((string) preg_replace('/[ \t\r\f\v]+/u', ' ', strip_tags($value)));
    }

    private static function date(mixed $value): string
    {
        $timestamp = is_numeric($value) ? (int) $value : 0;

        return $timestamp > 0 ? date('Y-m-d', $timestamp) : '';
    }

    /** https YouTube or Vimeo links only. */
    private static function videoUrl(mixed $value): string
    {
        $url = is_scalar($value) ? trim((string) $value) : '';
        if ($url === '' || preg_match('/[\x00-\x1F<>"]/', $url) || str_contains($url, '\\')) {
            return '';
        }
        $parts = parse_url($url);
        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return '';
        }

        return in_array(strtolower($parts['host'] ?? ''), ['youtube.com', 'www.youtube.com', 'youtu.be', 'vimeo.com', 'www.vimeo.com'], true) ? $url : '';
    }

    /** The cover is hidden when the article body already starts with the same picture (resized copies included). */
    private static function contentRepeatsCover(mixed $html, string $cover): bool
    {
        $identity = self::imageIdentity($cover);
        if ($identity === '' || trim((string) $html) === '' || ! preg_match_all('~<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1~isu', (string) $html, $matches)) {
            return false;
        }

        return in_array($identity, array_map(fn ($src) => self::imageIdentity($src), $matches[2]), true);
    }

    private static function imageIdentity(string $source): string
    {
        $source = rawurldecode(html_entity_decode($source, ENT_QUOTES, 'UTF-8'));
        $path = parse_url($source, PHP_URL_PATH);
        $identity = strtolower(pathinfo(basename(is_string($path) ? $path : $source), PATHINFO_FILENAME));
        do {
            $previous = $identity;
            $identity = (string) preg_replace('/-(?:scaled|\d+x\d+|\d{4,8})$/i', '', $identity);
        } while ($identity !== $previous);

        return trim($identity, '-_ ');
    }
}
