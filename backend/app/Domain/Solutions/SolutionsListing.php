<?php

namespace App\Domain\Solutions;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Content\PostVisibility;
use App\Domain\Pages\PageSupport;
use App\Models\PostCategory;
use App\Support\RichText;
use App\Support\SiteSettings;
use App\Support\Text;

/**
 * Solutions hub: intro, featured solution cards, equipment shortcuts, CTA, editorial text with a
 * table of contents, and an optional category view.
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
        $solutions = $this->withImages(SolutionHub::solutions($this->db));
        $introRows = $this->withImages($this->section('solutions.intro'));
        $ctaRows = $this->withImages($this->section('solutions.cta'));
        $products = array_map(fn ($row) => $row + ['_image' => $this->looseImage($row['image'], 'upload/product/')], SolutionHub::productGroups($this->db));
        $groups = $this->db->table('post_categories as c')->where('c.kind', 'solution')->where('c.is_visible', true)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('posts as p')->whereColumn('p.category_id', 'c.id')->where('p.kind', 'solution')->where(PostVisibility::live('p.'))->where('p.slug', '<>', ''))
            ->orderBy('c.sort_order')->orderByDesc('c.id')->get(['c.id', 'c.name', 'c.summary', 'c.slug', 'c.image'])->map(fn ($row) => (array) $row)->all();

        $data = $this->payload($solutions, $introRows, $ctaRows, $products);
        if (isset($get['category'])) {
            $group = collect($groups)->firstWhere('slug', $get['category']);
            $category = null;
            if ($group) {
                $seoRow = $this->db->table('seo_metas')->where('owner_type', PostCategory::class)->where('owner_id', $group['id'])->first(['title', 'description', 'keywords']);
                $category = $this->category($group, $groups, $solutions, [
                    'title' => Text::flat((string) $seoRow?->title),
                    'description' => Text::flat((string) $seoRow?->description),
                    'keywords' => Text::flat((string) $seoRow?->keywords),
                    'image' => $this->urls->visual((string) $group['image'], 'upload/news/'),
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
        $featured = SolutionHub::select($solutions, true);
        $cards = [];
        foreach ($featured as $row) {
            $slug = Text::cms($row['slug']);
            if ($slug === '') {
                continue;
            }
            $cards[] = ['title' => Text::cms($row['nav_title']) ?: Text::cms($row['title']), 'description' => Text::cms($row['summary']), 'slug' => $slug, 'url' => '/giai-phap/'.$slug.'/',
                'image' => self::path($row['_cover']) ?: (self::path($row['_image']) ?: self::path($this->fallbackImage($slug))), 'actionLabel' => $ui['choice']];
        }

        $introRow = SolutionHub::select($introRows, false, 1)[0] ?? null;
        $editorial = self::editorialToc($introRow ? RichText::sanitize($introRow['body']) : '');
        $intro = null;
        $title = $ui['title'];
        if (! $introRows || $introRow) {
            $row = $introRow ?? ['title' => '', 'subtitle' => '', 'summary' => '', '_image' => ''];
            $title = Text::cms($row['title']) ?: $ui['title'];
            $hero = self::path($row['_image']);
            if (! $introRows && $hero === '') {
                foreach ($featured as $candidate) {
                    if (Text::cms($candidate['slug']) !== '' && ($hero = self::path($candidate['_cover']) ?: self::path($candidate['_image'])) !== '') {
                        break;
                    }
                }
            }
            $intro = [
                'eyebrow' => Text::cms($row['subtitle']) ?: $ui['eyebrow'],
                'title' => $title,
                'description' => Text::cms($row['summary']) ?: $ui['intro'],
                'image' => $hero,
                'links' => ['consult' => ['label' => $ui['consult'], 'href' => '#ptsContactModal'], 'explore' => ['label' => $ui['choices'], 'href' => '/giai-phap/#cac-giai-phap']],
            ];
        }

        $equipment = [];
        foreach (SolutionHub::select($products, true, 4) as $row) {
            $slug = trim($row['slug'], '/');
            $equipment[] = ['name' => Text::cms($row['name']), 'description' => Text::cms($row['summary']),
                'url' => '/danh-muc-san-pham/'.($slug !== '' ? $slug.'/' : ''), 'image' => self::path($row['_image'])];
        }

        $cta = null;
        $ctaRow = SolutionHub::select($ctaRows, false, 1)[0] ?? null;
        if (! $ctaRows || $ctaRow) {
            $row = $ctaRow ?? ['title' => '', 'subtitle' => '', 'summary' => '', '_image' => ''];
            $hotline = is_scalar($this->settings->options()['hotline'] ?? null) ? trim((string) $this->settings->options()['hotline']) : '';
            $tel = (string) preg_replace('/[^0-9+]/', '', $hotline);
            $button = Text::cms($row['subtitle']) ?: $ui['consult'];
            $cta = [
                'title' => Text::cms($row['title']) ?: $ui['contact'],
                'description' => Text::cms($row['summary']) ?: $ui['contact_desc'],
                'buttonLabel' => $button,
                'image' => self::path($row['_image']) ?: self::path(self::CTA_FALLBACK_IMAGE),
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
        $slug = Text::slug($group['slug']);
        $name = Text::cms($group['name']);
        if ($slug === '' || $name === '') {
            return null;
        }
        $cards = [];
        foreach (SolutionHub::select($solutions) as $row) {
            $itemSlug = Text::slug($row['slug']);
            if ($row['category_id'] !== $id || $itemSlug === '') {
                continue;
            }
            $cards[] = ['title' => Text::cms($row['title']), 'description' => Text::cms($row['summary']), 'slug' => $itemSlug, 'url' => '/giai-phap/'.$itemSlug.'/',
                'image' => self::path($row['_image']), 'actionLabel' => 'Xem giải pháp'];
        }
        if (! $cards) {
            return null;
        }
        // Each group links to its featured solution when it has one, else to its category page.
        $featured = SolutionHub::select($solutions, true);
        $navigation = [];
        foreach ($groups as $nav) {
            $navSlug = Text::slug($nav['slug']);
            $navId = (int) $nav['id'];
            $navName = Text::cms($nav['name']);
            if ($navSlug === '' || $navName === '') {
                continue;
            }
            $url = '/danh-muc-giai-phap/'.$navSlug.'/';
            foreach ($featured as $row) {
                if ($row['category_id'] === $navId) {
                    $representative = Text::slug($row['slug']);
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
            'description' => Text::cms((string) $group['summary']) ?: 'Khám phá các giải pháp phù hợp với nhu cầu thực tế và quy mô công trình của bạn.',
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

    /** Public upload/ or assets/ path only. */
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

    /** Adds the existing upload/news path of the picture (_image) and cover (_cover). */
    private function withImages(array $rows): array
    {
        return array_map(fn ($row) => $row + ['_image' => $this->looseImage($row['image'] ?? '', 'upload/news/'), '_cover' => $this->looseImage($row['cover_image'] ?? '', 'upload/news/')], $rows);
    }
}
