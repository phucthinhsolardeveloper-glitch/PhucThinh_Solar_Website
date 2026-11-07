<?php

namespace App\Domain\Site;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Legacy\Content\RichText;
use App\Legacy\Support\LegacyText;
use App\Support\SiteSettings;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Shared chrome of every page (api/pts_v1_site.php): brand, header menus, product category tree,
 * footer, catalog benefits and quick searches. Only published rows, only safe links and files.
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

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $index = [];

    public function __construct(private readonly ProductMediaUrls $urls, private readonly SiteSettings $settings, private readonly CategoryTree $categories)
    {
        $this->db = DB::connection();
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        // Visible categories that hold visible products, per depth, in Admin order.
        $level = fn (int $depth, string $parentKey) => array_values(array_map(
            fn ($row) => ['id' => (int) $row->id, 'namevi' => $row->name, 'slugvi' => $row->slug, $parentKey => (int) $row->parent_id],
            array_filter(DB::table('categories')->where('depth', $depth)->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')->get()->all(),
                fn ($row) => $this->categories->hasVisibleProducts((int) $row->id))));
        $lists = $level(1, 'parent');
        $cats = $level(2, 'id_list');
        $items = $level(3, 'id_cat');
        $subs = $level(4, 'id_item');
        foreach (['list' => $lists, 'cat' => $cats, 'item' => $items, 'sub' => $subs] as $level => $rows) {
            foreach ($rows as $row) {
                if ((int) $row['id'] > 0) {
                    $this->index[$level][(int) $row['id']] = $row;
                }
            }
        }

        $options = $this->settings->options();
        $photo = fn (string $placement, bool $published) => (array) ($this->db->selectOne(
            'select image as photo, link from banners where placement=?'.($published ? ' and is_visible=1' : '').' order by sort_order, id desc limit 1', [$placement]) ?? []);
        $badge = $photo('bct', true);

        $brand = [
            'name' => LegacyText::flat($this->settings->siteName()) ?: 'Phúc Thịnh Solar',
            'hotline' => LegacyText::flat($options['hotline'] ?? ''),
            'logo' => $this->asset($photo('logo', false)['photo'] ?? ''),
            'logoFooter' => $this->asset($photo('logo-footer', false)['photo'] ?? ''),
        ];
        $zalo = (string) preg_replace('/[^0-9]/', '', is_scalar($options['zalo'] ?? null) ? trim((string) $options['zalo']) : '');
        if (strlen($zalo) >= 8 && strlen($zalo) <= 15) {
            $brand['zaloHref'] = 'https://zalo.me/'.$zalo;
        }
        $technical = LegacyText::flat($options['hotline_technical'] ?? '');
        if ($technical !== '') {
            $brand['technicalHotline'] = $technical;
        }
        $favicon = $this->asset($photo('favicon', true)['photo'] ?? '');
        if ($favicon !== '') {
            $brand['favicon'] = $favicon;
        }

        $categories = [];
        foreach ($lists as $row) {
            $label = trim((string) $row['namevi']);
            $slug = trim((string) $row['slugvi'], '/');
            if ((int) $row['id'] > 0 && $label !== '' && $slug !== '') {
                $categories[] = ['id' => (int) $row['id'], 'name' => $label, 'slug' => $slug, 'url' => '/danh-muc-san-pham/'.rawurlencode($slug).'/'];
            }
        }

        return [
            'schemaVersion' => 1,
            'brand' => $brand,
            'productCategories' => $categories,
            'navigationSchemaVersion' => 1,
            'locale' => 'vi',
            'navigation' => $this->navigation(),
            'catalog' => $this->catalogMenu($lists, $cats, $items),
            'productCategoryTree' => $this->tree($lists, $cats, $items, $subs),
            'footer' => $this->footer($options, $badge),
            'catalogBenefits' => array_map(fn ($row) => ['title' => LegacyText::excerpt($row['namevi'], 60), 'description' => LegacyText::excerpt($row['descvi'], 90)],
                $this->rows("select title as namevi, summary as descvi from page_items where section='catalog.benefits' and is_visible=1 order by sort_order,id desc limit 0,4")),
            'quickSearches' => array_values(array_filter(array_map(fn ($row) => LegacyText::excerpt($row['namevi'], 48),
                $this->rows("select title as namevi from page_items where section='search.suggestions' and is_visible=1 order by sort_order,id desc limit 0,7")))),
        ];
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

        // Solutions: those ticked for the hub menu, or all published ones when none is ticked.
        $solutions = $this->rows("select id, title as namevi, nav_title as title_indexvi, slug as slugvi, concat_ws(',', if(is_visible,'hienthi',null), if(in_hub,'solutionhub',null)) as status from posts where kind='solution' order by sort_order,id desc");
        $flags = fn ($row) => explode(',', is_scalar($row['status']) ? trim((string) $row['status']) : '');
        $hubOnly = (bool) array_filter($solutions, fn ($row) => in_array('solutionhub', $flags($row), true));
        $links = [self::link('Tất cả giải pháp', '/giai-phap/')];
        foreach ($solutions as $row) {
            if (! in_array('hienthi', $flags($row), true) || ($hubOnly && ! in_array('solutionhub', $flags($row), true))) {
                continue;
            }
            $label = LegacyText::flat(($row['title_indexvi'] ?? '') !== '' ? $row['title_indexvi'] : $row['namevi']);
            $links[] = self::link($label, 'giai-phap/'.self::slug($row).'/');
        }
        $nav[2]['children'] = array_values(array_filter($links));

        $newsLists = $this->rows("select c.name as namevi, c.slug as slugvi, c.id from post_categories c where c.kind='article' and c.depth=1 and c.is_visible=1"
            ." and exists (select 1 from posts p where p.category_id=c.id and p.kind='article' and p.is_visible=1) order by c.sort_order,c.id desc");
        $level = fn (int $depth, string $parent) => $this->rows("select name as namevi, slug as slugvi, id, parent_id as {$parent} from post_categories where kind in ('article','solution') and depth={$depth} and is_visible=1 order by sort_order,id desc");
        $newsCats = $level(2, 'id_list');
        $newsItems = $level(3, 'id_cat');
        $newsSubs = $level(4, 'id_item');
        $children = [self::link('Tất cả bài viết', '/tin-tuc/')];
        foreach ($newsLists as $list) {
            $listLink = self::link($list['namevi'], 'category/'.self::slug($list).'/');
            if (! $listLink) {
                continue;
            }
            $listLink['children'] = [];
            foreach ($newsCats as $cat) {
                if ((int) $cat['id_list'] !== (int) $list['id'] || ! ($catLink = self::link($cat['namevi'], self::slug($cat)))) {
                    continue;
                }
                $catLink['children'] = [];
                foreach ($newsItems as $item) {
                    if ((int) $item['id_cat'] !== (int) $cat['id'] || ! ($itemLink = self::link($item['namevi'], self::slug($item)))) {
                        continue;
                    }
                    $itemLink['children'] = [];
                    foreach ($newsSubs as $sub) {
                        if ((int) $sub['id_item'] === (int) $item['id'] && ($subLink = self::link($sub['namevi'], self::slug($sub)))) {
                            $itemLink['children'][] = $subLink;
                        }
                    }
                    $catLink['children'][] = $itemLink;
                }
                $listLink['children'][] = $catLink;
            }
            $children[] = $listLink;
        }
        $nav[4]['children'] = array_values(array_filter($children));

        return $nav;
    }

    private function catalogMenu(array $lists, array $cats, array $items): array
    {
        $bySlug = [];
        foreach ($lists as $row) {
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
            $href = $catSlug !== null ? '' : $this->path($list, 'list');
            $children = [];
            foreach ($cats as $cat) {
                if ((int) $cat['id_list'] !== (int) $list['id']) {
                    continue;
                }
                if ($catSlug !== null) {
                    if (self::slug($cat) !== $catSlug) {
                        continue;
                    }
                    $href = $this->path($cat, 'cat');
                    foreach ($items as $item) {
                        if ((int) $item['id_cat'] === (int) $cat['id']) {
                            $children[] = self::link($item['namevi'], $this->path($item, 'item'));
                        }
                    }
                    break;
                }
                $children[] = self::link($cat['namevi'], $this->path($cat, 'cat'));
            }
            if ($href === '') {
                continue;
            }
            $menu[$group]['href'] = self::url($menu[$group]['href'] ?: $href);
            $menu[$group]['items'][] = ['label' => $label, 'href' => self::url($href), 'children' => array_values(array_filter($children))];
        }

        return $menu;
    }

    private function tree(array $lists, array $cats, array $items, array $subs): array
    {
        $group = function (array $rows, string $parent) {
            $out = [];
            foreach ($rows as $row) {
                $out[(int) $row[$parent]][] = $row;
            }

            return $out;
        };
        $byList = $group($cats, 'id_list');
        $byCat = $group($items, 'id_cat');
        $byItem = $group($subs, 'id_item');
        $node = fn (array $row, string $level) => ['id' => (int) $row['id'], 'name' => LegacyText::flat($row['namevi']), 'slug' => self::slug($row), 'url' => self::url($this->path($row, $level)), 'children' => []];
        $valid = fn (array $n) => $n['id'] > 0 && $n['name'] !== '' && $n['slug'] !== '';
        $tree = [];
        foreach ($lists as $list) {
            $listNode = $node($list, 'list');
            if (! $valid($listNode)) {
                continue;
            }
            foreach ($byList[$listNode['id']] ?? [] as $cat) {
                $catNode = $node($cat, 'cat');
                if (! $valid($catNode)) {
                    continue;
                }
                foreach ($byCat[$catNode['id']] ?? [] as $item) {
                    $itemNode = $node($item, 'item');
                    if (! $valid($itemNode)) {
                        continue;
                    }
                    foreach ($byItem[$itemNode['id']] ?? [] as $sub) {
                        $subNode = $node($sub, 'sub');
                        if ($valid($subNode)) {
                            $itemNode['children'][] = $subNode;
                        }
                    }
                    $catNode['children'][] = $itemNode;
                }
                $listNode['children'][] = $catNode;
            }
            $tree[] = $listNode;
        }

        return $tree;
    }

    private function footer(array $options, array $badge): array
    {
        $footer = (array) ($this->db->selectOne("select title as namevi, body as contentvi from page_items where section='site.footer' and is_visible=1 limit 0,1") ?? []);
        $policies = [];
        foreach ($this->rows("select title as namevi, slug as slugvi, id from posts where kind='policy' and is_visible=1 order by sort_order,id desc") as $row) {
            if ($link = self::link($row['namevi'], 'ho-tro/'.self::slug($row).'/')) {
                $policies[] = $link;
            }
        }
        $locations = [];
        foreach ($this->rows('select name as namevi, address, id, map_url as ggmap from store_locations where is_visible=1'
            ." order by case when name like 'Trụ sở%' then 0 when name like 'Kho Gia Lai%' then 1 else 2 end,sort_order,id desc") as $row) {
            $name = LegacyText::flat($row['namevi']);
            $address = LegacyText::flat($row['address']);
            if ($name !== '' && $address !== '') {
                $locations[] = ['name' => $name, 'address' => $address, 'mapUrl' => self::url($row['ggmap'])];
            }
        }
        $social = [];
        foreach (['fanpage' => 'Facebook', 'youtube' => 'YouTube'] as $key => $label) {
            $href = self::url($options[$key] ?? '');
            if ($href !== '') {
                $social[] = ['label' => $label, 'href' => $href];
            }
        }
        // The contact cards show the hotlines, so tel: links and the tax-code line are taken out of the CMS text.
        $content = RichText::sanitize($footer['contentvi'] ?? '');
        $content = (string) preg_replace('/MST:\s*5901151081\b.*?(?:<br\s*\/?>|$)/iu', '', $content, 1);
        $content = (string) preg_replace('~<a\b(?=[^>]*\bhref="tel:)[^>]*>.*?</a>~is', '', $content);
        $dto = [
            'companyName' => LegacyText::flat($footer['namevi'] ?? ''),
            'contactText' => RichText::dto($content),
            'policies' => $policies,
            'locations' => $locations,
            'socialLinks' => $social,
        ];
        $image = self::url($this->asset($badge['photo'] ?? ''));
        $href = self::url(self::url($badge['link'] ?? ''));
        if ($image !== '' && $href !== '') {
            $dto['trustBadge'] = ['image' => $image, 'href' => $href];
        }

        return $dto;
    }

    /** danh-muc-san-pham/<list>/<cat>/... through published ancestors only (ptsPublicNavProductPath). */
    private function path(array $row, string $level): string
    {
        $id = (int) $row['id'];
        if (! isset($this->index[$level][$id])) {
            return '';
        }
        $parents = ['list' => null, 'cat' => ['list', 'id_list'], 'item' => ['cat', 'id_cat'], 'sub' => ['item', 'id_item']];
        $chain = [];
        $current = $this->index[$level][$id];
        $currentLevel = $level;
        while (true) {
            array_unshift($chain, $current);
            if ($parents[$currentLevel] === null) {
                break;
            }
            [$parentLevel, $column] = $parents[$currentLevel];
            $parentId = (int) ($current[$column] ?? 0);
            if (! isset($this->index[$parentLevel][$parentId])) {
                return '';
            }
            $current = $this->index[$parentLevel][$parentId];
            $currentLevel = $parentLevel;
        }
        $parts = ['danh-muc-san-pham'];
        foreach ($chain as $ancestor) {
            $slug = self::slug($ancestor);
            if ($slug === '') {
                return '';
            }
            $parts[] = $slug;
        }

        return implode('/', $parts).'/';
    }

    /** A file name under upload/photo with an image extension that exists (ptsPublicNavAsset). */
    private function asset(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || basename($value) !== $value || ! preg_match('/\.(?:png|jpe?g|gif|svg|webp)$/i', $value)) {
            return '';
        }

        return $this->urls->exists('upload/photo/'.$value) ? '/upload/photo/'.$value : '';
    }

    /** @return list<array<string, mixed>> */
    private function rows(string $sql): array
    {
        return array_map(fn ($row) => (array) $row, $this->db->select($sql));
    }

    private static function slug(array $row): string
    {
        return trim(is_scalar($row['slugvi'] ?? null) ? trim((string) $row['slugvi']) : '', '/');
    }

    /** Site-relative or http(s) link; anything else becomes the fallback (ptsPublicNavUrl). */
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
        $label = LegacyText::flat($label);
        $href = self::url($href);

        return $label === '' || $href === '' ? [] : ['label' => $label, 'href' => $href];
    }
}
