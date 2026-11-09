<?php

namespace App\Domain\Home;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Support\SiteSettings;
use App\Support\Text;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Homepage data. Each block reads its Admin rows and falls back to fixed copy and stock pictures. */
class HomeAssembler
{
    private const CATEGORY_FALLBACKS = ['den-nlmt' => 'home-street-lighting-v2.png', 'tam-pin' => 'category-panels-user-2026.jpg', 'bien-tan' => 'category-inverter-user-2026.jpg',
        'inverter' => 'category-inverter-user-2026.jpg', 'pin-luu-tru' => 'category-storage-user-2026.jpg', 'luu-tru' => 'category-storage-user-2026.jpg', 'bom' => 'category-pump-user-2026.jpg'];

    private const HERO_DEFAULTS = [
        ['street-light', 'Đèn đường năng lượng mặt trời', 'Chiếu sáng đường nội bộ, khu dân cư và công trình.', 'home-hero-street-lighting-v1.webp'],
        ['inverter', 'Biến tần năng lượng mặt trời', 'Thiết bị chuyển đổi và quản lý nguồn điện.', 'home-hero-inverter-v1.webp'],
        ['panel', 'Tấm pin năng lượng mặt trời', 'Thiết bị quang điện cho hệ thống năng lượng mặt trời.', 'home-hero-solar-panels-v1.webp'],
        ['storage', 'Pin lưu trữ', 'Chủ động lưu trữ điện theo nhu cầu sử dụng.', 'home-hero-battery-storage-v1.webp'],
    ];

    private const SOLUTION_SLOTS = [
        ['label' => 'Doanh nghiệp và nhà xưởng', 'needles' => ['doanh nghiệp', 'doanh nghiep', 'nhà xưởng', 'nha xuong'], 'name' => 'Điện mặt trời cho doanh nghiệp', 'description' => 'Đề xuất phương án theo nhu cầu vận hành, đặc điểm mái và điều kiện lắp đặt tại cơ sở.', 'image' => 'solution-bess-v2.jpg', 'href' => '/giai-phap/dien-mat-troi-doanh-nghiep/'],
        ['label' => 'Gia đình', 'needles' => ['gia đình', 'gia dinh', 'hộ gia đình', 'ho gia dinh'], 'name' => 'Điện mặt trời cho gia đình', 'description' => 'Tư vấn hệ thống theo nhu cầu dùng điện, diện tích mái và nhu cầu lưu trữ của gia đình.', 'image' => 'solution-home-solar-v2.jpg', 'href' => '/giai-phap/dien-mat-troi-ho-gia-dinh/'],
        ['label' => 'Chiếu sáng công trình', 'needles' => ['chiếu sáng', 'chieu sang', 'đèn', 'den'], 'name' => 'Chiếu sáng đường và công trình', 'description' => 'Tư vấn lựa chọn thiết bị và phương án bố trí đèn theo đặc điểm từng khu vực.', 'image' => 'home-solution-construction-v1.png', 'href' => '/giai-phap/'],
        ['label' => 'Nông nghiệp', 'needles' => ['nông nghiệp', 'nong nghiep', 'bơm', 'bom'], 'name' => 'Bơm nước năng lượng mặt trời', 'description' => 'Tư vấn hệ thống theo nguồn nước, nhu cầu tưới tiêu và điều kiện sử dụng.', 'image' => 'solution-solar-pump-v2.jpg', 'href' => '/giai-phap/'],
    ];

    private const PROCESS_DEFAULTS = [
        ['01', 'Tư vấn & khảo sát', 'Lắng nghe nhu cầu, khảo sát hiện trạng và điều kiện lắp đặt.'],
        ['02', 'Thiết kế & báo giá', 'Xây dựng phương án kỹ thuật và lựa chọn thiết bị phù hợp.'],
        ['03', 'Thi công & lắp đặt', 'Triển khai theo phương án đã thống nhất, chú trọng an toàn và chất lượng.'],
        ['04', 'Nghiệm thu & bàn giao', 'Kiểm tra, chạy thử, nghiệm thu và hướng dẫn sử dụng.'],
        ['05', 'Bảo hành & đồng hành', 'Tiếp nhận yêu cầu hỗ trợ và cung cấp dịch vụ theo chính sách.'],
    ];

