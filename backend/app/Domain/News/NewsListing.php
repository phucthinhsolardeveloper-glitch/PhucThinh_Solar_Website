<?php

namespace App\Domain\News;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Content\PostVisibility;
use App\Domain\Pages\PageSupport;
use App\Models\PostCategory;
use App\Support\SiteSettings;
use App\Support\Text;

/**
 * Knowledge hub: six articles a page, newest first, optional search and category path
 * (list/cat/item/sub). Without filters the first page shows up to three featured articles
 * (those marked for the hub, else the top of the manual order) that are then left out of the list.
 */
class NewsListing extends PageSupport
{
    private const PER_PAGE = 6;

    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const FIELDS = ['id', 'category_id', 'title', 'slug', 'image', 'summary', 'body', 'published_at'];

    public function __construct(ProductMediaUrls $urls, private readonly SiteSettings $settings)
    {
        parent::__construct($urls);
    }

    /** @return array{0: int, 1: array<string, mixed>} HTTP status and body */
    public function handle(array $get): array
    {
        $bad = [400, ['error' => ['code' => 'BAD_QUERY']]];
        $allowed = ['q', 'p', 'category', 'category_type', 'category_path'];
        foreach ($get as $key => $value) {
            if (! in_array($key, $allowed, true) || ! is_string($value) || (in_array($key, ['p', 'category'], true) && trim($value) === '')) {
                return $bad;
            }
        }
        $query = isset($get['q']) ? mb_substr(trim($get['q']), 0, 120) : '';
        $page = isset($get['p']) ? (int) $get['p'] : 1;
        $slug = isset($get['category']) ? trim($get['category'], '/') : '';
        $type = $get['category_type'] ?? ($slug !== '' ? 'list' : '');
        $segments = [];
        if (isset($get['category_path'])) {
            if (! isset($get['category_type'])) {
                return $bad;
            }
            $segments = explode('/', trim($get['category_path'], '/'));
            if (in_array('', $segments, true) || count($segments) > 4 || ($slug !== '' && end($segments) !== $slug)) {
                return $bad;
            }
            $slug = (string) end($segments);
        } elseif ($slug !== '') {
            $segments = [$slug];
        }
        if (($type !== '' && ! in_array($type, ['list', 'cat', 'item', 'sub'], true)) || ($type !== '' && $slug === '')
            || (isset($get['p']) && ! preg_match('/^[1-9][0-9]*$/', $get['p'])) || $page > 1000000 || ($slug !== '' && ! preg_match(self::SLUG, $slug))) {
            return $bad;
        }
        foreach ($segments as $segment) {
            if (! preg_match(self::SLUG, $segment)) {
                return $bad;
            }
        }

        // Top groups that hold at least one published article.
        $categories = $this->db->table('post_categories as c')->where('c.kind', 'article')->where('c.depth', 1)->where('c.is_visible', true)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('posts as p')->whereColumn('p.category_id', 'c.id')->where('p.kind', 'article')->where(PostVisibility::live('p.'))->where('p.slug', '<>', ''))
            ->orderBy('c.sort_order')->orderByDesc('c.id')->get(['c.id', 'c.name', 'c.summary', 'c.slug', 'c.image'])->map(fn ($row) => (array) $row)->all();
        $selected = null;
        $field = null;
        if ($slug !== '') {
            $resolved = $this->resolve($type, $segments);
            if ($resolved === null) {
                return [404, ['error' => ['code' => 'CATEGORY_NOT_FOUND']]];
            }
            [$selected, $field] = $resolved;
            $seoRow = $this->db->table('seo_metas')->where('owner_type', PostCategory::class)->where('owner_id', $selected['id'])->first(['title', 'description', 'keywords']);
            $selectedMeta = ['type' => 'tin-tuc', 'level' => $type, 'path' => implode('/', $segments), 'seo' => [
                'title' => Text::flat((string) $seoRow?->title),
                'description' => Text::flat((string) $seoRow?->description),
                'keywords' => Text::flat((string) $seoRow?->keywords),
                'image' => $this->urls->visual((string) $selected['image'], 'upload/news/'),
            ]];
        }

