<?php

namespace App\Domain\Catalog\Listing;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Models\Category;
use App\Support\Text;
use Illuminate\Database\Connection;

/**
 * Category landing extras of the catalog payload: hero picture, "choose by
 * need" cards, FAQ, support banner, SEO and canonical URL.
 */
final class CatalogLanding
{
    /**
     * Stock hero pictures for category paths without one in Admin.
     */
    private const HERO_FALLBACKS = [
        'san-pham' => ['catalog-products-hero-20261007-v2.png', 'Danh mục thiết bị năng lượng mặt trời Phúc Thịnh Solar'],
        'danh-muc-san-pham/den-nlmt' => ['catalog-lighting-overview-20261008.png', 'Tổng quan giải pháp chiếu sáng năng lượng mặt trời'],
        'danh-muc-san-pham/den-nlmt/den-duong' => ['catalog-solar-street-lighting-20261008.png', 'Đèn đường năng lượng mặt trời cho đường phố và lối đi'],
        'danh-muc-san-pham/den-nlmt/den-pha-nang-luong-mat-troi' => ['catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'],
        'danh-muc-san-pham/den-nlmt/den-pha' => ['catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'],
        'danh-muc-san-pham/den-nlmt/den-san-vuon' => ['catalog-solar-garden-lighting-20261008-v2.png', 'Đèn sân vườn năng lượng mặt trời cho nhà ở và khuôn viên'],
        'danh-muc-san-pham/den-nlmt/den-ufo' => ['catalog-solar-ufo-highbay-20261008-v2.png', 'Đèn UFO năng lượng mặt trời cho nhà xưởng và không gian cao'],
        'danh-muc-san-pham/den-nlmt/den-op-tran' => ['catalog-solar-ceiling-lighting-20261008.png', 'Đèn ốp trần năng lượng mặt trời cho hiên nhà và hành lang'],
        'danh-muc-san-pham/tru-den/tru-den-duong' => ['catalog-solar-street-poles-20261008.png', 'Trụ đèn đường cho hệ thống chiếu sáng ngoài trời'],
        'danh-muc-san-pham/tru-den/tru-den-trang-tri' => ['catalog-decorative-lamp-posts-20261008.png', 'Trụ đèn trang trí cho sân vườn và khuôn viên'],
        'danh-muc-san-pham/tru-den/can-den-gia-do' => ['catalog-street-light-arms-20261008.png', 'Cần đèn và giá đỡ cho hệ thống chiếu sáng'],
        'danh-muc-san-pham/tru-den/mong-de-tru' => ['catalog-street-pole-foundations-20261008.png', 'Móng và đế trụ cho công trình chiếu sáng'],
        'danh-muc-san-pham/tam-pin-nang-luong-mat-troi/kep-tam-pin' => ['catalog-utility-solar-mounting-20261008.png', 'Kẹp tấm pin và phụ kiện cố định cho trang trại điện mặt trời'],
        'danh-muc-san-pham/bien-tan-inverter/hoa-luoi' => ['catalog-grid-tie-solar-20261008.png', 'Biến tần hòa lưới cho nhà máy điện mặt trời quy mô lớn'],
        'danh-muc-san-pham/bien-tan-inverter/hybrid-luu-tru' => ['catalog-hybrid-inverter-storage-20261008.png', 'Biến tần hybrid kết hợp điện mặt trời và hệ thống lưu trữ'],
        'danh-muc-san-pham/pin-luu-tru/pin-battery' => ['catalog-lithium-battery-modules-20261008.png', 'Module pin lithium cho hệ thống lưu trữ năng lượng'],
        'danh-muc-san-pham/pin-luu-tru/he-thong-ess' => ['catalog-ess-container-site-20261008.png', 'Hệ thống ESS container cho dự án lưu trữ năng lượng'],
        'danh-muc-san-pham/pin-luu-tru/bms-rack-phu-kien' => ['catalog-bms-rack-20261008.png', 'BMS, rack và phụ kiện quản lý hệ thống pin lưu trữ'],
        'danh-muc-san-pham/bien-tan-bom/bien-tan-bom-nlmt' => ['catalog-solar-pump-inverter-20261008.png', 'Biến tần bơm năng lượng mặt trời cho hệ thống tưới và cấp nước quy mô lớn'],
        'danh-muc-san-pham/bien-tan-bom/may-bom-nlmt' => ['catalog-solar-water-pump-20261008.png', 'Máy bơm năng lượng mặt trời cho nông nghiệp và công trình'],
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/bo-dieu-khien-sac-mppt' => ['catalog-mppt-controller-20261008-v2.png', 'Bộ điều khiển sạc MPPT cho hệ thống điện mặt trời'],
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/bo-dieu-khien-sac-pwm' => ['catalog-pwm-controller-20261008.png', 'Bộ điều khiển sạc PWM cho hệ thống điện mặt trời'],
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/cap-day-dien' => ['catalog-solar-cables-wiring-20261008.png', 'Cáp và dây điện chuyên dụng cho hệ thống năng lượng mặt trời'],
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/giam-sat-do-luong' => ['catalog-solar-monitoring-20261008.png', 'Thiết bị giám sát và đo lường hệ thống điện mặt trời'],
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/phu-kien-den-nlmt' => ['catalog-solar-lighting-accessories-20261008.png', 'Phụ kiện lắp đặt đèn năng lượng mặt trời'],
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/tiep-dia-ong-bao-ve' => ['catalog-solar-protection-grounding-20261008.png', 'Tiếp địa và ống bảo vệ cho hệ thống điện mặt trời'],
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/vat-tu-phu-tro' => ['catalog-solar-electrical-accessories-v2.jpg', 'Vật tư phụ trợ cho thi công và lắp đặt điện mặt trời'],
        'danh-muc-san-pham/den-duong' => ['catalog-solar-street-lighting-20261008.png', 'Đèn đường năng lượng mặt trời cho đường phố và lối đi'],
        'danh-muc-san-pham/den-pha-nang-luong-mat-troi' => ['catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'],
        'danh-muc-san-pham/den-pha' => ['catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'],
        'danh-muc-san-pham/den-ufo' => ['catalog-solar-ufo-highbay-20261008-v2.png', 'Đèn UFO năng lượng mặt trời cho nhà xưởng và không gian cao'],
        'danh-muc-san-pham/den-op-tran' => ['catalog-solar-ceiling-lighting-20261008.png', 'Đèn ốp trần năng lượng mặt trời cho hiên nhà và hành lang'],
        'danh-muc-san-pham/bien-tan-inverter' => ['catalog-inverter-overview-20261008.png', 'Hệ thống biến tần điện mặt trời cho công trình và nhà máy'],
        'danh-muc-san-pham/tam-pin-nang-luong-mat-troi' => ['catalog-utility-solar-farm-20261008.png', 'Trang trại tấm pin năng lượng mặt trời quy mô lớn'],
        'danh-muc-san-pham/tru-den' => ['catalog-lamp-posts-v2.jpg', 'Trụ đèn cổ điển và phụ kiện lắp đặt ngoài trời'],
        'danh-muc-san-pham/den-san-vuon' => ['catalog-ai-garden-lighting.webp', 'Không gian sân vườn sử dụng đèn năng lượng mặt trời'],
        'danh-muc-san-pham/quat-nang-luong-mat-troi' => ['catalog-solar-fan-20261008-v2.png', 'Quạt đứng sử dụng năng lượng mặt trời cho không gian gia đình'],
        'danh-muc-san-pham/bien-tan-bom' => ['catalog-solar-pumping-overview-20261008.png', 'Hệ thống bơm nước năng lượng mặt trời cho nông nghiệp và công trình'],
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi' => ['catalog-solar-bos-accessories-20261008.png', 'Dây dẫn, đầu nối và thiết bị bảo vệ cho hệ thống điện mặt trời'],
        'danh-muc-san-pham/he-thong-khung-nhom' => ['catalog-utility-solar-mounting-20261008.png', 'Hệ khung và phụ kiện lắp đặt tấm pin cho dự án quy mô lớn'],
        'danh-muc-san-pham/pin-luu-tru' => ['catalog-utility-bess-20261008.png', 'Hệ thống pin lưu trữ năng lượng cho gia đình và doanh nghiệp'],
    ];