    private const ABOUT_POINTS = [
        ['label' => 'Sản phẩm chính hãng', 'description' => 'Thông tin thương hiệu, thông số kỹ thuật và chính sách bảo hành rõ ràng.'],
        ['label' => 'Tư vấn và thi công', 'description' => 'Đề xuất thiết bị và phương án lắp đặt phù hợp với điều kiện thực tế của từng công trình.'],
        ['label' => 'Hỗ trợ sau bàn giao', 'description' => 'Hướng dẫn sử dụng, tiếp nhận yêu cầu bảo hành và hỗ trợ kỹ thuật trong quá trình vận hành.'],
        ['label' => 'Hệ thống kho ba khu vực', 'description' => 'Kho tại TP.HCM, Đà Nẵng và Gia Lai, hỗ trợ giao hàng toàn quốc.'],
    ];

    private Connection $db;

    public function __construct(private readonly ProductMediaUrls $urls, private readonly SiteSettings $settings, private readonly CategoryTree $categories)
    {
        $this->db = DB::connection();
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        // Featured top categories with products to show; the major ones come first.
        $categories = $this->db->table('categories')->where('depth', 1)->where('is_featured', true)->where('is_visible', true)
            ->orderBy('sort_order')->orderByDesc('id')->get(['id', 'name', 'slug', 'image', 'is_major'])->map(fn ($row) => (array) $row)
            ->filter(fn ($row) => $this->categories->hasVisibleProducts((int) $row['id']))
            ->sortBy(fn ($row) => $row['is_major'] ? 0 : 1, SORT_REGULAR, false)->values()->all();

        $hero = $this->hero();
        $categoryOut = $this->categories($categories);
        $campaignRows = $this->banners('home-campaign');
        $campaigns = [];
        foreach ($campaignRows as $row) {
            $image = $row->is_visible ? $this->path($row->image, 'upload/photo/') : '';
            if ($image === '') {
                continue;
            }
            $campaigns[] = ['name' => self::text($row->title) ?: 'Giải pháp năng lượng', 'description' => self::text($row->summary), 'image' => $image, 'href' => self::url($row->link, '/san-pham/')];
            if (count($campaigns) >= 3) {
                break;
            }
        }
        $solutions = $this->solutions();
        $process = $this->process();
        $productGroups = $this->productGroups($categories);
        $options = $this->settings->options();
        $seoRow = $this->db->table('seo_metas')->where('page', 'home')->first(['title', 'description', 'keywords']);
        $logo = $this->banners('logo')->first();
        $popup = $this->banners('popup')->firstWhere('is_visible', true);

        return [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/',
            'seo' => [
                'title' => self::text(Text::flat((string) $seoRow?->title)),
                'description' => self::text(Text::flat((string) $seoRow?->description)),
                'keywords' => self::text(Text::flat((string) $seoRow?->keywords)),
                'image' => self::text($this->urls->visual((string) $logo?->image, 'upload/photo/')),
            ],
            'hero' => $hero,
            'categories' => $categoryOut,
            'campaigns' => $campaigns,
            'solutions' => $solutions,
            'process' => $process,
            'dealer' => ['title' => 'Đăng ký đại lý', 'description' => 'Cùng phát triển kinh doanh thiết bị năng lượng mặt trời với nguồn hàng đa dạng, chính sách hợp tác rõ ràng và sự đồng hành của đội ngũ Phúc Thịnh Solar.', 'href' => '/dai-ly/'],
            'about' => $this->about(),
            'contact' => ['hotline' => self::text($options['hotline'] ?? ''), 'email' => self::text($options['email'] ?? ''), 'address' => self::text($options['address'] ?? ''), 'workingHours' => self::text($options['thoigianlamviec'] ?? '')],
            'productGroups' => $productGroups,
            'popup' => $this->popup($popup),
            'energyChain' => $this->energyChain(),
            'summary' => [
                'hero' => count($hero),
                'categories' => count($categoryOut),
                'campaigns' => count($campaigns),
                'campaignsConfigured' => $campaignRows->isNotEmpty() ? 1 : 0,
                'solutions' => count($solutions),
                'process' => count($process),
                'productGroups' => count($productGroups),
            ],
        ];
    }

