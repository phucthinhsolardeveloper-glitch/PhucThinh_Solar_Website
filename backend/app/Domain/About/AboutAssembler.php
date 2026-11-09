<?php

namespace App\Domain\About;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Pages\PageLayouts;
use App\Models\Brand;
use App\Support\SiteSettings;
use App\Support\Text;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * About page (schema 2). History items stay empty on purpose: the history is written in the
 * storefront (AboutHistory.vue) because the CMS timeline carried a wrong founding year.
 */
class AboutAssembler
{
    private const LABELS = [
        'storyTitle' => 'Giới thiệu Phúc Thịnh Solar',
        'storyEyebrow' => 'Hành trình phát triển',
        'timelineLabel' => 'Các mốc phát triển của Phúc Thịnh Solar',
        'peopleEyebrow' => 'Con người là nền tảng',
        'peopleTitle' => 'Nhân sự Phúc Thịnh Solar',
        'peopleFallback' => 'Đội ngũ giàu kinh nghiệm, tư vấn sát nhu cầu thực tế, triển khai có trách nhiệm và đồng hành lâu dài cùng khách hàng.',
        'principleExpertise' => 'Chuyên môn thực tế',
        'principleExpertiseDescription' => 'Đội ngũ kỹ sư, kỹ thuật viên giàu kinh nghiệm, am hiểu thực tế.',
        'principleSupport' => 'Đồng hành đến cùng',
        'principleSupportDescription' => 'Tư vấn trung thực, hỗ trợ xuyên suốt vòng đời dự án.',
        'principleCustomer' => 'Lấy khách hàng làm trung tâm',
        'principleCustomerDescription' => 'Luôn lắng nghe, thấu hiểu và mang đến giá trị thiết thực.',
        'trustTitle' => 'Vì sao khách hàng lựa chọn chúng tôi?',
        'trustDefault' => 'Một cam kết thực tế được duy trì xuyên suốt từ tư vấn, triển khai đến hỗ trợ sau bàn giao.',
        'trustAuthentic' => 'Thiết bị có nguồn gốc rõ ràng, thông tin minh bạch.',
        'trustTeam' => 'Tư vấn đúng nhu cầu và điều kiện triển khai thực tế.',
        'trustSupport' => 'Hỗ trợ kỹ thuật và chăm sóc khách hàng sau bàn giao.',
        'trustWarranty' => 'Điều kiện bảo hành rõ ràng, quy trình hỗ trợ minh bạch.',
        'compassTitle' => 'Tầm nhìn – Sứ mệnh – Giá trị cốt lõi',
        'certTitle' => 'Chứng nhận & năng lực',
        'certIntro' => 'Cam kết chất lượng được thể hiện bằng những chứng nhận đang được công bố.',
        'certLabel' => 'Chứng nhận',
        'certAll' => 'Xem tất cả chứng nhận',
        'certDetail' => 'Xem chi tiết',
        'contactTitle' => 'Cần tư vấn giải pháp năng lượng mặt trời?',
        'contactIntro' => 'Đội ngũ Phúc Thịnh Solar luôn sẵn sàng đồng hành cùng bạn.',
        'contactPrimary' => 'Nhận tư vấn ngay',
    ];

    private Connection $db;

    public function __construct(private readonly ProductMediaUrls $urls, private readonly SiteSettings $settings)
    {
        $this->db = DB::connection();
    }