    private const LIGHTING_FALLBACK = [
        'faq' => [
            ['type' => 'faq', 'question' => 'Nên chọn đèn theo những tiêu chí nào?', 'answer' => 'Xác định khu vực cần chiếu sáng, độ sáng mong muốn, thời gian sử dụng mỗi đêm, vị trí lắp đặt, khả năng chống bụi nước và chính sách bảo hành trước khi chọn sản phẩm.'],
            ['type' => 'faq', 'question' => 'Thời gian chiếu sáng phụ thuộc vào đâu?', 'answer' => 'Thời gian hoạt động phụ thuộc vào dung lượng pin lưu trữ, công suất đèn, khả năng thu nắng của tấm pin, chế độ vận hành và điều kiện thời tiết thực tế.'],
            ['type' => 'faq', 'question' => 'Cần lưu ý gì khi lắp đặt?', 'answer' => 'Tấm pin cần đặt ở vị trí nhận nắng tốt và hạn chế bóng che; thân đèn, móng hoặc giá đỡ phải phù hợp với vị trí sử dụng và được kiểm tra định kỳ.'],
        ],
        'cards' => [[
            'type' => 'cards',
            'title' => 'Chọn đèn đúng nhu cầu',
            'items' => [
                ['photo' => 'den-nlmt-duong-giao-thong.png', 'title' => 'Đường giao thông', 'desc' => 'Đảm bảo chiếu sáng an toàn, ổn định', 'link' => '/danh-muc-san-pham/den-nlmt/?is_need=duong-giao-thong'],
                ['photo' => 'den-nlmt-san-vuon.png', 'title' => 'Sân nhà & sân vườn', 'desc' => 'Tạo không gian sáng đẹp, tiện nghi', 'link' => '/danh-muc-san-pham/den-nlmt/?is_need=san-nha'],
                ['photo' => 'den-nlmt-cong-trinh.png', 'title' => 'Khuôn viên & công trình', 'desc' => 'Giải pháp chiếu sáng linh hoạt, bền bỉ', 'link' => '/danh-muc-san-pham/den-nlmt/?is_need=khuon-vien'],
            ],
        ]],
    ];