    /** Admin slides replace the stock ones position by position; once Admin has any slide, stock ones are not mixed in. */
    private function hero(): array
    {
        $rows = $this->banners('hero-carousel');
        $enabled = $rows->filter(fn ($row) => $row->is_visible && filled($row->image))->values();
        $hero = [];
        foreach (self::HERO_DEFAULTS as $i => [$key, $name, $description, $photo]) {
            $row = $enabled->get($i);
            if ($rows->isNotEmpty() && ! $row) {
                continue;
            }
            $image = $row ? $this->path($row->image, 'upload/photo/') : $this->path($photo, 'assets/images/site-visuals/');
            if ($image === '') {
                continue;
            }
            $options = (array) json_decode((string) $row?->options, true);
            $secondLine = self::text($options['titleLine2'] ?? '');
            $hero[] = [
                'key' => $key,
                'name' => trim((self::text($row?->title) ?: $name).' '.$secondLine),
                'description' => self::text($row?->summary) ?: $description,
                'image' => $image,
                'href' => self::url($row?->link, '/san-pham/'),
            ];
        }

        return $hero;
    }

    private function categories(array $categories): array
    {
        $out = [];
        foreach ($categories as $row) {
            $slug = self::text($row['slug']);
            if ($slug === '') {
                continue;
            }
            $image = $this->path($row['image'], 'upload/product/');
            if ($image === '') {
                foreach (self::CATEGORY_FALLBACKS as $needle => $file) {
                    if (str_contains($slug, $needle)) {
                        $image = $this->path($file, 'assets/images/site-visuals/');
                        break;
                    }
                }
            }
            $out[] = ['id' => (int) $row['id'], 'name' => self::text($row['name']), 'slug' => $slug, 'url' => '/danh-muc-san-pham/'.$slug.'/',
                'major' => (bool) $row['is_major'], 'image' => $image];
        }

        return $out;
    }

    /** Four fixed solution slots, each filled by the first featured solution whose name matches the slot. */
    private function solutions(): array
    {
        $rows = $this->db->table('posts')->where('kind', 'solution')->where('is_featured', true)->where('is_visible', true)
            ->orderBy('sort_order')->orderByDesc('id')->get(['title', 'nav_title', 'slug', 'summary', 'cover_image'])->map(fn ($row) => (array) $row)->all();
        $solutions = [];
        foreach (self::SOLUTION_SLOTS as $i => $slot) {
            $row = [];
            foreach ($rows as $candidate) {
                $search = mb_strtolower(self::text($candidate['title'].' '.$candidate['nav_title']));
                foreach ($slot['needles'] as $needle) {
                    if (str_contains($search, mb_strtolower($needle))) {
                        $row = $candidate;
                        break 2;
                    }
                }
            }
            $image = $i === 2 ? $this->path($slot['image'], 'assets/images/site-visuals/') : $this->path($row['cover_image'] ?? '', 'upload/news/');
            $image = $image ?: $this->path($slot['image'], 'assets/images/site-visuals/');
            if ($image === '') {
                continue;
            }
            $solutions[] = ['label' => $slot['label'], 'name' => self::text($row['title'] ?? '') ?: $slot['name'], 'description' => self::text($row['summary'] ?? '') ?: $slot['description'],
                'image' => $image, 'href' => self::url(($row['slug'] ?? '') ? 'giai-phap/'.$row['slug'].'/' : $slot['href'])];
        }

        return $solutions;
    }

    private function process(): array
    {
        $rows = $this->items('home.process')->where('is_visible', true)->take(5)->values();
        $out = [];
        foreach (self::PROCESS_DEFAULTS as $i => [$number, $name, $description]) {
            $row = $rows->get($i);
            $out[] = ['number' => self::text($row?->figure) ?: $number, 'name' => self::text($row?->title) ?: $name, 'description' => self::text($row?->summary) ?: $description];
        }

        return $out;
    }

    private function about(): array
    {
        $rows = $this->items('home.about');
        $row = $rows->firstWhere('is_visible', true);
        $points = [];
        foreach ($this->items('home.about_points')->where('is_visible', true)->take(4) as $point) {
            $fallback = self::ABOUT_POINTS[count($points)];
            $points[] = ['label' => self::text($point->title), 'description' => self::text($point->summary) ?: $fallback['description'], 'image' => $this->path($point->image, 'upload/news/')];
        }
        if (! $points) {
            $points = array_map(fn ($point) => $point + ['image' => ''], self::ABOUT_POINTS);
        }

        return [
            'configured' => $rows->isNotEmpty(),
            'visible' => $row !== null,
            'title' => self::text($row?->title) ?: 'Về Phúc Thịnh Solar',
            'description' => self::text($row?->summary) ?: 'Phúc Thịnh Solar cung cấp đèn năng lượng mặt trời, tấm pin, biến tần, pin lưu trữ và thiết bị bơm nước.',
            'href' => '/gioi-thieu/',
            'action' => self::text($row?->extra) ?: 'Tìm hiểu về Phúc Thịnh Solar',
            'points' => $points,
        ];
    }

