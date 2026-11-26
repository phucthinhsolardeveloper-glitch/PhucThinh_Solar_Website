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