    public function __construct(private readonly Connection $db, private readonly ProductMediaUrls $urls) {}

    /**
     * @param  int|null  $selectedId  the category shown, null on /san-pham/
     * @param  array<string, mixed>  $categoryRow
     * @param  array<string, mixed>  $category
     * @return array<string, mixed>
     */
    public function extras(string $categoryPath, ?int $selectedId, array $categoryRow, array $category): array
    {
        $currentPath = $categoryPath === '' ? 'san-pham' : 'danh-muc-san-pham/'.$categoryPath;

        [$faq, $cards] = $this->sections($categoryRow);
        $applications = [];
        $title = '';
        foreach ($cards as $block) {
            if ($title === '') {
                $title = Text::plain($block['title'] ?? '');
            }
            foreach (is_array($block['items'] ?? null) ? $block['items'] : [] as $item) {
                $itemTitle = is_array($item) ? Text::plain($item['title'] ?? '') : '';
                if ($itemTitle === '') {
                    continue;
                }
                $photo = (string) ($item['photo'] ?? '');
                $image = $this->urls->visual($photo, 'upload/product/') ?: $this->urls->visual($photo, 'assets/images/site-visuals/');
                $applications[] = ['title' => $itemTitle, 'description' => Text::plain($item['desc'] ?? ''), 'image' => $image, 'href' => self::href($item['link'] ?? '')];
            }
        }
        $faqs = [];
        foreach ($faq as $item) {
            $question = Text::plain($item['question'] ?? '');
            $answer = Text::plain($item['answer'] ?? '');
            if ($question !== '' && $answer !== '') {
                $faqs[] = ['question' => $question, 'answer' => $answer];
            }
        }

        $canonical = $category['url'] ?? '/san-pham/';
        if (! preg_match('#^/(?:san-pham/|danh-muc-san-pham/(?:[a-z0-9]+(?:-[a-z0-9]+)*/){1,4})$#', $canonical)) {
            $canonical = '/san-pham/';
        }

        return [
            'categoryHero' => $this->hero($currentPath, $categoryPath, $categoryRow),
            'categoryContent' => ['title' => $title, 'applications' => $applications, 'faq' => $faqs],
            'support' => $this->support(),
            'seo' => $this->seo($selectedId, $categoryRow),
            'canonical' => ['url' => $canonical],
        ];
    }

    /** @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>} FAQ and cards, a level-2 page borrowing from its parent */
    private function sections(array $categoryRow): array
    {
        $depth = (int) ($categoryRow['depth'] ?? 0);
        if ($depth < 1 || $depth > 2) {
            return [[], []];
        }
        $parts = CatalogAssembler::sectionParts($this->db, (int) $categoryRow['id']);
        [$faq, $cards] = [$parts['faq'], $parts['cards']];
        if ($depth === 2 && (! $faq || ! $cards) && ! empty($categoryRow['parent_id'])) {
            $parent = CatalogAssembler::sectionParts($this->db, (int) $categoryRow['parent_id']);
            $faq = $faq ?: $parent['faq'];
            $cards = $cards ?: $parent['cards'];
        }
        if ($depth === 1 && ($categoryRow['slug'] ?? '') === 'den-nlmt' && (! $faq || ! $cards)) {
            $faq = $faq ?: self::LIGHTING_FALLBACK['faq'];
            $cards = $cards ?: self::LIGHTING_FALLBACK['cards'];
        }

        return [$faq, $cards];
    }

