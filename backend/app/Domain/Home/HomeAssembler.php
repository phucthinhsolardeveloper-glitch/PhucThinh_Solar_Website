<?php

namespace App\Domain\Home;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Legacy\Settings\LegacySettings;
use App\Legacy\Support\LegacyText;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Homepage data (api/pts_v1_home.php + pts_public_home_api.php). Each block reads its Admin rows and
 * falls back to fixed copy and stock pictures only where the PHP page did.
 */
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

    public function __construct(private readonly ProductMediaUrls $urls, private readonly LegacySettings $settings)
    {
        $this->db = DB::connection('legacy');
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $categories = $this->rows("select id,namevi,slugvi,status,photo from table_product_list where type='san-pham' and find_in_set('noibat',status) and find_in_set('hienthi',status)"
            ." and exists (select 1 from table_product p where p.id_list=table_product_list.id and p.type='san-pham' and find_in_set('hienthi',p.status)) order by numb,id desc");
        $isMajor = fn ($row) => str_contains(','.$row['status'].',', ',major,');
        $categories = array_merge(array_values(array_filter($categories, $isMajor)), array_values(array_filter($categories, fn ($row) => ! $isMajor($row))));

        $hero = $this->hero();
        $categoryOut = $this->categories($categories);
        $campaignRows = $this->rows("select namevi,descvi,photo,link,status from table_photo where type='home-campaign' order by numb,id desc");
        $campaigns = [];
        foreach ($campaignRows as $row) {
            $image = self::visible($row) && ! empty($row['photo']) ? $this->path($row['photo'], 'upload/photo/') : '';
            if ($image === '') {
                continue;
            }
            $campaigns[] = ['name' => self::text($row['namevi']) ?: 'Giải pháp năng lượng', 'description' => self::text($row['descvi']), 'image' => $image, 'href' => self::url($row['link'], '/san-pham/')];
            if (count($campaigns) >= 3) {
                break;
            }
        }
        $solutions = $this->solutions();
        $process = $this->process();
        $productGroups = $this->productGroups($categories);
        $options = $this->settings->options();
        $seoRow = (array) ($this->db->selectOne("select titlevi,descriptionvi,keywordsvi from table_seo where com='setting' and act='update' and type='setting' limit 1") ?? []);
        $logo = $this->db->selectOne("select photo from table_photo where type='logo' and act='photo_static' limit 0,1");
        $popup = (array) ($this->db->selectOne("select id,date_updated,namevi,photo,link from table_photo where type='popup' and act='photo_static' and find_in_set('hienthi',status) limit 0,1") ?? []);

        return [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/',
            'seo' => [
                'title' => self::text(LegacyText::flat($seoRow['titlevi'] ?? '')),
                'description' => self::text(LegacyText::flat($seoRow['descriptionvi'] ?? '')),
                'keywords' => self::text(LegacyText::flat($seoRow['keywordsvi'] ?? '')),
                'image' => self::text($this->urls->visual($logo->photo ?? '', 'upload/photo/')),
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
                'campaignsConfigured' => $campaignRows ? 1 : 0,
                'solutions' => count($solutions),
                'process' => count($process),
                'productGroups' => count($productGroups),
            ],
        ];
    }

    /** Admin slides replace the stock ones position by position; once Admin has any slide, stock ones are not mixed in. */
    private function hero(): array
    {
        $rows = $this->rows("select namevi,descvi,photo,link,status,options from table_photo where type='hero-carousel' order by numb,id desc");
        $enabled = array_values(array_filter($rows, fn ($row) => self::visible($row) && ! empty($row['photo'])));
        $hero = [];
        foreach (self::HERO_DEFAULTS as $i => [$key, $name, $description, $photo]) {
            $row = $enabled[$i] ?? [];
            if ($rows && ! $row) {
                continue;
            }
            $image = $row ? $this->path($row['photo'], 'upload/photo/') : $this->path($photo, 'assets/images/site-visuals/');
            if ($image === '') {
                continue;
            }
            $options = ! empty($row['options']) ? json_decode((string) $row['options'], true) : [];
            $secondLine = is_array($options) ? self::text($options['titleLine2'] ?? '') : '';
            $hero[] = [
                'key' => $key,
                'name' => trim((self::text($row['namevi'] ?? '') ?: $name).' '.$secondLine),
                'description' => self::text($row['descvi'] ?? '') ?: $description,
                'image' => $image,
                'href' => self::url($row['link'] ?? '', '/san-pham/'),
            ];
        }

        return $hero;
    }

    private function categories(array $categories): array
    {
        $out = [];
        foreach ($categories as $row) {
            $slug = self::text($row['slugvi']);
            if ($slug === '') {
                continue;
            }
            $image = $this->path($row['photo'], 'upload/product/');
            if ($image === '') {
                foreach (self::CATEGORY_FALLBACKS as $needle => $file) {
                    if (str_contains($slug, $needle)) {
                        $image = $this->path($file, 'assets/images/site-visuals/');
                        break;
                    }
                }
            }
            $out[] = ['id' => (int) $row['id'], 'name' => self::text($row['namevi']), 'slug' => $slug, 'url' => '/danh-muc-san-pham/'.$slug.'/',
                'major' => str_contains(','.$row['status'].',', ',major,'), 'image' => $image];
        }

        return $out;
    }

    /** Four fixed solution slots, each filled by the first featured solution whose name matches the slot. */
    private function solutions(): array
    {
        $rows = $this->rows("select namevi,title_indexvi,slugvi,descvi,photo1,status from table_news where type='giai-phap' and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc");
        $solutions = [];
        foreach (self::SOLUTION_SLOTS as $i => $slot) {
            $row = [];
            foreach ($rows as $candidate) {
                $search = mb_strtolower(self::text(($candidate['namevi'] ?? '').' '.($candidate['title_indexvi'] ?? '')));
                foreach ($slot['needles'] as $needle) {
                    if (str_contains($search, mb_strtolower($needle))) {
                        $row = $candidate;
                        break 2;
                    }
                }
            }
            $image = $i === 2 ? $this->path($slot['image'], 'assets/images/site-visuals/') : $this->path($row['photo1'] ?? '', 'upload/news/');
            $image = $image ?: $this->path($slot['image'], 'assets/images/site-visuals/');
            if ($image === '') {
                continue;
            }
            $solutions[] = ['label' => $slot['label'], 'name' => self::text($row['namevi'] ?? '') ?: $slot['name'], 'description' => self::text($row['descvi'] ?? '') ?: $slot['description'],
                'image' => $image, 'href' => self::url(($row['slugvi'] ?? '') ? 'giai-phap/'.$row['slugvi'].'/' : $slot['href'])];
        }

        return $solutions;
    }

    private function process(): array
    {
        $rows = $this->rows("select namevi,solieu,descvi from table_news where type='quy-trinh' and find_in_set('hienthi',status) order by numb,id desc limit 0,5");
        $out = [];
        foreach (self::PROCESS_DEFAULTS as $i => [$number, $name, $description]) {
            $row = $rows[$i] ?? [];
            $out[] = ['number' => self::text($row['solieu'] ?? '') ?: $number, 'name' => self::text($row['namevi'] ?? '') ?: $name, 'description' => self::text($row['descvi'] ?? '') ?: $description];
        }

        return $out;
    }

    private function about(): array
    {
        $rows = $this->rows("select namevi,descvi,content1vi,status from table_news where type='home-about' order by numb,id desc");
        $row = [];
        foreach ($rows as $candidate) {
            if (self::visible($candidate)) {
                $row = $candidate;
                break;
            }
        }
        $points = [];
        foreach ($this->rows("select namevi,descvi,photo,status from table_news where type='home-about-point' order by numb,id desc") as $point) {
            if (! self::visible($point) || count($points) >= 4) {
                continue;
            }
            $fallback = self::ABOUT_POINTS[count($points)];
            $points[] = ['label' => self::text($point['namevi']), 'description' => self::text($point['descvi']) ?: $fallback['description'], 'image' => $this->path($point['photo'], 'upload/news/')];
        }
        if (! $points) {
            $points = array_map(fn ($point) => $point + ['image' => ''], self::ABOUT_POINTS);
        }

        return [
            'configured' => count($rows) > 0,
            'visible' => ! empty($row),
            'title' => self::text($row['namevi'] ?? '') ?: 'Về Phúc Thịnh Solar',
            'description' => self::text($row['descvi'] ?? '') ?: 'Phúc Thịnh Solar cung cấp đèn năng lượng mặt trời, tấm pin, biến tần, pin lưu trữ và thiết bị bơm nước.',
            'href' => '/gioi-thieu/',
            'action' => self::text($row['content1vi'] ?? '') ?: 'Tìm hiểu về Phúc Thịnh Solar',
            'points' => $points,
        ];
    }

    /** Up to ten pictured products for each of the first three featured categories that have any. */
    private function productGroups(array $categories): array
    {
        $hasPhoto = "(table_product.photo is not null and trim(table_product.photo) <> '') or exists (select 1 from table_gallery g where g.id_parent = table_product.id and g.com = 'product' and g.kind = 'man' and g.val = table_product.type and find_in_set('hienthi', g.status))";
        $groups = [];
        foreach ($categories as $category) {
            if (count($groups) >= 3) {
                break;
            }
            $products = [];
            foreach ($this->db->select("select id,namevi,slugvi,photo from table_product where type='san-pham' and id_list=? and photo<>'' and find_in_set('hienthi',status)"
                ." order by case when {$hasPhoto} then 0 else 1 end, table_product.numb,table_product.id desc limit 0,10", [(int) $category['id']]) as $row) {
                $image = $this->path($row->photo, 'upload/product/');
                if ($image !== '') {
                    $products[] = ['id' => (int) $row->id, 'name' => self::text($row->namevi), 'slug' => self::text($row->slugvi), 'href' => self::url('san-pham/'.$row->slugvi.'/', '/san-pham/'), 'image' => $image];
                }
            }
            if ($products) {
                $groups[] = ['categoryId' => (int) $category['id'], 'categoryName' => self::text($category['namevi']), 'categoryUrl' => '/danh-muc-san-pham/'.self::text($category['slugvi']).'/', 'products' => $products];
            }
        }

        return $groups;
    }

    /** The key changes whenever the popup record, picture or edit time changes, so an edited campaign shows again. */
    private function popup(array $row): ?array
    {
        $image = $row ? $this->path($row['photo'] ?? '', 'upload/photo/') : '';
        if ($image === '') {
            return null;
        }

        return [
            'key' => substr(hash('sha256', ($row['id'] ?? '').'|'.($row['date_updated'] ?? '').'|'.($row['photo'] ?? '')), 0, 20),
            'image' => $image,
            'title' => self::text($row['namevi'] ?? ''),
            'href' => self::url($row['link'] ?? ''),
        ];
    }

    /** Admin rows override the energy-chain cards by position; empty fields stay empty so Nuxt keeps its default copy. */
    private function energyChain(): array
    {
        $chain = [];
        foreach ($this->rows("select namevi,title_indexvi,descvi,solieu,photo from table_news where type='home-energy-chain' and find_in_set('hienthi',status) order by numb,id desc limit 0,5") as $row) {
            $chain[] = [
                'name' => self::cut(self::text($row['namevi']), 80),
                'stage' => self::cut(self::text($row['title_indexvi']), 28),
                'description' => self::cut(self::text($row['descvi']), 220),
                'action' => self::cut(self::text($row['solieu']), 40),
                'image' => $this->path($row['photo'], 'upload/news/'),
            ];
        }

        return $chain;
    }

    /** A bare file name with an image extension that exists in the folder (ptsHomePath). */
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

    /** @return list<array<string, mixed>> */
    private function rows(string $sql): array
    {
        return array_map(fn ($row) => (array) $row, $this->db->select($sql));
    }

    private static function visible(array $row): bool
    {
        return str_contains(','.($row['status'] ?? '').',', ',hienthi,');
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
