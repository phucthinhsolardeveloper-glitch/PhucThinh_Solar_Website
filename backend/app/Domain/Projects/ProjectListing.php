<?php

namespace App\Domain\Projects;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\News\NewsListing;
use App\Domain\Pages\PageSupport;
use App\Models\PostCategory;
use App\Legacy\Support\LegacyText;
use App\Support\SiteSettings;

/**
 * Completed projects (api/pts_v1_projects.php). The first page without a category lifts one project
 * into the "featured" slot: the first one ticked "projecthub", else the first on the page.
 */
class ProjectListing extends PageSupport
{
    private const FALLBACK_CTA_PHOTO = 'anh-quan-gl-4-9449.jpg';

    public function __construct(ProductMediaUrls $urls, private readonly SiteSettings $settings)
    {
        parent::__construct($urls);
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(array $get): array
    {
        $bad = [400, ['error' => ['code' => 'BAD_QUERY']]];
        foreach ($get as $key => $value) {
            if (! in_array($key, ['category', 'page', 'per_page'], true) || ! is_string($value) || trim($value) === '') {
                return $bad;
            }
        }
        $slug = trim($get['category'] ?? '', '/');
        if (($slug !== '' && ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug))
            || (isset($get['page']) && ! preg_match('/^[1-9][0-9]*$/', $get['page'])) || (isset($get['per_page']) && ! preg_match('/^[1-9][0-9]*$/', $get['per_page']))) {
            return $bad;
        }
        $page = (int) ($get['page'] ?? 1);
        $perPage = (int) ($get['per_page'] ?? 12);
        if ($page > 10000 || $perPage > 48) {
            return $bad;
        }

        $categoryRows = $this->rows("select id, name as namevi, coalesce(summary,'') as descvi, coalesce(image,'') as photo, slug as slugvi, 'hienthi' as status from post_categories where kind='project' and depth=1 and is_visible=1 order by sort_order,id desc");
        $selected = null;
        foreach ($categoryRows as $row) {
            if ($slug !== '' && (string) $row['slugvi'] === $slug) {
                $selected = $row;
            }
        }
        if ($slug !== '' && $selected === null) {
            return [404, ['error' => ['code' => 'CATEGORY_NOT_FOUND']]];
        }
        $category = null;
        if ($selected !== null) {
            $seoRow = $this->row("select coalesce(title,'') as titlevi, coalesce(description,'') as descriptionvi, coalesce(keywords,'') as keywordsvi from seo_metas where owner_type=? and owner_id=? limit 1", [PostCategory::class, (int) $selected['id']]);
            // As in PHP, the selected category carries no picture of its own (its row is taken before pictures are checked).
            $category = $this->categoryDto($selected + ['_photo' => ''], [
                'title' => LegacyText::cms(LegacyText::flat($seoRow['titlevi'] ?? '')),
                'description' => LegacyText::cms(LegacyText::flat($seoRow['descriptionvi'] ?? '')),
                'keywords' => LegacyText::cms(LegacyText::flat($seoRow['keywordsvi'] ?? '')),
                'image' => $this->urls->visual($selected['photo'] ?? '', 'upload/news/'),
            ]);
        }

        $where = "kind='project' and is_visible=1".($selected !== null ? ' and category_id=?' : '');
        $params = $selected !== null ? [(int) $selected['id']] : [];
        $total = (int) ($this->row("select count(*) as total from posts where {$where}", $params)['total'] ?? 0);
        $rows = $this->rows("select id, category_id as id_list, title as namevi, slug as slugvi, coalesce(image,'') as photo, coalesce(summary,'') as descvi, coalesce(address,'') as address, concat_ws(',', 'hienthi', if(in_hub,'projecthub',null)) as status from posts where {$where} order by sort_order,id desc limit ".(($page - 1) * $perPage).', '.$perPage, $params);
        $featuredRow = [];
        if ($page === 1 && $selected === null && $rows) {
            foreach ($rows as $index => $row) {
                if (self::visible($row) && in_array('projecthub', array_map('trim', explode(',', (string) $row['status'])), true)) {
                    $featuredRow = $row;
                    unset($rows[$index]);
                    break;
                }
            }
            if (! $featuredRow) {
                $featuredRow = array_shift($rows);
            }
            $rows = array_values($rows);
        }

        $categoryMap = [];
        $categoryDtos = [];
        foreach ($categoryRows as $row) {
            if ($dto = $this->categoryDto($row + ['_photo' => $this->photo($row['photo'])])) {
                $categoryMap[(int) $row['id']] = $dto;
                $categoryDtos[] = $dto;
            }
        }
        $project = fn (array $row, bool $featured) => $this->projectDto($row, $categoryMap, $featured);
        $introRows = $this->items('projects.intro');

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/du-an-da-thuc-hien/',
            'title' => $category ? 'Dự án '.$category['name'] : 'Dự án đã thực hiện',
            'seo' => $this->pageSeo('du-an', fn ($v) => LegacyText::cms($v)),
            'intro' => $this->intro($introRows, $category),
            'category' => $category,
            'categories' => $categoryDtos,
            'featured' => $featuredRow ? $project($featuredRow, true) : null,
            'projects' => array_values(array_filter(array_map(fn ($row) => $project($row, false), $rows))),
            'featuredBenefits' => $this->benefits(),
            'pagination' => ['page' => $page, 'perPage' => $perPage, 'total' => max(0, $total)],
            'cta' => $this->cta(),
            'labels' => ['all' => 'Tất cả', 'filters' => 'Lọc dự án', 'featured' => 'Dự án nổi bật', 'view' => 'Xem dự án', 'empty' => 'Chưa có dự án trong nhóm này.', 'cta' => 'Liên hệ ngay'],
        ]];
    }

    private function categoryDto(array $row, ?array $seo = null): ?array
    {
        $id = (int) ($row['id'] ?? 0);
        $name = LegacyText::cms($row['namevi'] ?? '');
        $slug = LegacyText::slug($row['slugvi'] ?? '');
        if (! self::visible($row) || $id <= 0 || $name === '' || $slug === '') {
            return null;
        }
        $dto = ['id' => $id, 'name' => $name, 'description' => LegacyText::cms($row['descvi'] ?? ''), 'slug' => $slug, 'url' => '/danh-muc-du-an/'.$slug.'/',
            'canonicalPath' => '/'.$slug, 'image' => NewsListing::media($row['_photo'])];
        if ($seo !== null) {
            $dto['seo'] = $seo;
        }

        return $dto;
    }

    private function projectDto(array $row, array $categories, bool $featured): ?array
    {
        $id = (int) $row['id'];
        $name = LegacyText::cms($row['namevi']);
        $slug = LegacyText::slug($row['slugvi']);
        if (! self::visible($row) || $id <= 0 || $name === '' || $slug === '') {
            return null;
        }
        $categoryId = (int) ($row['id_list'] ?? 0);

        return ['id' => $id, 'title' => $name, 'slug' => $slug, 'url' => '/'.$slug.'/', 'description' => LegacyText::cms($row['descvi']), 'address' => LegacyText::cms($row['address']),
            'image' => NewsListing::media($this->photo($row['photo'])), 'category' => $categoryId > 0 ? ($categories[$categoryId] ?? null) : null, 'featured' => $featured];
    }

    private function intro(array $rows, ?array $category): array
    {
        $intro = [];
        foreach ($rows as $row) {
            if (self::visible($row)) {
                $intro = $row;
                break;
            }
        }
        $introDescription = LegacyText::cms($intro['descvi'] ?? '');

        return [
            'visible' => $category !== null || ! $rows || $intro !== [],
            'title' => LegacyText::cms($category['name'] ?? '') ?: (LegacyText::cms($intro['namevi'] ?? '') ?: 'Dự án đã thực hiện'),
            'description' => LegacyText::cms($category['description'] ?? '') ?: ($introDescription ?: 'Phúc Thịnh Solar tự hào đồng hành cùng khách hàng kiến tạo những công trình năng lượng sạch, bền vững cho tương lai.'),
        ];
    }

    private function benefits(): array
    {
        $items = [];
        foreach ($this->items('projects.benefits', 3, true) as $row) {
            $label = LegacyText::cms($row['namevi']);
            if (self::visible($row) && $label !== '') {
                $items[] = ['label' => $label, 'image' => NewsListing::media($this->photo($row['photo']))];
            }
        }

        return $items ?: [
            ['label' => 'Khảo sát và tư vấn thiết kế miễn phí', 'image' => ''],
            ['label' => 'Thiết bị chính hãng, thi công đúng kỹ thuật', 'image' => ''],
            ['label' => 'Bảo hành rõ ràng, hỗ trợ vận hành lâu dài', 'image' => ''],
        ];
    }

    private function cta(): ?array
    {
        $fallbackPhoto = $this->photo(self::FALLBACK_CTA_PHOTO);
        $rows = $this->items('projects.cta') ?: [[
            'title_indexvi' => '', 'namevi' => 'Bạn có công trình cần tư vấn?', 'descvi' => 'Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ, tư vấn giải pháp điện mặt trời phù hợp nhất cho nhu cầu của bạn.',
            'content1vi' => 'Nhận tư vấn miễn phí', 'status' => 'hienthi', 'photo' => '',
        ]];
        $hotline = is_scalar($this->settings->options()['hotline'] ?? null) ? trim((string) $this->settings->options()['hotline']) : '';
        foreach ($rows as $row) {
            if (! self::visible($row)) {
                continue;
            }

            return [
                'eyebrow' => LegacyText::cms($row['title_indexvi']) ?: 'Đồng hành cùng năng lượng sạch',
                'title' => LegacyText::cms($row['namevi']) ?: 'Bạn có công trình cần tư vấn?',
                'description' => LegacyText::cms($row['descvi']) ?: 'Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ, tư vấn giải pháp điện mặt trời phù hợp nhất cho nhu cầu của bạn.',
                'buttonLabel' => LegacyText::cms(trim((string) $row['content1vi']) !== '' ? $row['content1vi'] : 'Nhận tư vấn miễn phí'),
                'image' => NewsListing::media($this->photo($row['photo']) ?: $fallbackPhoto),
                'trust' => $this->trust(),
                'hotline' => $hotline === '' ? null : ['label' => 'Gọi ngay hotline', 'text' => $hotline, 'tel' => preg_replace('/[^0-9+]/', '', $hotline)],
            ];
        }

        return null;
    }

    private function trust(): array
    {
        $icons = ['headset', 'cogs', 'shield'];
        $items = [];
        foreach ($this->items('projects.trust', 3, true) as $index => $row) {
            $label = LegacyText::cms($row['namevi']);
            if (self::visible($row) && $label !== '') {
                $items[] = ['label' => $label, 'image' => NewsListing::media($this->photo($row['photo'])), 'icon' => $icons[min($index, 2)]];
            }
        }

        return $items ?: [
            ['label' => 'Tư vấn tận tâm', 'image' => '', 'icon' => 'headset'],
            ['label' => 'Giải pháp tối ưu', 'image' => '', 'icon' => 'cogs'],
            ['label' => 'Đồng hành lâu dài', 'image' => '', 'icon' => 'shield'],
        ];
    }

    private function photo(mixed $photo): string
    {
        return is_scalar($photo) && (string) $photo !== '' && ! str_contains((string) $photo, '..') && $this->urls->exists('upload/news/'.$photo) ? (string) $photo : '';
    }
}
