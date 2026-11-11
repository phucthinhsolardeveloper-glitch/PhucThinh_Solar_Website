<?php

namespace App\Domain\News;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Content\PostVisibility;
use App\Domain\Pages\PageSupport;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Product;
use App\Support\RichText;
use App\Support\SiteSettings;
use App\Support\Text;

/** Knowledge articles and support/policy pages. Body HTML goes through the RichText sanitiser. */
class NewsDetail extends PageSupport
{
    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const ARTICLE_FIELDS = ['id', 'category_id', 'author_id', 'title', 'slug', 'image', 'summary', 'body', 'extra_body', 'published_at', 'updated_at'];

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
        $row = $this->db->table('posts')->where('slug', $slug)->where('kind', 'article')->where(PostVisibility::live())->first(self::ARTICLE_FIELDS);

        return $row ? $this->renderArticle((array) $row) : $notFound;
    }

    /** The signed preview renders the draft through the same presenter as the public article. */
    public function preview(PostRevision $revision): array
    {
        $post = $revision->post;
        if (! $post || $post->kind !== 'article') {
            return [404, ['error' => ['code' => 'ARTICLE_NOT_FOUND']]];
        }
        $payload = $revision->payload;
        $row = [
            'id' => $post->id, 'category_id' => $payload['category_id'] ?? $post->category_id,
            'author_id' => $post->author_id, 'title' => $payload['title'] ?? $post->title,
            'slug' => $payload['slug'] ?? $post->slug, 'image' => $payload['image'] ?? $post->image,
            'summary' => $payload['summary'] ?? '', 'body' => $payload['body'] ?? '',
            'extra_body' => $payload['extra_body'] ?? '',
            'published_at' => $payload['published_at'] ?? $post->published_at,
            'updated_at' => $revision->updated_at?->toDateTimeString() ?? now()->toDateTimeString(),
        ];
        $seo = $payload['seo'] ?? [];

        return $this->renderArticle($row, [
            'title' => (string) ($seo['title'] ?? ''), 'description' => (string) ($seo['description'] ?? ''),
            'keywords' => (string) ($seo['keywords'] ?? ''),
        ], $payload['products'] ?? null);
    }

    private function renderArticle(array $row, ?array $seoOverride = null, ?array $draftProductIds = null): array
    {
        $notFound = [404, ['error' => ['code' => 'ARTICLE_NOT_FOUND']]];
        $id = (int) $row['id'];
        $title = self::text($row['title']);
        $articleSlug = Text::slug($row['slug']);
        if ($title === '' || $articleSlug === '') {
            return $notFound;
        }
        $categoryId = (int) $row['category_id'];
        $category = [];
        $list = $categoryId > 0 ? $this->db->table('post_categories')->where('id', $categoryId)->where('kind', 'article')->where('is_visible', true)->first(['id', 'name', 'slug']) : null;
        if ($list && ($listSlug = Text::slug($list->slug)) !== '') {
            $category = ['id' => (int) $list->id, 'name' => self::text($list->name), 'slug' => $listSlug, 'url' => '/'.$listSlug.'/'];
        }
        $media = ['gallery' => [], 'videos' => []];
        $items = $this->db->table('post_media')->where('post_id', $id)->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')->get();
        foreach ($items->where('kind', 'image') as $photo) {
            if (($image = NewsListing::media((string) $photo->path)) !== '') {
                $media['gallery'][] = ['image' => $image, 'alt' => self::text($photo->caption)];
            }
        }
        foreach ($items->where('kind', 'video') as $video) {
            if (($url = self::videoUrl($video->video_url)) !== '') {
                $media['videos'][] = ['url' => $url, 'title' => self::text($video->caption)];
            }
        }
        $related = $this->siblings($id, 'article', $row['category_id']);
        $seo = $seoOverride ?? $this->seoOf($id);
        $seoTitle = self::text($seo['title']) ?: $title;
        if (stripos($seoTitle, 'phúc thịnh s') === false) {
            $seoTitle .= ' | Phúc Thịnh Solar';
        }
        $description = self::text($row['summary']);
        $cover = NewsListing::media($this->photo($row['image']));
        $quote = RichText::sanitize((string) $row['extra_body']);
        $published = self::date($row['published_at']);
        $linkedRows = $draftProductIds === null
            ? $this->rows("select p.id, p.name, p.slug, coalesce(p.image,'') as image from post_products pp inner join products p on p.id=pp.product_id"
                .' where pp.post_id=? and pp.is_visible=1 and p.is_visible=1 order by pp.sort_order,pp.id limit 4', [$id])
            : Product::query()->whereKey($draftProductIds)->where('is_visible', true)->get(['id', 'name', 'slug', 'image'])->toArray();
        if ($draftProductIds !== null) {
            $positions = array_flip(array_map('intval', $draftProductIds));
            usort($linkedRows, fn (array $a, array $b) => ($positions[(int) $a['id']] ?? 999) <=> ($positions[(int) $b['id']] ?? 999));
        }
        $linkedProducts = [];
        foreach (array_slice($linkedRows, 0, 4) as $product) {
            $productSlug = Text::slug($product['slug']);
            $image = $this->urls->product($product['image']);
            if ($productSlug !== '' && $image !== '') {
                $linkedProducts[] = ['id' => (int) $product['id'], 'name' => self::text($product['name']),
                    'url' => '/san-pham/'.$productSlug, 'image' => $image];
            }
        }

        $article = [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'id' => $id,
            'title' => $title,
            'slug' => $articleSlug,
            'canonical' => '/'.$articleSlug.'/',
            'date' => $published,
            'datePublished' => $published,
            'dateModified' => self::date(strtotime((string) $row['updated_at']) ?: $row['published_at']),
            'author' => ['name' => $this->author($row['author_id'])],
            'description' => $description,
            'cover' => $cover,
            'showCover' => $cover !== '' && ! self::contentRepeatsCover($row['body'], (string) $row['image']),
            'content' => RichText::dto(RichText::sanitize((string) $row['body'])),
            'quote' => $quote !== '' ? RichText::dto($quote) : null,
            'category' => $category,
            'media' => $media,
            'seo' => ['title' => $seoTitle, 'description' => self::text($seo['description']) ?: $description, 'keywords' => self::text($seo['keywords']), 'canonical' => '/'.$articleSlug],
        ];
        if ($linkedProducts !== []) {
            $article['linkedProducts'] = $linkedProducts;
        }

        return [200, [
            'article' => $article,
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
        $row = $this->db->table('posts')->where('slug', $slug)->where('kind', 'policy')->where(PostVisibility::live())->first(self::ARTICLE_FIELDS);
        $plain = fn ($v) => trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode(is_scalar($v) ? (string) $v : '', ENT_QUOTES, 'UTF-8'))));
        if (! $row || ($title = $plain($row->title)) === '') {
            return $notFound;
        }
        $description = $plain($row->summary);
        $related = $this->siblings((int) $row->id, 'policy', $row->category_id, withAuthor: false);
        $seo = $this->seoOf((int) $row->id);

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'id' => (int) $row->id,
            'title' => $title,
            'slug' => $slug,
            'canonical' => '/ho-tro/'.$slug.'/',
            'description' => $description,
            'cover' => NewsListing::media($this->photo($row->image)),
            'date' => self::date($row->published_at),
            'author' => ['name' => $this->author($row->author_id)],
            'content' => RichText::dto(RichText::sanitize((string) $row->body)),
            'related' => $this->related($related),
            'seo' => [
                'title' => $plain($seo['title']) ?: $title,
                'description' => $plain($seo['description']) ?: $description,
                'keywords' => $plain($seo['keywords']),
                'canonical' => '/ho-tro/'.$slug.'/',
            ],
        ]];
    }

    /** Up to 12 other published posts of the same kind and group, in Admin order. */
    private function siblings(int $id, string $kind, mixed $categoryId, bool $withAuthor = true): array
    {
        return $this->db->table('posts as n')->where('n.id', '<>', $id)->where('n.kind', $kind)->where(PostVisibility::live('n.'))->where('n.slug', '<>', '')
            ->when($categoryId, fn ($q) => $q->where('n.category_id', $categoryId), fn ($q) => $q->whereNull('n.category_id'))
            ->when($withAuthor, fn ($q) => $q->leftJoin('users as u', 'u.id', '=', 'n.author_id')->addSelect('u.name as author'))
            ->addSelect(['n.id', 'n.title', 'n.slug', 'n.image', 'n.summary', 'n.published_at'])
            ->orderBy('n.sort_order')->orderByDesc('n.id')->limit(12)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function related(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $slug = Text::slug($row['slug']);
            $title = self::text($row['title']);
            if ($slug === '' || $title === '') {
                continue;
            }
            $out[] = ['id' => (int) $row['id'], 'title' => $title, 'slug' => $slug, 'canonical' => '/'.$slug.'/',
                'date' => self::date($row['published_at']),
                'author' => self::authorName($row['author'] ?? ''), 'description' => self::text($row['summary']), 'image' => NewsListing::media($this->photo($row['image']))];
        }

        return $out;
    }

    /** Admin "news-cta": hidden on purpose returns null; never configured falls back to the standard invitation. */
    private function cta(): ?array
    {
        $rows = $this->section('news.cta');
        $cta = collect($rows)->firstWhere('is_visible', true) ?? [];
        if ($rows && ! $cta) {
            return null;
        }
        $hotline = is_scalar($this->settings->options()['hotline'] ?? null) ? trim((string) $this->settings->options()['hotline']) : '';
        $tel = (string) preg_replace('/[^0-9+]/', '', $hotline);

        return [
            'title' => self::text($cta['title'] ?? '') ?: 'Đội ngũ Phúc Thịnh Solar luôn sẵn sàng hỗ trợ',
            'description' => self::text($cta['summary'] ?? '') ?: 'Liên hệ ngay để được tư vấn và cập nhật những thông tin mới nhất.',
            'buttonLabel' => self::text($cta['extra'] ?? '') ?: 'Nhận tư vấn ngay',
            'image' => NewsListing::media($this->photo($cta['image'] ?? '')),
            'hotline' => $hotline !== '' && $tel !== '' ? ['label' => 'Gọi ngay hotline', 'text' => self::text($hotline), 'tel' => $tel] : null,
        ];
    }

    /** @return array{title: string, description: string, keywords: string} SEO fields of a post */
    private function seoOf(int $postId): array
    {
        $seo = $this->db->table('seo_metas')->where('owner_type', Post::class)->where('owner_id', $postId)->first(['title', 'description', 'keywords']);

        return ['title' => (string) $seo?->title, 'description' => (string) $seo?->description, 'keywords' => (string) $seo?->keywords];
    }

    private function author(mixed $userId): string
    {
        return self::authorName((int) $userId > 0 ? (string) $this->db->table('users')->where('id', (int) $userId)->value('name') : '');
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

    /** Article text keeping line breaks from block tags. */
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
        $timestamp = is_numeric($value) ? (int) $value : (int) strtotime((string) $value);

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