    /** @return array<string, mixed> */
    /** @param list<array{type: string, variant: string}>|null $layout a draft layout for preview; null = the published one */
    public function build(?array $layout = null): array
    {
        $l = self::LABELS;
        $history = $this->section('about.history', 8);
        $compassRows = $this->section('about.compass', 6);
        $trustRows = $this->section('about.reasons');
        $team = $this->section('about.team', 1);
        $principleRows = $this->section('about.principles', 3);

        $story = self::find($history, ['ve chung toi']) ?: ($history[0] ?? []);
        $historyRecord = self::find($history, ['lich su', 'hinh thanh']) ?: $story;
        $intro = self::text($story['summary'] ?? '') ?: self::sentences($story['body'] ?? '', 2);
        $body = self::sentences($story['body'] ?? '', 4);
        if ($intro !== '' && str_starts_with($body, $intro)) {
            $body = trim(substr($body, strlen($intro)));
        }
        if ($body === $intro) {
            $body = '';
        }

        $teamReason = self::find($trustRows, ['doi ngu nhan su', 'doi ngu', 'experienced staff', 'team']);
        $supportReason = self::find($trustRows, ['dich vu ho tro', 'cham soc khach hang', 'customer support', 'support']);
        $warrantyReason = self::find($trustRows, ['bao hanh', 'warranty']);
        $authenticReason = self::find($trustRows, ['chinh hang', 'genuine', 'authentic']);
        $coreReason = self::find($compassRows, ['gia tri cot loi', 'core value']);

        $people = $team[0] ?? [];
        $peopleImage = $this->image($people['image'] ?? '', 'upload/news/');
        if ($peopleImage === '') {
            // The promotional picture that opens the contact form doubles as the team picture.
            $adv = $this->db->table('banners')->where('placement', 'adv')->where('link', '#ptsContactModal')->orderBy('sort_order')->orderByDesc('id')->pluck('image')
                ->map(fn ($image) => $this->image($image, 'upload/photo/'))->first(fn ($image) => $image !== '');
            $peopleImage = (string) $adv;
        }

        $card = fn (array $row, string $fallbackDescription) => [
            'title' => self::text($row['subtitle']) ?: self::text($row['title']),
            'description' => self::text($row['summary']) ?: $fallbackDescription,
            'image' => $this->image($row['image'], 'upload/news/'),
        ];
        $principles = array_map(fn ($row) => $card($row, self::sentences($row['body'], 1)), $principleRows);
        if (! $principles) {
            foreach ([[$teamReason, 'principleExpertise'], [$supportReason, 'principleSupport'], [$coreReason, 'principleCustomer']] as [$row, $key]) {
                $principles[] = ['title' => $l[$key], 'description' => self::text($row['summary'] ?? '') ?: $l[$key.'Description'], 'image' => $this->image($row['image'] ?? '', 'upload/news/')];
            }
        }

        // Reasons flagged "about" in Admin, else the four classic reasons found by name.
        $selected = array_values(array_filter($trustRows, fn ($row) => in_array('about', $row['flags'], true)));
        if (! $selected) {
            foreach ([$authenticReason, $teamReason, $supportReason, $warrantyReason] as $row) {
                if (! empty($row['id'])) {
                    $selected[(int) $row['id']] = $row;
                }
            }
        }
        $trust = [];
        foreach (array_slice(array_values($selected), 0, 4) as $row) {
            $name = self::normalise($row['title']);
            $has = fn (string ...$needles) => array_any($needles, fn ($n) => str_contains($name, $n));
            $fallback = match (true) {
                $has('chinh hang', 'genuine', 'authentic') => $l['trustAuthentic'],
                $has('doi ngu', 'team', 'staff') => $l['trustTeam'],
                $has('bao hanh', 'warranty') => $l['trustWarranty'],
                $has('ho tro', 'support') => $l['trustSupport'],
                default => $l['trustDefault'],
            };
            $trust[] = $card($row, $fallback);
        }
        $compass = array_map(fn ($row) => $card($row, self::sentences($row['body'], 1)), array_slice($compassRows, 0, 3));

        $cta = $this->firstBanner('about-cta');
        $seoRow = $this->db->table('seo_metas')->where('page', 'gioi-thieu')->first(['title', 'description', 'keywords', 'image']);
        $options = $this->settings->options();
        $hotline = is_scalar($options['hotline'] ?? null) ? trim((string) $options['hotline']) : '';
        $zaloValue = is_scalar($options['zalo'] ?? null) ? (string) $options['zalo'] : '';
        $zalo = ! empty($zaloValue) ? (string) preg_replace('/[^0-9]/', '', $zaloValue) : '';
        $zalo = trim($zalo);
        $showroom = $this->firstBanner('about-showroom');

        return [
            'schemaVersion' => 2,
            'locale' => 'vi',
            'path' => '/gioi-thieu/',
            'layout' => $layout ?? app(PageLayouts::class)->published('about'),
            'title' => 'Giới thiệu Phúc Thịnh Solar',
            'seo' => [
                'title' => self::text(Text::flat((string) $seoRow?->title)),
                'description' => self::text(Text::flat((string) $seoRow?->description)),
                'keywords' => self::text(Text::flat((string) $seoRow?->keywords)),
                'image' => $this->urls->visual((string) $seoRow?->image, 'upload/seopage/'),
            ],
            'story' => ['eyebrow' => $l['storyEyebrow'], 'title' => $l['storyTitle'], 'intro' => $intro, 'body' => $body, 'image' => $this->image($story['image'] ?? '', 'upload/news/')],
            'history' => ['title' => self::text($historyRecord['subtitle'] ?? '') ?: self::text($historyRecord['title'] ?? ''), 'image' => $this->image($historyRecord['image'] ?? '', 'upload/news/'), 'items' => []],
            'people' => [
                'eyebrow' => self::text($people['subtitle'] ?? '') ?: $l['peopleEyebrow'],
                'title' => self::text($people['title'] ?? '') ?: $l['peopleTitle'],
                'intro' => self::text($people['summary'] ?? '') ?: $l['peopleFallback'],
                'body' => self::sentences($people['body'] ?? '', 2),
                'image' => $peopleImage,
                'principles' => $principles,
            ],
            'trust' => ['title' => $l['trustTitle'], 'items' => $trust],
            'compass' => ['title' => $l['compassTitle'], 'items' => $compass],
            'certificates' => ['title' => $l['certTitle'], 'intro' => $l['certIntro'], 'items' => $this->certificates()],
            'partners' => $this->partners(),
            'cta' => [
                'title' => self::text($cta?->title) ?: $l['contactTitle'],
                'description' => self::text($cta?->summary) ?: $l['contactIntro'],
                'image' => $this->image($cta?->image, 'upload/photo/'),
                'href' => self::safeLink($cta?->link) ?: null,
                'buttonLabel' => $l['contactPrimary'],
                'hotline' => $hotline === '' ? null : ['label' => 'Gọi ngay hotline', 'text' => $hotline, 'tel' => preg_replace('/[^0-9+]/', '', $hotline)],
                'zaloUrl' => $zalo === '' || strlen($zalo) > 20 ? null : 'https://zalo.me/'.$zalo,
            ],
            'labels' => [
                'story' => 'Về Phúc Thịnh Solar',
                'history' => 'Lịch sử phát triển',
                'people' => 'Con người',
                'compass' => 'Tầm nhìn và sứ mệnh',
                'certificates' => 'Năng lực và chứng nhận',
                'timeline' => $l['timelineLabel'],
                'certLabel' => $l['certLabel'],
                'certAll' => $l['certAll'],
                'certDetail' => $l['certDetail'],
            ],
            'showroomImage' => $this->image($showroom?->image, 'upload/photo/'),
        ];
    }

