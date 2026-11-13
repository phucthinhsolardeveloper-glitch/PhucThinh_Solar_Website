<?php

namespace App\Domain\Site;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Content\PostVisibility;
use App\Domain\Solar\SolarEstimator;
use App\Domain\Solutions\SolutionHub;
use App\Support\RichText;
use App\Support\SiteSettings;
use App\Support\Text;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared chrome of every page: brand, header menus, product category tree, footer, catalog benefits
 * and quick searches. Only published rows, only safe links and files.
 */
class SiteAssembler
{
    /** Header catalog menu: fixed slots pointing at categories, shown only when the category exists. */
    private const CATALOG_SLOTS = [
        ['lighting', 'Đèn pha năng lượng mặt trời', 'den-nlmt', 'den-pha-nang-luong-mat-troi'],
        ['lighting', 'Đèn đường năng lượng mặt trời', 'den-nlmt', 'den-duong'],
        ['lighting', 'Đèn sân vườn', 'den-nlmt', 'den-san-vuon'],
        ['lighting', 'Đèn UFO năng lượng mặt trời', 'den-nlmt', 'den-ufo'],
        ['lighting', 'Đèn ốp trần', 'den-nlmt', 'den-op-tran'],
        ['solar', 'Tấm pin năng lượng mặt trời', 'tam-pin-nang-luong-mat-troi', null],
        ['solar', 'Biến tần / Inverter', 'bien-tan-inverter', null],
        ['solar', 'Pin lưu trữ', 'pin-luu-tru', null],
        ['solar', 'Thiết bị & phụ kiện', 'thiet-bi-dien-nang-luong-mat-troi', null],
        ['devices', 'Bơm nước năng lượng mặt trời', 'bien-tan-bom', null],
        ['devices', 'Quạt năng lượng mặt trời', 'quat-nang-luong-mat-troi', null],
    ];

    private const ABOUT_LINKS = [
        'Về Phúc Thịnh Solar' => 'gioi-thieu/#ve-phuc-thinh',
        'Lịch sử phát triển' => 'gioi-thieu/#lich-su-phat-trien',
        'Tầm nhìn - Sứ mệnh' => 'gioi-thieu/#tam-nhin-su-menh',
        'Năng lực & chứng nhận' => 'gioi-thieu/#about-certificates',
        'Hệ thống chi nhánh' => 'lien-he/#he-thong-chi-nhanh',
        'Liên hệ' => 'lien-he/#yeu-cau-tu-van',
    ];

    private Connection $db;

    /** @var array<int, object{id: int, parent_id: ?int, name: string, slug: string}> visible categories holding visible products */
    private array $shown = [];

    /** @var array<int, list<object>> parent id (0 = top) => shown children in Admin order */
    private array $children = [];