        $published = fn () => $this->db->table('posts')->where('kind', 'article')->where(PostVisibility::live())->where('slug', '<>', '');
        $introRows = $this->section('news.intro');
        $intro = collect($introRows)->firstWhere('is_visible', true) ?? [];
        $heroEnabled = ! $introRows || in_array('hero', $intro['flags'] ?? [], true);
        $featuredRows = [];
        if ($slug === '' && $query === '' && $heroEnabled) {
            $hub = $this->db->table('posts')->where('kind', 'article')->where('in_hub', true)->exists();
            $featuredRows = $published()->when($hub, fn ($q) => $q->where('in_hub', true))
                ->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id')->limit(3)->get(self::FIELDS)->map(fn ($row) => (array) $row)->all();
        }
        $listed = $published()
            ->when($featuredRows, fn ($q) => $q->whereNotIn('id', array_column($featuredRows, 'id')))
            ->when($selected, fn ($q) => $q->whereIn('category_id', $field));
        if ($query !== '') {
            $pattern = '%'.strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $listed->where(fn ($q) => $q->whereRaw("title like ? escape '!'", [$pattern])->orWhereRaw("summary like ? escape '!'", [$pattern])->orWhereRaw("body like ? escape '!'", [$pattern]));
        }
        $total = (clone $listed)->count();
        $page = min($page, max(1, (int) ceil($total / self::PER_PAGE)));
        $rows = $listed->orderByDesc('published_at')->orderByDesc('id')->forPage($page, self::PER_PAGE)->get(self::FIELDS)->map(fn ($row) => (array) $row)->all();
        $ctaRows = $this->section('news.cta');

