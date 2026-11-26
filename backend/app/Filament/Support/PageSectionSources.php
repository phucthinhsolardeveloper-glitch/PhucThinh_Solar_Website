<?php

namespace App\Filament\Support;

use App\Domain\Pages\PageLayouts;
use App\Domain\Routing\PublicUrls;
use App\Filament\Pages\SiteSettingsPage;
use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\PageItems\PageItemResource;
use App\Filament\Resources\SeoPages\SeoPageResource;
use App\Filament\Resources\Solutions\SolutionResource;

/**
 * Where each Home and About layout block really gets its words and pictures, and where to edit them.
 * It mirrors HomeAssembler, AboutAssembler and the Nuxt section components: when one of those changes what
 * it reads, change the line here too.
 */
final class PageSectionSources
{
    /**
     * note: what the visitor sees comes from. sources: [type, key] pairs, type one of items (page_items section),
     * banners (placement), settings, categories, solutions, brands. anchor: element id on the public page.
     */
    private const MAP = [
        'home' => [
            'hero' => ['note' => 'Ảnh, tiêu đề và liên kết lấy theo thứ tự từ ảnh đầu trang. Chưa có ảnh nào thì trang dùng bộ ảnh có sẵn.',
                'sources' => [['banners', 'hero-carousel']], 'anchor' => 'home-title'],
            'catalog' => ['note' => 'Thẻ lấy từ danh mục gốc được đánh dấu nổi bật; các dòng "Chuỗi năng lượng" thay chữ và ảnh trên thẻ theo thứ tự, ô để trống dùng chữ có sẵn.',
                'sources' => [['categories', ''], ['items', 'home.energy_chain']], 'anchor' => 'home-catalog-title'],
            'energy' => ['note' => 'Cảnh minh họa 3D cố định trong giao diện, Admin chỉ bật, tắt và đổi vị trí. Dòng dữ liệu "Chuỗi năng lượng" thuộc khối Danh mục thiết bị.',
                'sources' => [], 'anchor' => null],
            'campaigns' => ['note' => 'Tối đa 3 ảnh chiến dịch đang hiện. Chưa tạo ảnh nào thì trang dùng 3 thẻ có sẵn; đã tạo nhưng ẩn hết thì khối trống.',
                'sources' => [['banners', 'home-campaign']], 'anchor' => 'home-campaign-title'],
            'solutions' => ['note' => 'Bốn ô cố định, mỗi ô lấy giải pháp nổi bật đang đăng có tên khớp với ô; ô không có bài khớp dùng chữ có sẵn.',
                'sources' => [['solutions', '']], 'anchor' => 'home-solutions-title'],
            'process' => ['note' => 'Tối đa 5 bước đang bật thay chữ có sẵn theo thứ tự.',
                'sources' => [['items', 'home.process']], 'anchor' => 'home-process-title'],
            'dealer' => ['note' => 'Chữ trên khối này cố định trong hệ thống, chưa sửa được trong Admin.',
                'sources' => [], 'anchor' => 'home-dealer-title'],
            'about' => ['note' => 'Tiêu đề và mô tả lấy từ dòng giới thiệu ngắn đang bật đầu tiên; tối đa 4 điểm nổi bật. Ảnh nền cố định trong giao diện.',
                'sources' => [['items', 'home.about'], ['items', 'home.about_points']], 'anchor' => 'home-about-title'],
            'contact' => ['note' => 'Hotline, email, địa chỉ và giờ làm việc lấy từ Thông tin website.',
                'sources' => [['settings', '']], 'anchor' => 'home-contact-title'],
        ],
        'about' => [
            'story' => ['note' => 'Đoạn mở đầu lấy từ dòng "Nội dung mở đầu" (ưu tiên dòng tên "Về chúng tôi"). Tiêu đề lớn và ảnh cố định trong giao diện; dòng phải có ảnh thì khung ảnh mới hiện.',
                'sources' => [['items', 'about.history']], 'anchor' => 've-phuc-thinh'],
            'history' => ['note' => 'Chữ và các mốc thời gian cố định trong giao diện. Admin thay được ảnh showroom.',
                'sources' => [['banners', 'about-showroom']], 'anchor' => 'lich-su-phat-trien'],
            'people' => ['note' => 'Lời giới thiệu lấy từ một dòng Đội ngũ, kèm tối đa 3 dòng Nguyên tắc. Chưa có nguyên tắc thì trang ghép từ Lý do chọn chúng tôi và Sứ mệnh.',
                'sources' => [['items', 'about.team'], ['items', 'about.principles']], 'anchor' => 'con-nguoi'],
            'trust' => ['note' => 'Tối đa 4 lý do đang bật. Khối ẩn khi không chọn được lý do nào.',
                'sources' => [['items', 'about.reasons']], 'anchor' => 'vi-sao-chon-chung-toi'],
            'brands' => ['note' => 'Logo của các thương hiệu đang hiện, theo thứ tự thương hiệu. Tiêu đề cố định trong giao diện.',
                'sources' => [['brands', '']], 'anchor' => 'thuong-hieu-dong-hanh'],
            'compass' => ['note' => 'Tối đa 3 dòng đang bật. Khối ẩn khi không có dòng nào.',
                'sources' => [['items', 'about.compass']], 'anchor' => 'tam-nhin-su-menh'],
            'certificates' => ['note' => 'Ảnh chứng nhận đang hiện; ảnh đánh dấu nổi bật đứng trước. Khối ẩn khi không có ảnh nào.',
                'sources' => [['banners', 'chungnhan']], 'anchor' => 'about-certificates'],
            'contact' => ['note' => 'Tiêu đề, mô tả và ảnh lấy từ ảnh "Lời mời tư vấn"; hotline và Zalo lấy từ Thông tin website.',
                'sources' => [['banners', 'about-cta'], ['settings', '']], 'anchor' => 'about-cta-title'],
        ],
    ];

