<?php

namespace App\Domain\Solutions;

use App\Domain\Pages\PageSupport;
use App\Models\Post;
use App\Support\RichText;
use App\Support\Text;

/** One solution page (api/pts_v1_solution_detail.php): body, gallery, sibling solutions, equipment, CTA. */
class SolutionDetail extends PageSupport
{
    private const FALLBACK_IMAGES = [
        'dien-mat-troi-ho-gia-dinh' => 'assets/images/site-visuals/solution-home-solar-v2.jpg',
        'he-thong-luu-tru-bess' => 'assets/images/site-visuals/solution-bess-v2.jpg',
        'bom-nuoc-nang-luong-mat-troi' => 'assets/images/site-visuals/solution-solar-pump-v2.jpg',
    ];

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
        $row = $this->row("select id, category_id as id_list, title as namevi, '' as nameen, coalesce(summary,'') as descvi, '' as descen, coalesce(body,'') as contentvi, '' as contenten, coalesce(extra_body,'') as content1vi, '' as content1en,"
            ." coalesce(image,'') as photo, coalesce(cover_image,'') as photo1, slug as slugvi, '' as slugen, 'hienthi' as status from posts where slug = ? and kind = 'solution' and is_visible = 1 limit 1", [$slug]);
        if (! $row) {
            return [404, ['error' => ['code' => 'SOLUTION_NOT_FOUND']]];
        }
        $id = (int) $row['id'];
        $category = ! empty($row['id_list']) ? $this->row("select name as namevi from post_categories where id = ? and kind = 'solution' and is_visible = 1 limit 1", [(int) $row['id_list']]) : [];
        $seoRow = $this->row("select coalesce(title,'') as titlevi, coalesce(description,'') as descriptionvi, coalesce(keywords,'') as keywordsvi from seo_metas where owner_type=? and owner_id=? limit 1", [Post::class, $id]);
        $solutions = $this->rows("select id, title as namevi, '' as nameen, coalesce(nav_title,'') as title_indexvi, '' as title_indexen, coalesce(summary,'') as descvi, '' as descen, coalesce(image,'') as photo, coalesce(cover_image,'') as photo1, slug as slugvi, '' as slugen, concat_ws(',', if(is_visible,'hienthi',null), if(in_hub,'solutionhub',null)) as status from posts where kind='solution' order by sort_order,id desc");
        $products = $this->rows("select id, name as namevi, '' as nameen, coalesce(summary,'') as descvi, '' as descen, coalesce(image,'') as photo, slug as slugvi, '' as slugen, concat_ws(',', if(is_visible,'hienthi',null), if(in_solution_hub,'solutionhub',null)) as status from categories where depth=1 order by sort_order,id desc");
        $ctaRows = $this->items('solutions.cta');
        $gallery = [];
        foreach ($this->rows("select coalesce(path,'') as photo from post_media where post_id = ? and kind='image' and is_visible=1 order by sort_order,id desc", [$id]) as $item) {
            if (($image = $this->strictImage($item['photo'], 'upload/news/')) !== '') {
                $gallery[] = $image;
            }
        }
        $image = $this->strictImage($row['photo1'], 'upload/news/') ?: $this->strictImage($row['photo'], 'upload/news/');
        if ($image === '' && isset(self::FALLBACK_IMAGES[$slug]) && $this->urls->exists(self::FALLBACK_IMAGES[$slug])) {
            $image = '/'.self::FALLBACK_IMAGES[$slug];
        }

