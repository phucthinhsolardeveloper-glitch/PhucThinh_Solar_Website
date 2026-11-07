<?php

namespace App\Domain\News;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Pages\PageSupport;
use App\Legacy\Settings\LegacySettings;
use App\Legacy\Support\LegacyText;

/**
 * Knowledge hub (api/pts_v1_news.php): six articles a page, newest first, optional search and category
 * path (list/cat/item/sub). Without filters the first page shows up to three featured articles
 * (those ticked "newshub", else the top of the manual order) that are then left out of the list.
 */
class NewsListing extends PageSupport
{
    private const PER_PAGE = 6;

    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const FIELDS = 'id,id_list,namevi,slugvi,photo,descvi,contentvi,date_created,status';

    public function __construct(ProductMediaUrls $urls, private readonly LegacySettings $settings)
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
        foreach ($this->rows("select id,namevi,descvi,slugvi,photo,status from table_news_list where type='tin-tuc' and find_in_set('hienthi',status)"
            ." and exists(select 1 from table_news n where n.id_list=table_news_list.id and n.type='tin-tuc' and find_in_set('hienthi',n.status) and n.slugvi<>'') order by numb,id desc") as $row) {
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
            $action = ['list' => 'man_list', 'cat' => 'man_cat', 'item' => 'man_item', 'sub' => 'man_sub'][$type];
            $seoRow = $this->row('select titlevi,descriptionvi,keywordsvi from table_seo where id_parent = ? and com = ? and act = ? and type = ? limit 1', [(int) $selected['id'], 'news', $action, 'tin-tuc']);
            $selected['_meta'] = ['type' => 'tin-tuc', 'level' => $type, 'path' => implode('/', $segments), 'seo' => [
                'title' => LegacyText::flat($seoRow['titlevi'] ?? ''),
                'description' => LegacyText::flat($seoRow['descriptionvi'] ?? ''),
                'keywords' => LegacyText::flat($seoRow['keywordsvi'] ?? ''),
                'image' => $this->urls->visual($selected['photo'] ?? '', 'upload/news/'),
            ]];
        }

        $where = "type=? and find_in_set('hienthi',status) and slugvi<>''";
        $params = ['tin-tuc'];
        $introRows = $this->rows("select namevi,descvi,status from table_news where type='news-intro' order by numb,id desc");
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
            $hub = (int) ($this->row("select count(*) as num from table_news where type='tin-tuc' and find_in_set('newshub',status)")['num'] ?? 0) > 0;
            $featuredRows = $this->rows('select '.self::FIELDS.' from table_news where '.$where.($hub ? " and find_in_set('newshub',status)" : '').' order by numb,date_created desc,id desc limit 3', $params);
            if ($featuredRows) {
                $where .= ' and id not in ('.implode(',', array_fill(0, count($featuredRows), '?')).')';
                array_push($params, ...array_map(fn ($row) => (int) $row['id'], $featuredRows));
            }
        }
        if ($selected) {
            $where .= " and {$field}=?";
            $params[] = (int) $selected['id'];
        }
        if ($query !== '') {
            $pattern = '%'.strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $where .= " and (namevi like ? escape '!' or descvi like ? escape '!' or contentvi like ? escape '!')";
            array_push($params, $pattern, $pattern, $pattern);
        }
        $total = (int) ($this->row('select count(*) as num from table_news where '.$where, $params)['num'] ?? 0);
        $page = min($page, max(1, (int) ceil($total / self::PER_PAGE)));
        $rows = $this->rows('select '.self::FIELDS.' from table_news where '.$where.' order by date_created desc,id desc limit '.(($page - 1) * self::PER_PAGE).','.self::PER_PAGE, $params);
        $ctaRows = $this->rows("select namevi,descvi,content1vi,photo,status from table_news where type='news-cta' order by numb,id desc");

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
        $tables = ['table_news_list', 'table_news_cat', 'table_news_item', 'table_news_sub'];
        $parents = [null, 'id_list', 'id_cat', 'id_item'];
        $row = [];
        $parentId = 0;
        foreach ($segments as $i => $segment) {
            $row = $this->row("select id,namevi,descvi,slugvi,photo,status from {$tables[$i]} where slugvi=?".($parents[$i] ? " and {$parents[$i]}=?" : '')
                ." and type='tin-tuc' and find_in_set('hienthi',status) limit 1", $parents[$i] ? [$segment, $parentId] : [$segment]);
            if (! $row || (int) $row['id'] <= 0 || ! self::visible($row) || LegacyText::slug($row['slugvi']) !== $segment) {
                return null;
            }
            $parentId = (int) $row['id'];
        }

        return [$row, ['list' => 'id_list', 'cat' => 'id_cat', 'item' => 'id_item', 'sub' => 'id_sub'][$type]];
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
