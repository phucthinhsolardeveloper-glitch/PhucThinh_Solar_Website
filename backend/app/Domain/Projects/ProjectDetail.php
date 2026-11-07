<?php

namespace App\Domain\Projects;

use App\Domain\Pages\PageSupport;
use App\Models\Post;
use App\Legacy\Content\RichText;
use App\Legacy\Content\SolutionHub;
use App\Legacy\Support\LegacyText;

/** One completed project (api/pts_v1_project_detail.php): story, gallery, equipment used, nearby projects. */
class ProjectDetail extends PageSupport
{
    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(array $get): array
    {
        $bad = [400, ['error' => ['code' => 'BAD_QUERY']]];
        if (array_diff(array_keys($get), ['slug']) || (isset($get['slug']) && ! is_string($get['slug']))) {
            return $bad;
        }
        $slug = trim($get['slug'] ?? '', '/');
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $bad;
        }
        $row = $this->row("select id, category_id as id_list, title as namevi, slug as slugvi, coalesce(summary,'') as descvi, coalesce(body,'') as contentvi, coalesce(image,'') as photo, coalesce(address,'') as address,"
            ." coalesce(client_name,'') as tenkhachhang, coalesce(started_on,'') as project_start_date, coalesce(finished_on,'') as project_end_date, 'hienthi' as status, 'du-an' as type"
            ." from posts where slug = ? and kind = 'project' and is_visible = 1 limit 1", [$slug]);
        if (! $row) {
            return [404, ['error' => ['code' => 'PROJECT_NOT_FOUND']]];
        }
        $id = (int) $row['id'];
        $projectSlug = LegacyText::slug($row['slugvi']);
        $title = LegacyText::cms($row['namevi']);
        $seoRow = $this->row("select coalesce(title,'') as titlevi, coalesce(description,'') as descriptionvi, coalesce(keywords,'') as keywordsvi from seo_metas where owner_type=? and owner_id=? limit 0,1", [Post::class, $id]);
        $categoryRow = $this->row("select id, name as namevi, slug as slugvi, 'hienthi' as status from post_categories where id = ? and kind = 'project' and is_visible = 1 limit 1", [(int) $row['id_list']]);
        $categoryName = LegacyText::cms($categoryRow['namevi'] ?? '');
        $categorySlug = LegacyText::slug($categoryRow['slugvi'] ?? '');
        $category = (int) ($categoryRow['id'] ?? 0) > 0 && $categoryName !== '' && $categorySlug !== ''
            ? ['id' => (int) $categoryRow['id'], 'name' => $categoryName, 'slug' => $categorySlug, 'url' => '/danh-muc-du-an/'.$categorySlug.'/'] : null;

