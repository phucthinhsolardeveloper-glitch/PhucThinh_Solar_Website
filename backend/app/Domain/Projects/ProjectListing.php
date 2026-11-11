<?php

namespace App\Domain\Projects;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Content\PostVisibility;
use App\Domain\News\NewsListing;
use App\Domain\Pages\PageSupport;
use App\Models\PostCategory;
use App\Support\SiteSettings;
use App\Support\Text;

/**
 * Completed projects. The first page without a category lifts one project into the "featured"
 * slot: the first one marked for the hub (in_hub), else the first on the page.
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

        $categoryRows = $this->db->table('post_categories')->where('kind', 'project')->where('depth', 1)->where('is_visible', true)
            ->orderBy('sort_order')->orderByDesc('id')->get(['id', 'name', 'summary', 'image', 'slug'])->map(fn ($row) => (array) $row)->all();
        $selected = null;
        foreach ($categoryRows as $row) {
            if ($slug !== '' && (string) $row['slug'] === $slug) {
                $selected = $row;
            }
        }
        if ($slug !== '' && $selected === null) {
            return [404, ['error' => ['code' => 'CATEGORY_NOT_FOUND']]];
        }
        $category = null;
        if ($selected !== null) {
            $seoRow = $this->db->table('seo_metas')->where('owner_type', PostCategory::class)->where('owner_id', $selected['id'])->first(['title', 'description', 'keywords']);
            // The selected category's picture is its SEO share image; the card itself shows none.
            $category = $this->categoryDto($selected, '', [
                'title' => Text::cms(Text::flat((string) $seoRow?->title)),
                'description' => Text::cms(Text::flat((string) $seoRow?->description)),
                'keywords' => Text::cms(Text::flat((string) $seoRow?->keywords)),
                'image' => $this->urls->visual((string) $selected['image'], 'upload/news/'),
            ]);
        }

        $listed = $this->db->table('posts')->where('kind', 'project')->where(PostVisibility::live())
            ->when($selected !== null, fn ($q) => $q->where('category_id', $selected['id']));
        $total = (clone $listed)->count();
        $rows = $listed->orderBy('sort_order')->orderByDesc('id')->forPage($page, $perPage)
            ->get(['id', 'category_id', 'title', 'slug', 'image', 'summary', 'address', 'province_id', 'ward_id', 'in_hub', 'capacity_kwp'])->map(fn ($row) => (array) $row)->all();
        $featuredRow = [];
        if ($page === 1 && $selected === null && $rows) {
            foreach ($rows as $index => $row) {
                if ($row['in_hub']) {
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
            if ($dto = $this->categoryDto($row, $this->photo($row['image']))) {
                $categoryMap[(int) $row['id']] = $dto;
                $categoryDtos[] = $dto;
            }
        }
        $project = fn (array $row, bool $featured) => $this->projectDto($row, $categoryMap, $featured);
        $introRows = $this->section('projects.intro');

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/du-an-da-thuc-hien/',
            'title' => $category ? 'Dự án '.$category['name'] : 'Dự án đã thực hiện',
            'seo' => $this->pageSeo('du-an', fn ($v) => Text::cms($v)),
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

    private function categoryDto(array $row, string $photo, ?array $seo = null): ?array
    {
        $id = (int) $row['id'];
        $name = Text::cms($row['name']);
        $slug = Text::slug($row['slug']);
        if ($name === '' || $slug === '') {
            return null;
        }
        $dto = ['id' => $id, 'name' => $name, 'description' => Text::cms((string) $row['summary']), 'slug' => $slug, 'url' => '/danh-muc-du-an/'.$slug.'/',
            'canonicalPath' => '/'.$slug, 'image' => NewsListing::media($photo)];
        if ($seo !== null) {
            $dto['seo'] = $seo;
        }

        return $dto;
    }

    private function projectDto(array $row, array $categories, bool $featured): ?array
    {
        $name = Text::cms($row['title']);
        $slug = Text::slug($row['slug']);
        if ($name === '' || $slug === '') {
            return null;
        }
        $categoryId = (int) $row['category_id'];

        return ['id' => (int) $row['id'], 'title' => $name, 'slug' => $slug, 'url' => '/'.$slug.'/', 'description' => Text::cms((string) $row['summary']), 'address' => Text::cms(ProjectLocation::label($row['address'], $row['province_id'] ?? null, $row['ward_id'] ?? null)),
            'image' => NewsListing::media($this->photo($row['image'])), 'category' => $categoryId > 0 ? ($categories[$categoryId] ?? null) : null, 'featured' => $featured,
            'kwp' => is_numeric($row['capacity_kwp'] ?? null) && (float) $row['capacity_kwp'] > 0 ? (float) $row['capacity_kwp'] : null];
    }

    private function intro(array $rows, ?array $category): array
    {
        $intro = [];
        foreach ($rows as $row) {
            if ($row['is_visible']) {
                $intro = $row;
                break;
            }
        }
        $introDescription = Text::cms($intro['summary'] ?? '');

        return [
            'visible' => $category !== null || ! $rows || $intro !== [],
            'title' => Text::cms($category['name'] ?? '') ?: (Text::cms($intro['title'] ?? '') ?: 'Dự án đã thực hiện'),
            'description' => Text::cms($category['description'] ?? '') ?: ($introDescription ?: 'Phúc Thịnh Solar tự hào đồng hành cùng khách hàng kiến tạo những công trình năng lượng sạch, bền vững cho tương lai.'),
        ];
    }

    private function benefits(): array
    {
        $items = [];
        foreach ($this->section('projects.benefits', 3, true) as $row) {
            $label = Text::cms($row['title']);
            if ($label !== '') {
                $items[] = ['label' => $label, 'image' => NewsListing::media($this->photo($row['image']))];
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
        $rows = $this->section('projects.cta') ?: [[
            'subtitle' => '', 'title' => 'Bạn có công trình cần tư vấn?', 'summary' => 'Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ, tư vấn giải pháp điện mặt trời phù hợp nhất cho nhu cầu của bạn.',
            'extra' => 'Nhận tư vấn miễn phí', 'is_visible' => true, 'image' => '',
        ]];
        $hotline = is_scalar($this->settings->options()['hotline'] ?? null) ? trim((string) $this->settings->options()['hotline']) : '';
        foreach ($rows as $row) {
            if (! $row['is_visible']) {
                continue;
            }

            return [
                'eyebrow' => Text::cms($row['subtitle']) ?: 'Đồng hành cùng năng lượng sạch',
                'title' => Text::cms($row['title']) ?: 'Bạn có công trình cần tư vấn?',
                'description' => Text::cms($row['summary']) ?: 'Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ, tư vấn giải pháp điện mặt trời phù hợp nhất cho nhu cầu của bạn.',
                'buttonLabel' => Text::cms(trim($row['extra']) !== '' ? $row['extra'] : 'Nhận tư vấn miễn phí'),
                'image' => NewsListing::media($this->photo($row['image']) ?: $fallbackPhoto),
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
        foreach ($this->section('projects.trust', 3, true) as $index => $row) {
            $label = Text::cms($row['title']);
            if ($label !== '') {
                $items[] = ['label' => $label, 'image' => NewsListing::media($this->photo($row['image'])), 'icon' => $icons[min($index, 2)]];
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
