<?php

namespace App\Domain\News;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Pages\PageSupport;
use App\Models\PostCategory;
use App\Legacy\Support\LegacyText;
use App\Support\SiteSettings;

/**
 * Knowledge hub (api/pts_v1_news.php): six articles a page, newest first, optional search and category
 * path (list/cat/item/sub). Without filters the first page shows up to three featured articles
 * (those ticked "newshub", else the top of the manual order) that are then left out of the list.
 */
class NewsListing extends PageSupport
{
    private const PER_PAGE = 6;

    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const FIELDS = "id, category_id as id_list, title as namevi, slug as slugvi, coalesce(image,'') as photo, coalesce(summary,'') as descvi, coalesce(body,'') as contentvi, published_at as date_created, concat_ws(',', if(is_visible,'hienthi',null), if(in_hub,'newshub',null)) as status";

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

        $categories = [];
        foreach ($this->rows("select c.id, c.name as namevi, coalesce(c.summary,'') as descvi, c.slug as slugvi, coalesce(c.image,'') as photo, 'hienthi' as status from post_categories c"
            ." where c.kind='article' and c.depth=1 and c.is_visible=1 and exists(select 1 from posts p where p.category_id=c.id and p.kind='article' and p.is_visible=1 and p.slug<>'') order by c.sort_order,c.id desc") as $row) {
            $row['_photo'] = $this->newsPhotoExists($row['photo']) ? $row['photo'] : '';
            $categories[] = $row;
        }
        $selected = null;
        $field = null;
        if ($slug !== '') {
            $resolved = $this->resolve($type, $segments);
            if ($resolved === null) {
                return [404, ['error' => ['code' => 'CATEGORY_NOT_FOUND']]];
            }
            [$selected, $field] = $resolved;
            $seoRow = $this->row("select coalesce(title,'') as titlevi, coalesce(description,'') as descriptionvi, coalesce(keywords,'') as keywordsvi from seo_metas where owner_type=? and owner_id=? limit 1", [PostCategory::class, (int) $selected['id']]);
            $selected['_meta'] = ['type' => 'tin-tuc', 'level' => $type, 'path' => implode('/', $segments), 'seo' => [
                'title' => LegacyText::flat($seoRow['titlevi'] ?? ''),
                'description' => LegacyText::flat($seoRow['descriptionvi'] ?? ''),
                'keywords' => LegacyText::flat($seoRow['keywordsvi'] ?? ''),
                'image' => $this->urls->visual($selected['photo'] ?? '', 'upload/news/'),
            ]];
        }

        $where = "kind=? and is_visible=1 and slug<>''";
        $params = ['article'];
        $introRows = $this->items('news.intro');
        $intro = [];
        foreach ($introRows as $row) {
            if (self::visible($row)) {
                $intro = $row;
                break;
            }
        }
        $heroEnabled = ! $introRows || self::flag($intro['status'] ?? '', 'hero');
        $featuredRows = [];
        if ($slug === '' && $query === '' && $heroEnabled) {
            $hub = (int) ($this->row("select count(*) as num from posts where kind='article' and in_hub=1")['num'] ?? 0) > 0;
            $featuredRows = $this->rows('select '.self::FIELDS.' from posts where '.$where.($hub ? ' and in_hub=1' : '').' order by sort_order,published_at desc,id desc limit 3', $params);
            if ($featuredRows) {
                $where .= ' and id not in ('.implode(',', array_fill(0, count($featuredRows), '?')).')';
                array_push($params, ...array_map(fn ($row) => (int) $row['id'], $featuredRows));
            }
        }
        if ($selected) {
            $where .= ' and category_id in ('.implode(',', array_fill(0, count($field), '?')).')';
            array_push($params, ...$field);
        }
        if ($query !== '') {
            $pattern = '%'.strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $where .= " and (title like ? escape '!' or summary like ? escape '!' or body like ? escape '!')";
            array_push($params, $pattern, $pattern, $pattern);
        }
        $total = (int) ($this->row('select count(*) as num from posts where '.$where, $params)['num'] ?? 0);
        $page = min($page, max(1, (int) ceil($total / self::PER_PAGE)));
        $rows = $this->rows('select '.self::FIELDS.' from posts where '.$where.' order by published_at desc,id desc limit '.(($page - 1) * self::PER_PAGE).','.self::PER_PAGE, $params);
        $ctaRows = $this->items('news.cta');