    /** Up to ten pictured products for each of the first three featured categories that have any. */
    private function productGroups(array $categories): array
    {
        $groups = [];
        foreach ($categories as $category) {
            if (count($groups) >= 3) {
                break;
            }
            $products = [];
            $pictured = $this->db->table('products')->where('is_visible', true)->whereNotNull('image')->where('image', '<>', '')
                ->whereIn('category_id', $this->categories->descendantIds((int) $category['id']))
                ->orderBy('sort_order')->orderByDesc('id')->limit(10)->get(['id', 'name', 'slug', 'image']);
            foreach ($pictured as $row) {
                $image = $this->path($row->image, 'upload/product/');
                if ($image !== '') {
                    $products[] = ['id' => (int) $row->id, 'name' => self::text($row->name), 'slug' => self::text($row->slug), 'href' => self::url('san-pham/'.$row->slug.'/', '/san-pham/'), 'image' => $image];
                }
            }
            if ($products) {
                $groups[] = ['categoryId' => (int) $category['id'], 'categoryName' => self::text($category['name']), 'categoryUrl' => '/danh-muc-san-pham/'.self::text($category['slug']).'/', 'products' => $products];
            }
        }

        return $groups;
    }

    /** The key changes whenever the popup record, picture or edit time changes, so an edited campaign shows again. */
    private function popup(?object $row): ?array
    {
        $image = $row ? $this->path($row->image, 'upload/photo/') : '';
        if ($image === '') {
            return null;
        }

        return [
            'key' => substr(hash('sha256', $row->id.'|'.($row->updated_at ? strtotime((string) $row->updated_at) : '').'|'.$row->image), 0, 20),
            'image' => $image,
            'title' => self::text($row->title),
            'href' => self::url($row->link),
        ];
    }

    /** Admin rows override the energy-chain cards by position; empty fields stay empty so Nuxt keeps its default copy. */
    private function energyChain(): array
    {
        $chain = [];
        foreach ($this->items('home.energy_chain')->where('is_visible', true)->take(5) as $row) {
            $chain[] = [
                'name' => self::cut(self::text($row->title), 80),
                'stage' => self::cut(self::text($row->subtitle), 28),
                'description' => self::cut(self::text($row->summary), 220),
                'action' => self::cut(self::text($row->figure), 40),
                'image' => $this->path($row->image, 'upload/news/'),
            ];
        }

        return $chain;
    }

    /** A bare file name with an image extension that exists in the folder. */
    private function path(mixed $file, string $folder): string
    {
        if (! is_scalar($file) || basename((string) $file) !== (string) $file) {
            return '';
        }
        $file = trim((string) $file);
        if ($file === '' || ! preg_match('/\.(?:png|jpe?g|gif|webp)$/i', $file)) {
            return '';
        }

        return $this->urls->exists($folder.$file) ? '/'.$folder.$file : '';
    }

    /** @return Collection<int, \stdClass> pictures of one placement in Admin order */
    private function banners(string $placement): Collection
    {
        return $this->db->table('banners')->where('placement', $placement)->orderBy('sort_order')->orderByDesc('id')->get();
    }

    /** @return Collection<int, \stdClass> items of one page section in Admin order */
    private function items(string $section): Collection
    {
        return $this->db->table('page_items')->where('section', $section)->orderBy('sort_order')->orderByDesc('id')->get();
    }

    private static function text(mixed $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
        $value = (string) preg_replace('~<(?:br\s*/?|/p|/li)>~i', ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
    }

    private static function url(mixed $value, string $fallback = ''): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || preg_match('~[\x00-\x20<>"`]|(?:^|/)\.\.(?:/|$)|^(?:javascript|data|vbscript):~i', $value)) {
            return $fallback;
        }
        if (preg_match('~^https?://~i', $value)) {
            return $value;
        }

        return $value[0] === '/' ? $value : '/'.$value;
    }

    private static function cut(string $value, int $limit): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        $cut = mb_substr($value, 0, $limit);
        $space = strrpos($cut, ' ');
        if ($space !== false && $space > strlen($cut) / 2) {
            $cut = substr($cut, 0, $space);
        }

        return rtrim($cut, ' ,.;:-').'…';
    }
}
