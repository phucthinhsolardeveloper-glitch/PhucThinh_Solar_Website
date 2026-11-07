<?php

namespace App\Domain\Solutions;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Pages\PageSupport;
use App\Models\PostCategory;
use App\Support\RichText;
use App\Support\SiteSettings;
use App\Support\Text;

/**
 * Solutions hub (api/pts_v1_solutions.php): intro, solution cards ticked "solutionhub", equipment
 * shortcuts, CTA, editorial text with a table of contents, and an optional category view.
 */
class SolutionsListing extends PageSupport
{
    private const UI = [
        'eyebrow' => 'Giải pháp theo nhu cầu',
        'title' => 'Chọn đúng giải pháp cho nhu cầu của bạn',
        'intro' => 'Khám phá giải pháp năng lượng mặt trời hoặc tìm ngay thiết bị bạn cần. Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ bạn lựa chọn.',
        'choices' => 'Khám phá các giải pháp',
        'choice' => 'Xem giải pháp',
        'equipment' => 'Đi thẳng đến sản phẩm',
        'all_products' => 'Xem tất cả sản phẩm',
        'contact' => 'Chưa biết giải pháp nào phù hợp?',
        'contact_desc' => 'Chia sẻ nhu cầu của bạn. Đội ngũ Phúc Thịnh Solar sẽ hỗ trợ lựa chọn giải pháp phù hợp.',
        'consult' => 'Nhận tư vấn',
        'phone' => 'Gọi ngay hotline',
        'empty' => 'Các giải pháp đang được cập nhật. Hãy liên hệ để được tư vấn theo nhu cầu.',
    ];

    private const FALLBACK_IMAGES = [
        'dien-mat-troi-ho-gia-dinh' => 'assets/images/site-visuals/solution-home-solar-v2.jpg',
        'he-thong-luu-tru-bess' => 'assets/images/site-visuals/solution-bess-v2.jpg',
        'bom-nuoc-nang-luong-mat-troi' => 'assets/images/site-visuals/solution-solar-pump-v2.jpg',
    ];

    private const CTA_FALLBACK_IMAGE = 'assets/images/site-visuals/catalog-street-lighting.jpg';

    public function __construct(ProductMediaUrls $urls, private readonly SiteSettings $settings)
    {
        parent::__construct($urls);
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(array $get): array
    {
        if (array_diff(array_keys($get), ['category']) || (isset($get['category']) && (! is_string($get['category']) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $get['category'])))) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $solutions = $this->withImages($this->rows("select id, category_id as id_list, title as namevi, coalesce(nav_title,'') as title_indexvi, coalesce(summary,'') as descvi, coalesce(image,'') as photo, coalesce(cover_image,'') as photo1, slug as slugvi, concat_ws(',', if(is_visible,'hienthi',null), if(in_hub,'solutionhub',null)) as status from posts where kind='solution' order by sort_order,id desc"));
        $introRows = $this->withImages($this->items('solutions.intro'));
        $ctaRows = $this->withImages($this->items('solutions.cta'));
        $products = array_map(fn ($row) => $row + ['_photo' => $this->image($row['photo'], 'upload/product/')],
            $this->rows("select id, name as namevi, coalesce(summary,'') as descvi, coalesce(image,'') as photo, slug as slugvi, concat_ws(',', if(is_visible,'hienthi',null), if(in_solution_hub,'solutionhub',null)) as status from categories where depth=1 order by sort_order,id desc"));
        $groups = $this->rows("select c.id, c.name as namevi, coalesce(c.summary,'') as descvi, c.slug as slugvi, coalesce(c.image,'') as photo, 'hienthi' as status from post_categories c where c.kind='solution' and c.is_visible=1"
            ." and exists(select 1 from posts p where p.category_id=c.id and p.kind='solution' and p.is_visible=1 and p.slug<>'') order by c.sort_order,c.id desc");

        $data = $this->payload($solutions, $introRows, $ctaRows, $products);
        if (isset($get['category'])) {
            $group = array_values(array_filter($groups, fn ($g) => $g['slugvi'] === $get['category']))[0] ?? null;
            $category = null;
            if ($group) {
                $seoRow = $this->row("select coalesce(title,'') as titlevi, coalesce(description,'') as descriptionvi, coalesce(keywords,'') as keywordsvi from seo_metas where owner_type=? and owner_id=? limit 1", [PostCategory::class, (int) $group['id']]);
                $category = $this->category($group, $groups, $solutions, [
                    'title' => Text::flat($seoRow['titlevi'] ?? ''),
                    'description' => Text::flat($seoRow['descriptionvi'] ?? ''),
                    'keywords' => Text::flat($seoRow['keywordsvi'] ?? ''),
                    'image' => $this->urls->visual($group['photo'] ?? '', 'upload/news/'),
                ]);
            }
            if ($category === null) {
                return [404, ['error' => ['code' => 'CATEGORY_NOT_FOUND']]];
            }
            $data['category'] = $category;
        }

        return [200, $data];
    }