        $categoryMap = [];
        $categoryDtos = [];
        foreach ($categories as $row) {
            if ($dto = $this->categoryDto($row)) {
                $categoryMap[(int) $row['id']] = $dto;
                $categoryDtos[] = $dto;
            }
        }
        $category = $selected ? $this->categoryDto($selected + ['_photo' => $selected['photo'] ?? ''], $selected['_meta']) : null;
        $article = fn (array $row, bool $featured) => $this->articleDto($row + ['_photo' => $this->newsPhotoExists($row['photo']) ? $row['photo'] : ''], $categoryMap, $featured);
        $hotline = is_scalar($this->settings->options()['hotline'] ?? null) ? trim((string) $this->settings->options()['hotline']) : '';
        $introTitle = LegacyText::cms($intro['namevi'] ?? '');
        $introDescription = LegacyText::cms($intro['descvi'] ?? '');
        $defaultDescription = 'Kiến thức năng lượng mặt trời, thông tin sản phẩm và câu chuyện từ Phúc Thịnh Solar.';

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/tin-tuc/',
            'title' => $category ? $category['name'] : ($introTitle ?: 'Kiến thức'),
            'description' => $category ? $category['description'] : ($introDescription ?: $defaultDescription),
            'seo' => $this->pageSeo('tin-tuc', fn ($v) => LegacyText::cms($v)),
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

    /** @return array{0: array<string, mixed>, 1: string}|null the category row and the article column it filters */
    private function resolve(string $type, array $segments): ?array
    {
        $depth = ['list' => 1, 'cat' => 2, 'item' => 3, 'sub' => 4][$type];
        if (count($segments) !== $depth) {
            return null;
        }
        $row = [];
        $parentId = 0;
        foreach ($segments as $i => $segment) {
            $row = $this->row("select id, name as namevi, coalesce(summary,'') as descvi, slug as slugvi, coalesce(image,'') as photo, 'hienthi' as status from post_categories"
                ." where kind='article' and slug=? and depth=? and ".($i ? 'parent_id=?' : 'parent_id is null').' and is_visible=1 limit 1', $i ? [$segment, $i + 1, $parentId] : [$segment, 1]);
            if (! $row || (int) $row['id'] <= 0 || ! self::visible($row) || LegacyText::slug($row['slugvi']) !== $segment) {
                return null;
            }
            $parentId = (int) $row['id'];
        }

        // Articles of this category and of every category below it.
        $ids = [$parentId];
        for ($level = [$parentId]; $level; ) {
            $level = array_map('intval', array_column($this->rows('select id from post_categories where parent_id in ('.implode(',', $level).')'), 'id'));
            array_push($ids, ...$level);
        }

        return [$row, $ids];
    }

    private function categoryDto(array $row, array $meta = []): ?array
    {
        $id = (int) ($row['id'] ?? 0);
        $name = LegacyText::cms($row['namevi'] ?? '');
        $slug = LegacyText::slug($row['slugvi'] ?? '');
        if (! self::visible($row) || $id <= 0 || $name === '' || $slug === '') {
            return null;
        }

        return ['id' => $id, 'name' => $name, 'description' => LegacyText::cms($row['descvi'] ?? ''), 'slug' => $slug, 'url' => '/'.$slug.'/', 'canonicalPath' => '/'.$slug,
            'image' => self::media($row['_photo'] ?? '')] + array_intersect_key($meta, array_flip(['type', 'level', 'path', 'seo']));
    }

    private function articleDto(array $row, array $categories, bool $featured): ?array
    {
        $id = (int) ($row['id'] ?? 0);
        $title = LegacyText::cms($row['namevi'] ?? '');
        $slug = LegacyText::slug($row['slugvi'] ?? '');
        if (! self::visible($row) || $id <= 0 || $title === '' || $slug === '') {
            return null;
        }
        $content = LegacyText::cms($row['contentvi'] ?? '');
        $description = LegacyText::cms($row['descvi'] ?? '') ?: $content;
        if (mb_strlen($description) > 180) {
            $description = mb_substr($description, 0, 177).'…';
        }
        $categoryId = (int) ($row['id_list'] ?? 0);

        return [
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'url' => '/'.$slug.'/',
            'description' => $description,
            'date' => ! empty($row['date_created']) ? date('Y-m-d', (int) $row['date_created']) : '',
            'image' => self::media($row['_photo']),
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
            if (! self::visible($row)) {
                continue;
            }
            $anyVisible = true;
            $title = LegacyText::cms($row['namevi']);
            $description = LegacyText::cms($row['descvi']);
            if ($title === '' && $description === '') {
                continue;
            }

            return ['title' => $title, 'description' => $description, 'buttonLabel' => LegacyText::cms($row['content1vi']),
                'image' => self::media($this->newsPhotoExists($row['photo']) ? $row['photo'] : ''), 'hotline' => $phone];
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

    /** /upload/news/<file> for a safe name; existence is checked by the caller (ptsNewsMedia). */
    public static function media(mixed $value): string
    {
        $value = is_scalar($value) ? ltrim(trim((string) $value), '/') : '';

        return $value !== '' && basename($value) === $value && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value) ? '/upload/news/'.$value : '';
    }

    private function newsPhotoExists(mixed $photo): bool
    {
        return is_scalar($photo) && (string) $photo !== '' && ! str_contains((string) $photo, '..') && $this->urls->exists('upload/news/'.$photo);
    }

    private static function flag(mixed $status, string $flag): bool
    {
        return in_array($flag, array_filter(array_map('trim', explode(',', (string) $status))), true);
    }
}