    /**
     * The other pages built from page_items. They have no layout screen, so a block is only "where it comes from".
     * Same upkeep rule as MAP: mirrors DealerAssembler, Solutions*, News*, Project*, ProductExtras and their components.
     * seo: SeoMeta.page or null; path: public page, or null when the block repeats on many pages.
     */
    private const OTHER_PAGES = [
        'dealer' => ['label' => 'Đại lý', 'path' => '/dai-ly/', 'seo' => 'dai-ly', 'blocks' => [
            ['label' => 'Ảnh đầu trang', 'note' => 'Ảnh và dòng chữ trên ảnh lấy từ ảnh đầu tiên đang hiện.', 'sources' => [['banners', 'dealer-hero']]],
            ['label' => 'Mở đầu', 'note' => 'Dòng đang bật đầu tiên (theo thứ tự tạo): tiêu đề là tiêu đề lớn của trang, mô tả ngắn là đoạn giới thiệu. Ảnh của dòng này không được dùng; ảnh đầu trang nằm ở "Ảnh theo vị trí".', 'sources' => [['items', 'dealer.intro']]],
            ['label' => 'Tổng quan và sản phẩm phân phối', 'note' => 'Tiêu đề và mô tả lấy từ dòng Tổng quan đang bật đầu tiên (theo thứ tự tạo). Ảnh sản phẩm phân phối cần có tiêu đề, kèm ảnh hoặc liên kết.', 'sources' => [['items', 'dealer.overview'], ['banners', 'hinh-anh-dai-ly']]],
            ['label' => 'Hỗ trợ và quyền lợi', 'note' => 'Tiêu đề khối lấy từ dòng Hỗ trợ đang bật đầu tiên. Thẻ lấy từ 4 dòng Quyền lợi đứng đầu theo thứ tự; dòng đang tắt vẫn chiếm một chỗ trong 4, dòng không có tiêu đề không hiện.', 'sources' => [['items', 'dealer.support'], ['items', 'dealer.benefits']]],
            ['label' => 'Phản hồi đại lý', 'note' => '3 dòng đứng đầu theo thứ tự; dòng đang tắt vẫn chiếm một chỗ trong 3. Nội dung là lời trích, mô tả ngắn là dòng dưới tên.', 'sources' => [['items', 'dealer.testimonials']]],
            ['label' => 'Số liệu', 'note' => 'Tối đa 3 dòng đang bật. "Con số" chỉ nhận chữ số, dấu chấm và dấu phẩy; viết thêm chữ thì dòng không hiện.', 'sources' => [['items', 'dealer.stats']]],
        ]],
        'solutions' => ['label' => 'Giải pháp', 'path' => '/giai-phap/', 'seo' => 'giai-phap', 'blocks' => [
            ['label' => 'Mở đầu', 'note' => 'Dòng đang bật đầu tiên: tiêu đề, mô tả ngắn, ảnh; phần Nội dung thành bài dài có mục lục trên trang Giải pháp. Tiêu đề phụ không hiện. Có dòng nhưng tắt hết thì khối mở đầu ẩn.', 'sources' => [['items', 'solutions.intro']]],
            ['label' => 'Thẻ giải pháp', 'note' => 'Thẻ lấy từ các bài Giải pháp.', 'sources' => [['solutions', '']]],
            ['label' => 'Lời mời tư vấn', 'note' => 'Dòng đang bật đầu tiên, dùng ở trang Giải pháp và từng bài giải pháp. Chữ trên nút lấy từ "Tiêu đề phụ". Chưa có dòng nào thì dùng chữ có sẵn; tắt hết thì ẩn.', 'sources' => [['items', 'solutions.cta']]],
        ]],
        'news' => ['label' => 'Tin tức', 'path' => '/tin-tuc/', 'seo' => 'tin-tuc', 'blocks' => [
            ['label' => 'Mở đầu', 'note' => 'Tiêu đề và mô tả của dòng đang bật đầu tiên. Lưu ý: khi đã có dòng ở phần này, cụm bài nổi bật chỉ hiện nếu dòng đó mang cờ "hero" có từ dữ liệu cũ; form không sửa được cờ, nên dòng tạo mới đứng đầu sẽ làm cụm này ẩn.', 'sources' => [['items', 'news.intro']]],
            ['label' => 'Lời mời tư vấn', 'note' => 'Dòng đang bật đầu tiên, dùng ở trang danh sách và trang bài viết. Chữ trên nút lấy từ "Chữ trên nút". Chưa có dòng nào thì dùng chữ có sẵn; tắt hết thì ẩn.', 'sources' => [['items', 'news.cta']]],
        ]],
        'projects' => ['label' => 'Dự án', 'path' => '/du-an-da-thuc-hien/', 'seo' => 'du-an', 'blocks' => [
            ['label' => 'Mở đầu', 'note' => 'Tiêu đề và mô tả của dòng đang bật đầu tiên. Trang nhóm dự án ưu tiên tên và mô tả của nhóm.', 'sources' => [['items', 'projects.intro']]],
            ['label' => 'Lợi ích', 'note' => 'Tối đa 3 dòng đang bật. Không có dòng nào đang bật thì trang dùng 3 lợi ích có sẵn.', 'sources' => [['items', 'projects.benefits']]],
            ['label' => 'Lời mời tư vấn', 'note' => 'Dòng đang bật đầu tiên, dùng ở trang danh sách và trang dự án. Chữ trên nút lấy từ "Chữ trên nút". Chưa có dòng nào thì dùng chữ có sẵn; tắt hết thì ẩn.', 'sources' => [['items', 'projects.cta']]],
            ['label' => 'Cam kết trong lời mời', 'note' => 'Tối đa 3 dòng đang bật, nằm trong khối Lời mời tư vấn.', 'sources' => [['items', 'projects.trust']]],
        ]],
        'product' => ['label' => 'Trang sản phẩm', 'path' => null, 'seo' => null, 'blocks' => [
            ['label' => 'Cam kết', 'note' => 'Tối đa 8 dòng đang bật có tiêu đề, hiện giống nhau trên mọi trang sản phẩm. Không có dòng nào thì khối ẩn.', 'sources' => [['items', 'product.commitments']]],
        ]],
    ];