    public function __construct(private readonly ProductMediaUrls $urls, private readonly SiteSettings $settings, private readonly CategoryTree $categories)
    {
        $this->db = DB::connection();
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        foreach ($this->db->table('categories')->where('is_visible', true)->orderBy('depth')->orderBy('sort_order')->orderByDesc('id')->get(['id', 'parent_id', 'name', 'slug']) as $row) {
            if ($this->categories->hasVisibleProducts((int) $row->id)) {
                $this->shown[(int) $row->id] = $row;
                $this->children[(int) $row->parent_id][] = $row;
            }
        }
        $tops = $this->children[0] ?? [];

        $options = $this->settings->options();
        $picture = fn (string $placement, bool $published) => $this->db->table('banners')->where('placement', $placement)
            ->when($published, fn ($q) => $q->where('is_visible', true))->orderBy('sort_order')->orderByDesc('id')->first(['image', 'link']);
        $badge = $picture('bct', true);

        $brand = [
            'name' => Text::flat($this->settings->siteName()) ?: 'Phúc Thịnh Solar',
            'hotline' => Text::flat($options['hotline'] ?? ''),
            'logo' => $this->asset($picture('logo', false)?->image),
            'logoFooter' => $this->asset($picture('logo-footer', false)?->image),
        ];
        $zalo = (string) preg_replace('/[^0-9]/', '', is_scalar($options['zalo'] ?? null) ? trim((string) $options['zalo']) : '');
        if (strlen($zalo) >= 8 && strlen($zalo) <= 15) {
            $brand['zaloHref'] = 'https://zalo.me/'.$zalo;
        }
        $technical = Text::flat($options['hotline_technical'] ?? '');
        if ($technical !== '') {
            $brand['technicalHotline'] = $technical;
        }
        $favicon = $this->asset($picture('favicon', true)?->image);
        if ($favicon !== '') {
            $brand['favicon'] = $favicon;
        }

        $categories = [];
        foreach ($tops as $row) {
            $label = trim((string) $row->name);
            $slug = self::slug($row);
            if ($label !== '' && $slug !== '') {
                $categories[] = ['id' => (int) $row->id, 'name' => $label, 'slug' => $slug, 'url' => '/danh-muc-san-pham/'.rawurlencode($slug).'/'];
            }
        }

        return [
            'schemaVersion' => 1,
            'brand' => $brand,
            'productCategories' => $categories,
            'navigationSchemaVersion' => 1,
            'locale' => 'vi',
            'navigation' => $this->navigation(),
            'catalog' => $this->catalogMenu($tops),
            'productCategoryTree' => array_values(array_filter(array_map(fn ($row) => $this->node($row), $tops))),
            'footer' => $this->footer($options, $badge),
            'catalogBenefits' => $this->sectionTitles('catalog.benefits', 4)->map(fn ($row) => ['title' => Text::excerpt($row->title, 60), 'description' => Text::excerpt($row->summary, 90)])->all(),
            'quickSearches' => $this->sectionTitles('search.suggestions', 7)->map(fn ($row) => Text::excerpt($row->title, 48))->filter()->values()->all(),
            // Pages linking to /uoc-tinh-dien-mat-troi/ show the link only while the owner has it switched on.
            'estimator' => app(SolarEstimator::class)->enabled(),
            // Google Tag Manager container set in Admin → Thông tin website; empty means no tag on the site.
            'gtmId' => preg_match('/^GTM-[A-Z0-9]{4,12}$/', $gtm = strtoupper(trim((string) ($options['gtm_id'] ?? '')))) ? $gtm : '',
            // Google Analytics 4 measurement id loaded directly (gtag.js), for when the GTM container has no GA4 tag.
            'ga4Id' => preg_match('/^G-[A-Z0-9]{6,12}$/', $ga4 = strtoupper(trim((string) ($options['ga4_id'] ?? '')))) ? $ga4 : '',
        ];
    }

    /** @return Collection<int, \stdClass> */
    private function sectionTitles(string $section, int $limit): Collection
    {
        return $this->db->table('page_items')->where('section', $section)->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')->limit($limit)->get(['title', 'summary']);
    }