    /** Featured certificates first (up to three), then the rest in Admin order. */
    /**
     * Brand wall: visible brands that have a logo, in Admin order; those with products on sale link to them.
     *
     * @return list<array{id: int, name: string, logo: string, href: string|null}>
     */
    private function partners(): array
    {
        $partners = [];
        foreach ($this->db->table('brands as b')->where('b.is_visible', true)->orderBy('b.sort_order')->orderByDesc('b.id')
            ->select(['b.id', 'b.name', 'b.image'])
            ->selectRaw('exists(select 1 from products p where p.brand_id = b.id and p.is_visible = 1) as has_products')->get() as $brand) {
            $logo = Brand::logoUrl($brand->image);
            $name = self::text($brand->name);
            if ($logo !== '' && $name !== '') {
                $partners[] = ['id' => (int) $brand->id, 'name' => $name, 'logo' => $logo, 'href' => $brand->has_products ? '/san-pham/?brand='.(int) $brand->id : null];
            }
        }

        return $partners;
    }

    private function certificates(): array
    {
        $rows = $this->db->table('banners')->where('placement', 'chungnhan')->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')
            ->get(['id', 'title', 'summary', 'image', 'is_featured']);
        $featured = $rows->where('is_featured', true);
        $cards = ($featured->isNotEmpty() ? $featured : $rows)->take(3);
        $ordered = $cards->concat($rows->whereNotIn('id', $cards->pluck('id')));

        return $ordered->map(function ($row) {
            $image = $this->image($row->image, 'upload/photo/');

            return ['id' => (int) $row->id, 'name' => self::text($row->title), 'description' => self::text($row->summary), 'image' => $image,
                'href' => $image !== '' ? $image : null, 'featured' => (bool) $row->is_featured];
        })->values()->all();
    }

    /**
     * Visible items of an About section in Admin order; text columns are never null.
     *
     * @return list<array{id: int, title: string, subtitle: string, summary: string, body: string, image: string, flags: list<string>}>
     */
    private function section(string $section, int $limit = 0): array
    {
        return $this->db->table('page_items')->where('section', $section)->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')
            ->when($limit > 0, fn ($q) => $q->limit($limit))->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'title' => (string) $row->title, 'subtitle' => (string) $row->subtitle, 'summary' => (string) $row->summary,
                'body' => (string) $row->body, 'image' => (string) $row->image, 'flags' => array_values((array) json_decode((string) $row->flags, true))])->all();
    }

    private function firstBanner(string $placement): ?object
    {
        return $this->db->table('banners')->where('placement', $placement)->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')->first();
    }

    private function image(mixed $value, string $folder): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || basename($value) !== $value || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) {
            return '';
        }

        return $this->urls->exists($folder.$value) ? '/'.$folder.$value : '';
    }

    private static function text(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $value = (string) preg_replace('~<(?:br\s*/?|/p|/li)>~i', ' ', html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'));

        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
    }

    private static function sentences(mixed $value, int $limit): string
    {
        $plain = self::text($value);
        if ($plain === '') {
            return '';
        }
        $sentences = preg_split('/(?<=[.!?…])\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);

        return trim(implode(' ', array_slice($sentences ?: [$plain], 0, max(1, $limit))));
    }

    private static function normalise(mixed $value): string
    {
        $plain = self::text($value);

        return $plain === '' ? '' : str_replace('-', ' ', Text::changeTitle($plain));
    }

    /** First row whose normalised title contains one of the needles. */
    private static function find(array $rows, array $needles): array
    {
        foreach ($rows as $row) {
            $haystack = self::normalise($row['title']);
            foreach ($needles as $needle) {
                if (str_contains($haystack, self::normalise($needle))) {
                    return $row;
                }
            }
        }

        return [];
    }

    private static function safeLink(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '#ptsContactModal' || ! preg_match('~^(?:https?://[^\s]+|/?[a-zA-Z0-9][a-zA-Z0-9/_?&=.#%-]*)$~', $value)) {
            return null;
        }

        return $value;
    }
}