        $gallery = [];
        foreach ($this->rows("select coalesce(path,'') as photo, coalesce(caption,'') as namevi, coalesce(video_url,'') as link_video, 'man' as kind, if(kind='video','video','du-an') as val, 'hienthi' as status"
            ." from post_media where post_id = ? and is_visible = 1 order by sort_order,id desc", [$id]) as $item) {
            if (! self::visible($item) || $item['kind'] !== 'man') {
                continue;
            }
            $caption = LegacyText::cms($item['namevi']);
            $image = $this->strictImage($item['photo'], 'upload/news/');
            if ($item['val'] === 'du-an' && $image !== '') {
                $gallery[] = ['type' => 'image', 'src' => $image, 'caption' => $caption];
            } elseif ($item['val'] === 'video' && ($href = self::externalUrl($item['link_video'])) !== '') {
                $gallery[] = ['type' => 'video', 'src' => $image, 'href' => $href, 'caption' => $caption];
            }
        }
        $equipment = [];
        foreach ($this->rows("select p.id, p.name as namevi, p.slug as slugvi, coalesce(p.image,'') as photo, coalesce(p.code,'') as code, 'hienthi' as status from post_products pp inner join products p on p.id = pp.product_id"
            ." where pp.post_id = ? and pp.is_visible = 1 and p.is_visible = 1 order by pp.sort_order,pp.id", [$id]) as $product) {
            $name = LegacyText::cms($product['namevi']);
            $productSlug = LegacyText::slug($product['slugvi']);
            if (self::visible($product) && (int) $product['id'] > 0 && $name !== '' && $productSlug !== '') {
                $equipment[] = ['id' => (int) $product['id'], 'name' => $name, 'slug' => $productSlug, 'url' => '/san-pham/'.$productSlug, 'code' => LegacyText::cms($product['code']), 'image' => $this->strictImage($product['photo'], 'upload/product/')];
            }
        }
        $nearby = [];
        foreach ($this->rows("select id, title as namevi, slug as slugvi, coalesce(image,'') as photo, coalesce(address,'') as address, 'hienthi' as status from posts where id<>? and category_id <=> ? and kind='project' and is_visible=1 order by sort_order,id desc limit 0,3", [$id, $row['id_list']]) as $other) {
            $otherSlug = LegacyText::slug($other['slugvi']);
            $otherTitle = LegacyText::cms($other['namevi']);
            if (self::visible($other) && (int) $other['id'] > 0 && $otherSlug !== '' && $otherTitle !== '') {
                $nearby[] = ['id' => (int) $other['id'], 'title' => $otherTitle, 'slug' => $otherSlug, 'url' => '/'.$otherSlug.'/', 'address' => LegacyText::cms($other['address']), 'image' => $this->strictImage($other['photo'], 'upload/news/')];
            }
        }
        $seoTitle = ! empty($seoRow['titlevi']) ? LegacyText::cms($seoRow['titlevi']) : $title;
        if ($seoTitle !== '' && stripos($seoTitle, 'phúc thịnh s') === false) {
            $seoTitle .= ' | Phúc Thịnh Solar';
        }

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'project' => [
                'id' => $id,
                'title' => $title,
                'slug' => $projectSlug,
                'url' => '/'.$projectSlug.'/',
                'summary' => LegacyText::cms($row['descvi']),
                'coverImage' => $this->strictImage($row['photo'], 'upload/news/'),
                'category' => $category,
                'client' => LegacyText::cms($row['tenkhachhang']),
                'address' => LegacyText::cms($row['address']),
                'startDate' => self::date($row['project_start_date']),
                'endDate' => self::date($row['project_end_date']),
                'gallery' => $gallery,
                'relatedEquipment' => $equipment,
                'relatedProjects' => $nearby,
                'cta' => $this->cta(),
            ],
            'content' => RichText::dto(RichText::sanitize($row['contentvi'])),
            'seo' => [
                'title' => $seoTitle,
                'description' => ! empty($seoRow['descriptionvi']) ? LegacyText::cms($seoRow['descriptionvi']) : '',
                'keywords' => ! empty($seoRow['keywordsvi']) ? LegacyText::cms($seoRow['keywordsvi']) : '',
            ],
            'canonical' => ['url' => '/'.$projectSlug, 'sources' => ['project' => 'legacy canonical entity path /'.$slug, 'gallery' => 'news.php detail branch',
                'relatedEquipment' => 'product_projects.php', 'relatedProjects' => 'news.php same category', 'cta' => 'project_hub.php project-cta slot']],
            'parity' => [
                'viewCount' => ['included' => false, 'reason' => 'read_only_preview_does_not_increment_legacy_view_count'],
                'contactSubmission' => ['included' => false, 'reason' => 'legacy_contact_flow_stays_php'],
            ],
        ]];
    }

    /**
     * Admin "project-cta" block. The PHP endpoint never loads the site settings, so its hotline is always
     * empty; kept identical here (Nuxt takes the hotline from the site API).
     */
    private function cta(): ?array
    {
        $rows = $this->items('projects.cta');
        $visible = SolutionHub::select($rows, '', 1);
        if ($rows && ! $visible) {
            return null;
        }
        $row = $visible[0] ?? [];
        $image = $this->strictImage($row['photo'] ?? '', 'upload/news/');
        if ($image === '' && $this->urls->exists('upload/news/anh-quan-gl-4-9449.jpg')) {
            $image = '/upload/news/anh-quan-gl-4-9449.jpg';
        }
        $trust = [];
        foreach ($this->items('projects.trust', 3, true) as $item) {
            $label = LegacyText::cms($item['namevi']);
            if (self::visible($item) && $label !== '') {
                $trust[] = ['label' => $label, 'icon' => $this->strictImage($item['photo'], 'upload/news/'), 'faIcon' => 'fa-check'];
            }
        }

        return [
            'eyebrow' => SolutionHub::field($row, 'title_index') ?: 'Đồng hành cùng năng lượng sạch',
            'title' => SolutionHub::field($row, 'name') ?: 'Bạn có công trình cần tư vấn?',
            'description' => SolutionHub::field($row, 'desc') ?: 'Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ, tư vấn giải pháp điện mặt trời phù hợp nhất cho nhu cầu của bạn.',
            'buttonLabel' => SolutionHub::field($row, 'content1') ?: 'Nhận tư vấn miễn phí',
            'image' => $image,
            'hotline' => '',
            'trust' => $trust ?: [
                ['label' => 'Tư vấn tận tâm', 'icon' => '', 'faIcon' => 'fa-headset'],
                ['label' => 'Giải pháp tối ưu', 'icon' => '', 'faIcon' => 'fa-cogs'],
                ['label' => 'Đồng hành lâu dài', 'icon' => '', 'faIcon' => 'fa-shield-alt'],
            ],
        ];
    }

    private static function externalUrl(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }

        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true) ? $value : '';
    }

    private static function date(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}(?:[  T]\d{2}:\d{2}:\d{2})?$/', $value)) {
            return '';
        }
        $time = strtotime($value);

        return $time === false ? '' : date('Y-m-d', $time);
    }
}