    /** @return list<array<string, mixed>> */
    private function navigation(): array
    {
        $about = [];
        foreach (self::ABOUT_LINKS as $label => $href) {
            $about[] = self::link($label, $href);
        }
        $nav = [
            ['label' => 'Danh mục sản phẩm', 'href' => '/san-pham/', 'children' => []],
            ['label' => 'Giới thiệu', 'href' => '/gioi-thieu/', 'children' => array_values(array_filter($about))],
            ['label' => 'Giải pháp', 'href' => '/giai-phap/', 'children' => []],
            ['label' => 'Dự án', 'href' => '/du-an-da-thuc-hien/', 'children' => []],
            ['label' => 'Kiến thức', 'href' => '/tin-tuc/', 'children' => []],
            ['label' => 'Hợp tác', 'href' => '/dai-ly/', 'children' => []],
        ];

        // Solutions marked for the hub, or every published one when none is marked.
        $links = [self::link('Tất cả giải pháp', '/giai-phap/')];
        foreach (SolutionHub::select(SolutionHub::solutions($this->db), true) as $row) {
            $links[] = self::link(Text::flat($row['nav_title'] !== '' ? $row['nav_title'] : $row['title']), 'giai-phap/'.trim($row['slug'], '/').'/');
        }
        $nav[2]['children'] = array_values(array_filter($links));

        // Knowledge menu: article groups with published articles, then their sub-groups.
        $groups = $this->db->table('post_categories as c')->where('c.kind', 'article')->where('c.depth', 1)->where('c.is_visible', true)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('posts as p')->whereColumn('p.category_id', 'c.id')->where('p.kind', 'article')->where(PostVisibility::live('p.')))
            ->orderBy('c.sort_order')->orderByDesc('c.id')->get(['c.id', 'c.name', 'c.slug']);
        $subGroups = $this->db->table('post_categories')->whereIn('kind', ['article', 'solution'])->where('depth', '>', 1)->where('is_visible', true)
            ->orderBy('sort_order')->orderByDesc('id')->get(['id', 'parent_id', 'name', 'slug'])->groupBy('parent_id');
        $branch = function (object $group, string $href) use (&$branch, $subGroups): array {
            $link = self::link($group->name, $href);
            if (! $link) {
                return [];
            }
            $link['children'] = [];
            foreach ($subGroups->get($group->id, []) as $child) {
                if ($childLink = $branch($child, self::slug($child))) {
                    $link['children'][] = $childLink;
                }
            }

            return $link;
        };
        $children = [self::link('Tất cả bài viết', '/tin-tuc/')];
        foreach ($groups as $group) {
            $children[] = $branch($group, 'category/'.self::slug($group).'/');
        }
        $nav[4]['children'] = array_values(array_filter($children));