    /** Layout page key → SeoMeta.page and public path. */
    public const PAGES = ['home' => ['seo' => 'home', 'path' => '/'], 'about' => ['seo' => 'gioi-thieu', 'path' => '/gioi-thieu/']];

    public static function note(string $page, string $type): string
    {
        return self::MAP[$page][$type]['note'] ?? '';
    }

    /**
     * Edit links of one block. url is null when the signed-in user may not open that screen.
     *
     * @return list<array{label: string, url: ?string}>
     */
    public static function links(string $page, string $type): array
    {
        return array_map(fn (array $source) => self::link($source[0], $source[1]), self::MAP[$page][$type]['sources'] ?? []);
    }

    /**
     * The block on the page visitors see now, or null when the published layout hides it (or it has no id to jump to).
     * A block with no data can still be missing from the page; the link then opens the top of the page.
     */
    public static function liveUrl(string $page, string $type): ?string
    {
        $anchor = self::MAP[$page][$type]['anchor'] ?? null;
        $published = array_column(app(PageLayouts::class)->published($page), 'type');

        return $anchor !== null && in_array($type, $published, true) ? PublicUrls::base().self::PAGES[$page]['path'].'#'.$anchor : null;
    }

    /**
     * Every page built from editable blocks, for the "choose by page" list: Home and About first.
     *
     * @return list<array{label: string, blocks: list<array{label: string, links: list<array{label: string, url: ?string}>}>}>
     */
    public static function directory(): array
    {
        $out = [];
        foreach (array_keys(self::MAP) as $page) {
            $blocks = [];
            foreach (PageLayouts::PAGES[$page]['sections'] as $type => $section) {
                if (self::links($page, $type) !== []) {
                    $blocks[] = ['label' => $section['label'], 'links' => self::links($page, $type)];
                }
            }
            $out[] = ['label' => PageLayouts::PAGES[$page]['label'], 'blocks' => $blocks];
        }
        foreach (self::OTHER_PAGES as $page) {
            $out[] = ['label' => $page['label'], 'blocks' => array_map(fn (array $block) => ['label' => $block['label'],
                'links' => array_map(fn (array $source) => self::link($source[0], $source[1]), $block['sources'])], $page['blocks'])];
        }

        return $out;
    }