        $categoryMap = [];
        $categoryDtos = [];
        foreach ($categories as $row) {
            if ($dto = $this->categoryDto($row, $this->newsPhotoExists($row['image']) ? (string) $row['image'] : '')) {
                $categoryMap[(int) $row['id']] = $dto;
                $categoryDtos[] = $dto;
            }
        }
        $category = $selected ? $this->categoryDto($selected, (string) $selected['image'], $selectedMeta) : null;
        $article = fn (array $row, bool $featured) => $this->articleDto($row, $categoryMap, $featured);
        $hotline = is_scalar($this->settings->options()['hotline'] ?? null) ? trim((string) $this->settings->options()['hotline']) : '';
        $introTitle = Text::cms($intro['title'] ?? '');
        $introDescription = Text::cms($intro['summary'] ?? '');
        $defaultDescription = 'Kiến thức năng lượng mặt trời, thông tin sản phẩm và câu chuyện từ Phúc Thịnh Solar.';

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/tin-tuc/',
            'title' => $category ? $category['name'] : ($introTitle ?: 'Kiến thức'),
            'description' => $category ? $category['description'] : ($introDescription ?: $defaultDescription),
            'seo' => $this->pageSeo('tin-tuc', fn ($v) => Text::cms($v)),
            'category' => $category,
            'categories' => $categoryDtos,
            'featured' => $heroEnabled && ! $category && $query === '' ? array_values(array_filter(array_map(fn ($row) => $article($row, true), $featuredRows))) : [],
            'articles' => array_values(array_filter(array_map(fn ($row) => $article($row, false), $rows))),
            'pagination' => ['page' => $page, 'perPage' => self::PER_PAGE, 'total' => max(0, $total)],
            'intro' => ! $introRows || $intro ? ['title' => $introTitle ?: 'Kiến thức', 'description' => $introDescription ?: $defaultDescription] : null,
            'cta' => $this->cta($ctaRows, $hotline),
            'labels' => ['all' => 'Tất cả', 'search' => 'Tìm kiếm bài viết', 'featured' => 'Bài viết nổi bật', 'results' => 'Bài viết', 'empty' => 'Chưa tìm thấy bài viết phù hợp.', 'reset' => 'Xem tất cả bài viết', 'read' => 'phút đọc', 'pages' => 'Trang bài viết', 'readMore' => 'Đọc tiếp', 'ctaPhone' => 'Gọi ngay hotline'],
        ]];
    }

    /** @return array{0: array<string, mixed>, 1: list<int>}|null the category row and the ids of it and every category below */
    private function resolve(string $type, array $segments): ?array
    {
        $depth = ['list' => 1, 'cat' => 2, 'item' => 3, 'sub' => 4][$type];
        if (count($segments) !== $depth) {
            return null;
        }
        $row = null;
        $parentId = null;
        foreach ($segments as $i => $segment) {
            $row = $this->db->table('post_categories')->where('kind', 'article')->where('slug', $segment)->where('depth', $i + 1)
                ->where('parent_id', $parentId)->where('is_visible', true)->first(['id', 'name', 'summary', 'slug', 'image']);
            if (! $row || Text::slug($row->slug) !== $segment) {
                return null;
            }
            $parentId = (int) $row->id;
        }

        // Articles of this category and of every category below it.
        $ids = [$parentId];
        for ($level = [$parentId]; $level;) {
            $level = $this->db->table('post_categories')->whereIn('parent_id', $level)->pluck('id')->map(fn ($id) => (int) $id)->all();
            array_push($ids, ...$level);
        }

        return [(array) $row, $ids];
    }

    private function categoryDto(array $row, string $photo, array $meta = []): ?array
    {
        $name = Text::cms($row['name']);
        $slug = Text::slug($row['slug']);
        if ($name === '' || $slug === '') {
            return null;
        }

        return ['id' => (int) $row['id'], 'name' => $name, 'description' => Text::cms((string) $row['summary']), 'slug' => $slug, 'url' => '/'.$slug.'/', 'canonicalPath' => '/'.$slug,
            'image' => self::media($photo)] + $meta;
    }

    private function articleDto(array $row, array $categories, bool $featured): ?array
    {
        $title = Text::cms($row['title']);
        $slug = Text::slug($row['slug']);
        if ($title === '' || $slug === '') {
            return null;
        }
        $content = Text::cms((string) $row['body']);
        $description = Text::cms((string) $row['summary']) ?: $content;
        if (mb_strlen($description) > 180) {
            $description = mb_substr($description, 0, 177).'…';
        }
        $categoryId = (int) $row['category_id'];

        return [
            'id' => (int) $row['id'],
            'title' => $title,
            'slug' => $slug,
            'url' => '/'.$slug.'/',
            'description' => $description,
            'date' => ! empty($row['published_at']) ? date('Y-m-d', (int) strtotime((string) $row['published_at'])) : '',
            'image' => self::media($this->newsPhotoExists($row['image']) ? $row['image'] : ''),
            'readingMinutes' => self::readingMinutes($content),
            'category' => $categoryId > 0 ? ($categories[$categoryId] ?? null) : null,
            'featured' => $featured,
        ];
    }

    /** First published CTA row; the built-in invitation when Admin has none or only a published empty one. */
    private function cta(array $rows, string $hotline): ?array
    {
        $phone = $hotline === '' ? null : ['label' => 'Gọi ngay hotline', 'text' => $hotline, 'tel' => preg_replace('/[^0-9+]/', '', $hotline)];
        $anyVisible = false;
        foreach ($rows as $row) {
            if (! $row['is_visible']) {
                continue;
            }
            $anyVisible = true;
            $title = Text::cms($row['title']);
            $description = Text::cms($row['summary']);
            if ($title === '' && $description === '') {
                continue;
            }

            return ['title' => $title, 'description' => $description, 'buttonLabel' => Text::cms($row['extra']),
                'image' => self::media($this->newsPhotoExists($row['image']) ? $row['image'] : ''), 'hotline' => $phone];
        }
        if ($anyVisible || ! $rows) {
            return ['title' => 'Đội ngũ Phúc Thịnh Solar luôn sẵn sàng hỗ trợ', 'description' => 'Liên hệ ngay để được tư vấn và cập nhật những thông tin mới nhất.', 'buttonLabel' => 'Nhận tư vấn ngay', 'image' => '', 'hotline' => $phone];
        }

        return null;
    }

    public static function readingMinutes(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        return max(1, (int) ceil(count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []) / 220));
    }

    /** /upload/news/<file> for a safe name; existence is checked by the caller. */
    public static function media(mixed $value): string
    {
        $value = is_scalar($value) ? ltrim(trim((string) $value), '/') : '';

        return $value !== '' && basename($value) === $value && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value) ? '/upload/news/'.$value : '';
    }

    private function newsPhotoExists(mixed $photo): bool
    {
        return is_scalar($photo) && (string) $photo !== '' && ! str_contains((string) $photo, '..') && $this->urls->exists('upload/news/'.$photo);
    }
}