        return $nav;
    }

    /** Header catalog menu: fixed slots pointing at shown categories. */
    private function catalogMenu(array $tops): array
    {
        $bySlug = [];
        foreach ($tops as $row) {
            $bySlug[self::slug($row)] = $row;
        }
        $menu = [
            'lighting' => ['label' => 'Chiếu sáng', 'href' => '', 'items' => []],
            'solar' => ['label' => 'Điện mặt trời', 'href' => '', 'items' => []],
            'devices' => ['label' => 'Thiết bị ứng dụng', 'href' => '', 'items' => []],
        ];
        foreach (self::CATALOG_SLOTS as [$group, $label, $listSlug, $catSlug]) {
            if (! isset($bySlug[$listSlug])) {
                continue;
            }
            $list = $bySlug[$listSlug];
            $href = $catSlug !== null ? '' : $this->path($list);
            $children = [];
            foreach ($this->children[(int) $list->id] ?? [] as $cat) {
                if ($catSlug !== null) {
                    if (self::slug($cat) !== $catSlug) {
                        continue;
                    }
                    $href = $this->path($cat);
                    foreach ($this->children[(int) $cat->id] ?? [] as $item) {
                        $children[] = self::link($item->name, $this->path($item));
                    }
                    break;
                }
                $children[] = self::link($cat->name, $this->path($cat));
            }
            if ($href === '') {
                continue;
            }
            $menu[$group]['href'] = self::url($menu[$group]['href'] ?: $href);
            $menu[$group]['items'][] = ['label' => $label, 'href' => self::url($href), 'children' => array_values(array_filter($children))];
        }

        return $menu;
    }

    /** One category of the product tree with its shown descendants; null when it has no name or slug. */
    private function node(object $row): ?array
    {
        $node = ['id' => (int) $row->id, 'name' => Text::flat($row->name), 'slug' => self::slug($row), 'url' => self::url($this->path($row)), 'children' => []];
        if ($node['name'] === '' || $node['slug'] === '') {
            return null;
        }
        foreach ($this->children[(int) $row->id] ?? [] as $child) {
            if ($childNode = $this->node($child)) {
                $node['children'][] = $childNode;
            }
        }

        return $node;
    }

    private function footer(array $options, ?object $badge): array
    {
        $footer = $this->db->table('page_items')->where('section', 'site.footer')->where('is_visible', true)->first(['title', 'body']);
        $policies = [];
        foreach ($this->db->table('posts')->where('kind', 'policy')->where(PostVisibility::live())->orderBy('sort_order')->orderByDesc('id')->get(['title', 'slug']) as $row) {
            if ($link = self::link($row->title, 'ho-tro/'.self::slug($row).'/')) {
                $policies[] = $link;
            }
        }
        $locations = [];
        $branches = $this->db->table('store_locations')->where('is_visible', true)
            ->orderByRaw("case when name like 'Trụ sở%' then 0 when name like 'Kho Gia Lai%' then 1 else 2 end")->orderBy('sort_order')->orderByDesc('id')->get(['name', 'address', 'map_url']);
        foreach ($branches as $row) {
            $name = Text::flat($row->name);
            $address = Text::flat($row->address);
            if ($name !== '' && $address !== '') {
                $locations[] = ['name' => $name, 'address' => $address, 'mapUrl' => self::url($row->map_url)];
            }
        }
        // Only the accounts the owner filled in are shown, in the order SocialNetworks lists them.
        // `kind` travels with the link so the footer picks an icon by identity, not by sniffing the URL.
        $social = [];
        $hidden = SocialNetworks::hidden($options);
        foreach (SocialNetworks::NETWORKS as $key => [$label, $kind]) {
            $href = in_array($key, $hidden, true) ? '' : self::url($options[$key] ?? '');
            if ($href !== '') {
                $social[] = ['label' => $label, 'href' => $href, 'kind' => $kind];
            }
        }
        // The contact cards show the hotlines, so tel: links and the tax-code line are taken out of the CMS text.
        $content = RichText::sanitize((string) $footer?->body);
        $content = (string) preg_replace('/MST:\s*5901151081\b.*?(?:<br\s*\/?>|$)/iu', '', $content, 1);
        $content = (string) preg_replace('~<a\b(?=[^>]*\bhref="tel:)[^>]*>.*?</a>~is', '', $content);
        $dto = [
            'companyName' => Text::flat((string) $footer?->title),
            'contactText' => RichText::dto($content),
            'policies' => $policies,
            'locations' => $locations,
            'socialLinks' => $social,
        ];
        $image = self::url($this->asset($badge?->image));
        $href = self::url(self::url($badge?->link));
        if ($image !== '' && $href !== '') {
            $dto['trustBadge'] = ['image' => $image, 'href' => $href];
        }

        return $dto;
    }

    /** danh-muc-san-pham/<top>/<child>/... through shown ancestors only; '' when one is hidden or empty. */
    private function path(object $row): string
    {
        $parts = [];
        for ($node = $row; $node; $node = $node->parent_id ? ($this->shown[(int) $node->parent_id] ?? false) : null) {
            $slug = self::slug($node);
            if ($slug === '') {
                return '';
            }
            array_unshift($parts, $slug);
        }
        if ($node === false) {
            return '';
        }

        return 'danh-muc-san-pham/'.implode('/', $parts).'/';
    }

    /** A file name under upload/photo with an image extension that exists. */
    private function asset(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || basename($value) !== $value || ! preg_match('/\.(?:png|jpe?g|gif|svg|webp)$/i', $value)) {
            return '';
        }

        return $this->urls->exists('upload/photo/'.$value) ? '/upload/photo/'.$value : '';
    }

    private static function slug(object $row): string
    {
        return trim(trim((string) $row->slug), '/');
    }

    /** Site-relative or http(s) link; anything else becomes the fallback. */
    public static function url(mixed $value, string $fallback = ''): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || preg_match('~^(?:javascript|data|vbscript):~i', $value) || preg_match('~[\x00-\x20<>"\']~', $value) || str_contains($value, '..')) {
            return $fallback;
        }
        if (preg_match('~^(?:https?:)?//~i', $value)) {
            return preg_match('~^https?://~i', $value) ? $value : $fallback;
        }

        return '/'.ltrim($value, '/');
    }

    /** @return array{label: string, href: string}|array{} */
    private static function link(mixed $label, string $href): array
    {
        $label = Text::flat($label);
        $href = self::url($href);

        return $label === '' || $href === '' ? [] : ['label' => $label, 'href' => $href];
    }
}