        $navigation = [];
        foreach (SolutionHub::select($solutions, 'solutionhub') as $item) {
            $itemSlug = self::field($item, 'slug');
            $itemTitle = self::field($item, 'title_index') ?: self::field($item, 'name');
            if ($itemSlug === '' || $itemTitle === '') {
                continue;
            }
            $navigation[] = ['title' => $itemTitle, 'slug' => $itemSlug, 'url' => self::canonical($itemSlug),
                'image' => $this->strictImage($item['photo1'], 'upload/news/') ?: $this->strictImage($item['photo'], 'upload/news/'), 'active' => (int) $item['id'] === $id];
        }
        $equipment = [];
        foreach (SolutionHub::select($products, 'solutionhub', 4) as $item) {
            $name = self::field($item, 'name');
            if ($name === '') {
                continue;
            }
            $listSlug = trim((string) ($item['slugvi'] ?? ''), '/');
            $equipment[] = ['name' => $name, 'description' => self::field($item, 'desc'), 'url' => '/danh-muc-san-pham/'.($listSlug !== '' ? $listSlug.'/' : ''),
                'image' => $this->strictImage($item['photo'], 'upload/product/')];
        }
        $canonical = self::canonical(Text::slug($row['slugvi']));

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => $canonical,
            'seo' => [
                'title' => Text::flat($seoRow['titlevi'] ?? ''),
                'description' => Text::flat($seoRow['descriptionvi'] ?? ''),
                'keywords' => Text::flat($seoRow['keywordsvi'] ?? ''),
                'image' => $this->urls->visual($row['photo'] ?? '', 'upload/news/'),
            ],
            'solution' => [
                'id' => $id,
                'title' => self::text($row['namevi']),
                'slug' => Text::slug($row['slugvi']),
                'url' => $canonical,
                'category' => self::text($category['namevi'] ?? ''),
                'summary' => self::text($row['descvi']),
                'image' => $image,
                'content' => RichText::dto(RichText::sanitize($row['contentvi'])),
                'extraContent' => RichText::dto(RichText::sanitize($row['content1vi'])),
                'gallery' => array_values(array_unique($gallery)),
            ],
            'navigation' => ['allUrl' => '/giai-phap/', 'items' => $navigation],
            'equipment' => $equipment,
            'cta' => ['label' => 'Nhận tư vấn giải pháp', 'href' => '/lien-he/#yeu-cau-tu-van'],
            'campaignCta' => $this->campaignCta($ctaRows),
            'editorial' => ['rendered' => false, 'reason' => 'toc_derived_client_side_from_sanitized_html'],
            'parity' => ['toc' => ['included' => false, 'reason' => 'toc_derived_client_side_from_sanitized_html']],
        ]];
    }

    /**
     * Admin "solution-cta". The PHP endpoint does not load site settings, so the hotline is always
     * absent here; Nuxt shows the hotline from the site API.
     */
    private function campaignCta(array $rows): ?array
    {
        $visible = SolutionHub::select($rows, '', 1);
        if ($rows && ! $visible) {
            return null;
        }
        $row = $visible[0] ?? [];
        $button = SolutionHub::field($row, 'title_index') ?: 'Nhận tư vấn';

        return [
            'title' => SolutionHub::field($row, 'name') ?: 'Chưa biết giải pháp nào phù hợp?',
            'description' => SolutionHub::field($row, 'desc') ?: 'Chia sẻ nhu cầu của bạn. Đội ngũ Phúc Thịnh Solar sẽ hỗ trợ lựa chọn giải pháp phù hợp.',
            'buttonLabel' => $button,
            'image' => $this->strictImage($row['photo'] ?? '', 'upload/news/') ?: '/assets/images/site-visuals/catalog-street-lighting.jpg',
            'links' => ['consult' => ['label' => $button, 'href' => '#ptsContactModal']],
            'hotline' => null,
        ];
    }

    private static function canonical(string $slug): string
    {
        return $slug === '' ? '' : '/giai-phap/'.$slug.'/';
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8')))) : '';
    }

    /** Vietnamese column, English one when the Vietnamese is empty (solutionHubDetailField). */
    private static function field(array $row, string $field): string
    {
        return self::text($row[$field.'vi'] ?? '') ?: self::text($row[$field.'en'] ?? '');
    }
}