    /**
     * What a list filtered to one page_items section needs to say: the rule, the block's other sources, the page.
     * Null for a section that is not mapped.
     *
     * @return array{note: string, links: list<array{label: string, url: ?string}>, liveUrl: ?string}|null
     */
    public static function sectionHelp(string $section): ?array
    {
        foreach (array_keys(self::MAP) as $page) {
            if ($type = self::itemSections($page)[$section][0] ?? null) {
                return ['note' => PageLayouts::PAGES[$page]['label'].' · '.self::note($page, $type),
                    'links' => self::otherLinks(self::MAP[$page][$type]['sources'], $section, self::PAGES[$page]['seo']),
                    'liveUrl' => self::liveUrl($page, $type)];
            }
        }
        foreach (self::OTHER_PAGES as $page) {
            foreach ($page['blocks'] as $block) {
                if (in_array(['items', $section], $block['sources'], true)) {
                    return ['note' => $page['label'].' · '.$block['note'],
                        'links' => self::otherLinks($block['sources'], $section, $page['seo']),
                        'liveUrl' => $page['path'] === null ? null : PublicUrls::base().$page['path']];
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $sources
     * @return list<array{label: string, url: ?string}>
     */
    private static function otherLinks(array $sources, string $current, ?string $seo): array
    {
        $links = [];
        foreach ($sources as [$kind, $key]) {
            if ($kind !== 'items' || $key !== $current) {
                $links[] = self::link($kind, $key);
            }
        }

        return $seo === null ? $links : [...$links, self::seoLink()];
    }

    /** @return array<string, list<string>> page_items section → layout blocks reading it */
    public static function itemSections(string $page): array
    {
        $out = [];
        foreach (self::MAP[$page] ?? [] as $type => $block) {
            foreach ($block['sources'] as [$kind, $key]) {
                if ($kind === 'items') {
                    $out[$key][] = $type;
                }
            }
        }

        return $out;
    }

    /** @return array{label: string, url: ?string} */
    private static function link(string $kind, string $key): array
    {
        [$label, $allowed, $url] = match ($kind) {
            'items' => ['Nội dung: '.(PageItemResource::SECTIONS[$key][0] ?? $key), PageItemResource::canViewAny(),
                fn () => PageItemResource::getUrl('index', ['filters' => ['section' => ['value' => $key]]])],
            'banners' => ['Ảnh: '.(BannerResource::PLACEMENTS[$key] ?? $key), BannerResource::canViewAny(),
                fn () => BannerResource::getUrl('index', ['filters' => ['placement' => ['value' => $key]]])],
            'categories' => ['Danh mục sản phẩm', CategoryResource::canViewAny(), fn () => CategoryResource::getUrl()],
            'solutions' => ['Giải pháp', SolutionResource::canViewAny(), fn () => SolutionResource::getUrl()],
            'brands' => ['Thương hiệu', BrandResource::canViewAny(), fn () => BrandResource::getUrl()],
            'settings' => ['Thông tin website', SiteSettingsPage::canAccess(), fn () => SiteSettingsPage::getUrl()],
            'seo' => ['SEO của trang', SeoPageResource::canViewAny(), fn () => SeoPageResource::getUrl()],
            default => throw new \LogicException('Unknown page source: '.$kind),
        };

        return ['label' => $label, 'url' => $allowed ? $url() : null];
    }

    /** @return array{label: string, url: ?string} */
    public static function seoLink(): array
    {
        return self::link('seo', '');
    }
}