    private function payload(array $solutions, array $introRows, array $ctaRows, array $products): array
    {
        $ui = self::UI;
        $featured = SolutionHub::select($solutions, 'solutionhub');
        $cards = [];
        foreach ($featured as $row) {
            $slug = SolutionHub::field($row, 'slug');
            if ($slug === '') {
                continue;
            }
            $cards[] = ['title' => SolutionHub::navName($row), 'description' => SolutionHub::field($row, 'desc'), 'slug' => $slug, 'url' => '/giai-phap/'.$slug.'/',
                'image' => self::path($row['_photo1']) ?: (self::path($row['_photo']) ?: self::path($this->fallbackImage($slug))), 'actionLabel' => $ui['choice']];
        }

        $visibleIntro = SolutionHub::select($introRows, '', 1);
        $editorial = self::editorialToc($visibleIntro ? RichText::sanitize($visibleIntro[0]['contentvi'] ?? '') : '');
        $intro = null;
        $title = $ui['title'];
        if (! $introRows || $visibleIntro) {
            $row = $visibleIntro[0] ?? [];
            $title = SolutionHub::field($row, 'name') ?: $ui['title'];
            $hero = self::path($row['_photo'] ?? '');
            if (! $introRows && $hero === '') {
                foreach ($featured as $candidate) {
                    if (SolutionHub::field($candidate, 'slug') !== '' && ($hero = self::path($candidate['_photo1']) ?: self::path($candidate['_photo'])) !== '') {
                        break;
                    }
                }
            }
            $intro = [
                'eyebrow' => SolutionHub::field($row, 'title_index') ?: $ui['eyebrow'],
                'title' => $title,
                'description' => SolutionHub::field($row, 'desc') ?: $ui['intro'],
                'image' => $hero,
                'links' => ['consult' => ['label' => $ui['consult'], 'href' => '#ptsContactModal'], 'explore' => ['label' => $ui['choices'], 'href' => '/giai-phap/#cac-giai-phap']],
            ];
        }

        $equipment = [];
        foreach (SolutionHub::select($products, 'solutionhub', 4) as $row) {
            $slug = trim((string) ($row['slugvi'] ?? ''), '/');
            $equipment[] = ['name' => SolutionHub::field($row, 'name'), 'description' => SolutionHub::field($row, 'desc'),
                'url' => '/danh-muc-san-pham/'.($slug !== '' ? $slug.'/' : ''), 'image' => self::path($row['_photo'])];
        }

        $cta = null;
        $visibleCta = SolutionHub::select($ctaRows, '', 1);
        if (! $ctaRows || $visibleCta) {
            $row = $visibleCta[0] ?? [];
            $hotline = is_scalar($this->settings->options()['hotline'] ?? null) ? trim((string) $this->settings->options()['hotline']) : '';
            $tel = (string) preg_replace('/[^0-9+]/', '', $hotline);
            $button = SolutionHub::field($row, 'title_index') ?: $ui['consult'];
            $cta = [
                'title' => SolutionHub::field($row, 'name') ?: $ui['contact'],
                'description' => SolutionHub::field($row, 'desc') ?: $ui['contact_desc'],
                'buttonLabel' => $button,
                'image' => self::path($row['_photo'] ?? '') ?: self::path(self::CTA_FALLBACK_IMAGE),
                'links' => ['consult' => ['label' => $button, 'href' => '#ptsContactModal']],
                'hotline' => $tel === '' ? null : ['label' => 'Gọi ngay hotline', 'text' => $hotline, 'tel' => $tel],
            ];
        }

        return [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/giai-phap/',
            'title' => $title,
            'seo' => self::seo($this->pageSeo('giai-phap', fn ($v) => $v)),
            'category' => null,
            'intro' => $intro,
            'cards' => $cards,
            'equipment' => $equipment,
            'allProductsUrl' => '/san-pham/',
            'labels' => ['choices' => $ui['choices'], 'equipment' => $ui['equipment'], 'allProducts' => $ui['all_products'], 'empty' => $ui['empty'], 'consult' => $ui['consult'], 'phone' => $ui['phone']],
            'cta' => $cta,
            'editorial' => ['content' => RichText::dto($editorial['html']), 'toc' => $editorial['toc']],
        ];
    }