    /** Admin hero for this path; a hidden Admin hero disables the stock picture on purpose. */
    private function hero(string $currentPath, string $categoryPath, array $categoryRow): ?array
    {
        $hero = null;
        $configured = false;
        foreach ($this->db->table('banners')->where('placement', 'product-category-hero')->orderBy('sort_order')->orderByDesc('id')
            ->get(['title', 'link', 'image', 'is_visible']) as $row) {
            if (trim((string) parse_url((string) $row->link, PHP_URL_PATH), '/') !== $currentPath) {
                continue;
            }
            $configured = true;
            if ($row->is_visible && filled($row->image)) {
                $hero = ['photo' => $row->image, 'upload' => 'upload/photo/', 'alt' => (string) $row->title];
            }
            break;
        }
        if ($hero === null && ! $configured && isset(self::HERO_FALLBACKS[$currentPath])) {
            [$file, $alt] = self::HERO_FALLBACKS[$currentPath];
            if ($this->urls->visual($file, 'assets/images/site-visuals/') !== '') {
                $hero = ['photo' => $file, 'upload' => 'assets/images/site-visuals/', 'alt' => $alt];
            }
        }
        if ($hero === null && ! empty($categoryRow['image'])) {
            $hero = ['photo' => $categoryRow['image'], 'upload' => 'upload/product/', 'alt' => $categoryRow['name'] ?? ''];
        }
        if ($hero === null && $categoryPath === '' && $this->urls->exists('assets/images/site-visuals/catalog-products-hero-20261007-v2.png')) {
            $hero = ['photo' => 'catalog-products-hero-20261007-v2.png', 'upload' => 'assets/images/site-visuals/', 'alt' => 'Danh mục thiết bị năng lượng mặt trời Phúc Thịnh Solar'];
        }
        if ($hero === null) {
            return null;
        }
        $image = $this->urls->visual($hero['photo'], $hero['upload'])
            ?: $this->urls->visual($hero['photo'], 'assets/images/site-visuals/')
            ?: $this->urls->visual($hero['photo'], 'upload/product/');

        return $image !== '' ? ['image' => $image, 'alt' => Text::plain($hero['alt'])] : null;
    }

    /** First published "catalog-campaign" banner; a built-in invitation only when Admin has none at all. */
    private function support(): ?array
    {
        $rows = $this->db->table('banners')->where('placement', 'catalog-campaign')->orderBy('sort_order')->orderByDesc('id')
            ->get(['title', 'summary', 'image', 'link', 'is_visible']);
        $banner = null;
        foreach ($rows as $row) {
            if ($row->is_visible && filled($row->image)) {
                $banner = ['name' => (string) $row->title, 'desc' => (string) $row->summary, 'photo' => $row->image, 'link' => $row->link ?? 'lien-he/'];
                break;
            }
        }
        if ($rows->isEmpty()) {
            $banner = ['name' => 'Cần hỗ trợ chọn sản phẩm phù hợp?', 'desc' => 'Chia sẻ nhu cầu để đội ngũ Phúc Thịnh Solar tư vấn cấu hình và báo giá phù hợp.', 'photo' => 'catalog-equipment-system.jpg', 'link' => 'lien-he/'];
        }
        $title = $banner ? Text::plain($banner['name']) : '';
        if ($title === '') {
            return null;
        }

        return [
            'title' => $title,
            'description' => Text::plain($banner['desc']),
            'image' => $this->urls->visual($banner['photo'], 'upload/photo/') ?: $this->urls->visual($banner['photo'], 'assets/images/site-visuals/'),
            'href' => self::href($banner['link']),
        ];
    }

    /** SEO of the category level shown, or of the /san-pham/ page. */
    private function seo(?int $selectedId, array $categoryRow): array
    {
        $row = [];
        $image = '';
        if ($selectedId !== null) {
            if (! empty($categoryRow['id'])) {
                $row = (array) ($this->db->table('seo_metas')->where('owner_type', Category::class)->where('owner_id', (int) $categoryRow['id'])
                    ->first(['title', 'description', 'keywords']) ?? []);
                $image = $this->urls->visual($categoryRow['image'] ?? '', 'upload/product/');
            }
        } else {
            $row = (array) ($this->db->table('seo_metas')->where('page', 'san-pham')->first(['title', 'description', 'keywords', 'image']) ?? []);
            $image = $this->urls->visual($row['image'] ?? '', 'upload/seopage/');
        }
        $title = ! empty($row['title']) ? Text::seo($row['title']) : Text::seo(($categoryRow['name'] ?? '') ?: 'Sản phẩm');
        if ($title !== '' && stripos($title, 'phúc thịnh s') === false) {
            $title .= ' | Phúc Thịnh Solar';
        }

        return [
            'title' => $title,
            'description' => ! empty($row['description']) ? Text::seo($row['description']) : '',
            'keywords' => ! empty($row['keywords']) ? Text::seo($row['keywords']) : '',
            'image' => $image,
        ];
    }

    /** Site-relative link from Admin, refusing scripts, protocol-relative and traversal. */
    private static function href(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || preg_match('/[\x00-\x20<>"\'`]/', $value) || str_contains($value, '..') || preg_match('/^(?:javascript|data|vbscript):/i', $value) || str_starts_with($value, '//')) {
            return '';
        }

        return str_starts_with($value, '/') ? $value : '/'.$value;
    }
}