    private function category(array $group, array $groups, array $solutions, array $seo): ?array
    {
        $id = (int) $group['id'];
        $slug = Text::slug($group['slugvi']);
        $name = Text::cms($group['namevi']);
        if ($id <= 0 || $slug === '' || $name === '') {
            return null;
        }
        $cards = [];
        foreach (SolutionHub::select($solutions) as $row) {
            $itemSlug = Text::slug(SolutionHub::field($row, 'slug'));
            if ((int) $row['id_list'] !== $id || $itemSlug === '') {
                continue;
            }
            $cards[] = ['title' => SolutionHub::field($row, 'name'), 'description' => SolutionHub::field($row, 'desc'), 'slug' => $itemSlug, 'url' => '/giai-phap/'.$itemSlug.'/',
                'image' => self::path($row['_photo']), 'actionLabel' => 'Xem giải pháp'];
        }
        if (! $cards) {
            return null;
        }
        // Each group links to its featured solution when it has one, else to its category page.
        $featured = SolutionHub::select($solutions, 'solutionhub');
        $navigation = [];
        foreach ($groups as $nav) {
            $navSlug = Text::slug($nav['slugvi']);
            $navId = (int) $nav['id'];
            $navName = Text::cms($nav['namevi']);
            if ($navId <= 0 || $navSlug === '' || $navName === '') {
                continue;
            }
            $url = '/danh-muc-giai-phap/'.$navSlug.'/';
            foreach ($featured as $row) {
                if ((int) $row['id_list'] === $navId) {
                    $representative = Text::slug(SolutionHub::field($row, 'slug'));
                    if ($representative !== '') {
                        $url = '/giai-phap/'.$representative.'/';
                    }
                    break;
                }
            }
            $navigation[] = ['id' => $navId, 'name' => $navName, 'slug' => $navSlug, 'url' => $url, 'active' => $navId === $id];
        }

        return [
            'id' => $id,
            'name' => $name,
            'description' => Text::cms($group['descvi']) ?: 'Khám phá các giải pháp phù hợp với nhu cầu thực tế và quy mô công trình của bạn.',
            'seo' => self::seo($seo),
            'slug' => $slug,
            'canonicalPath' => '/'.$slug,
            'cards' => $cards,
            'navigation' => $navigation,
        ];
    }

    /** Numbered ids on h2/h3 headings of the editorial text, and the matching table of contents. */
    public static function editorialToc(string $html): array
    {
        if ($html === '') {
            return ['html' => '', 'toc' => ['rendered' => false, 'reason' => 'no_editorial_content']];
        }
        $items = [];
        $index = 0;
        $rendered = preg_replace_callback('~<h([23])([^>]*)>(.*?)</h\1>~is', function ($match) use (&$items, &$index) {
            if ($index >= 30) {
                return $match[0];
            }
            $label = trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
            if ($label === '') {
                return $match[0];
            }
            $index++;
            $id = 'solution-section-'.$index;
            $items[] = ['id' => $id, 'label' => $label, 'level' => (int) $match[1]];

            return '<h'.$match[1].preg_replace('~\s+id\s*=\s*(["\']).*?\1~i', '', $match[2]).' id="'.$id.'">'.$match[3].'</h'.$match[1].'>';
        }, $html);

        return ['html' => is_string($rendered) ? $rendered : $html, 'toc' => $items ? ['rendered' => true, 'items' => $items] : ['rendered' => false, 'reason' => 'no_editorial_headings']];
    }

    private static function seo(array $seo): array
    {
        $text = fn ($v) => is_scalar($v) ? trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', Text::cms($v)))) : '';

        return ['title' => $text($seo['title'] ?? ''), 'description' => $text($seo['description'] ?? ''), 'keywords' => $text($seo['keywords'] ?? ''), 'image' => self::path($seo['image'] ?? '')];
    }

    /** Public upload/ or assets/ path only (ptsPublicSolutionsPath). */
    public static function path(mixed $path): string
    {
        $path = is_scalar($path) ? trim((string) $path) : '';
        if ($path === '' || preg_match('~^(?:https?:)?//|[?#:]|(?:^|/)\.\.(?:/|$)~i', $path) || ! preg_match('~^/?(?:upload|assets)/~i', $path)) {
            return '';
        }

        return '/'.ltrim($path, '/');
    }

    private function fallbackImage(string $slug): string
    {
        $path = self::FALLBACK_IMAGES[$slug] ?? '';

        return $path !== '' && $this->urls->exists($path) ? $path : '';
    }

    private function withImages(array $rows): array
    {
        return array_map(fn ($row) => $row + ['_photo' => $this->image($row['photo'] ?? '', 'upload/news/'), '_photo1' => $this->image($row['photo1'] ?? '', 'upload/news/')], $rows);
    }

    private function image(mixed $file, string $folder): string
    {
        return $this->looseImage($file, $folder);
    }
}
