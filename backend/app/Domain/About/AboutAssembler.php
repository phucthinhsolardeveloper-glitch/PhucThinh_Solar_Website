<?php

namespace App\Domain\About;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Legacy\Settings\LegacySettings;
use App\Legacy\Support\LegacyText;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * About page (api/pts_v1_about.php, schema 2). History items stay empty on purpose: the history is
 * written in the storefront (AboutHistory.vue) because the old CMS timeline said 2019.
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

    public function __construct(private readonly ProductMediaUrls $urls, private readonly LegacySettings $settings)
    {
        $this->db = DB::connection('legacy');
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $l = self::LABELS;
        $history = $this->news('lich-su', 8);
        $compassRows = $this->news('su-menh', 6);
        $trustRows = $this->news('ly-do-chon-chung-toi');
        $team = $this->news('about-team', 1);
        $principleRows = $this->news('about-principle', 3);

        $story = self::find($history, ['ve chung toi']) ?: ($history[0] ?? []);
        $historyRecord = self::find($history, ['lich su', 'hinh thanh']) ?: $story;
        $intro = self::text($story['descvi'] ?? '') ?: self::sentences($story['contentvi'] ?? '', 2);
        $body = self::sentences($story['contentvi'] ?? '', 4);
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
        $peopleImage = $this->image($people['photo'] ?? '', 'upload/news/');
        if ($peopleImage === '') {
            foreach ($this->rows("select namevi,descvi,link,photo from table_photo where type='adv' order by numb,id desc") as $adv) {
                if (($adv['link'] ?? '') === '#ptsContactModal' && ($peopleImage = $this->image($adv['photo'], 'upload/photo/')) !== '') {
                    break;
                }
            }
        }

        $card = fn (array $row, string $fallbackDescription) => [
            'title' => self::text($row['title_indexvi'] ?? $row['namevi'] ?? ''),
            'description' => self::text($row['descvi'] ?? '') ?: $fallbackDescription,
            'image' => $this->image($row['photo'] ?? '', 'upload/news/'),
        ];
        $principles = array_map(fn ($row) => $card($row, self::sentences($row['contentvi'] ?? '', 1)), $principleRows);
        if (! $principles) {
            foreach ([[$teamReason, 'principleExpertise'], [$supportReason, 'principleSupport'], [$coreReason, 'principleCustomer']] as [$row, $key]) {
                $principles[] = ['title' => $l[$key], 'description' => self::text($row['descvi'] ?? '') ?: $l[$key.'Description'], 'image' => $this->image($row['photo'] ?? '', 'upload/news/')];
            }
        }

        // Reasons ticked "about" in Admin, else the four classic reasons found by name.
        $selected = array_values(array_filter($trustRows, fn ($row) => ! empty($row['status']) && in_array('about', explode(',', $row['status']), true)));
        if (! $selected) {
            foreach ([$authenticReason, $teamReason, $supportReason, $warrantyReason] as $row) {
                if (! empty($row['id'])) {
                    $selected[(int) $row['id']] = $row;
                }
            }
        }
        $trust = [];
        foreach (array_slice(array_values($selected), 0, 4) as $row) {
            $name = self::normalise($row['namevi'] ?? '');
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
        $compass = array_map(fn ($row) => $card($row, self::sentences($row['contentvi'] ?? '', 1)), array_slice($compassRows, 0, 3));

        $cta = $this->rows("select namevi,descvi,link,photo,status from table_photo where type='about-cta' and find_in_set('hienthi',status) order by numb,id desc limit 0,1")[0] ?? [];
        $seoRow = (array) ($this->db->selectOne("select titlevi,descriptionvi,keywordsvi,photo from table_seopage where type='gioi-thieu' limit 0,1") ?? []);
        $options = $this->settings->options();
        $hotline = is_scalar($options['hotline'] ?? null) ? trim((string) $options['hotline']) : '';
        $zaloValue = is_scalar($options['zalo'] ?? null) ? (string) $options['zalo'] : '';
        $zalo = ! empty($zaloValue) ? (string) preg_replace('/[^0-9]/', '', $zaloValue) : '';
        $zalo = trim($zalo);
        $showroom = $this->db->selectOne("select photo from table_photo where type='about-showroom' and act='photo_static' and find_in_set('hienthi',status) limit 0,1");

        return [
            'schemaVersion' => 2,
            'locale' => 'vi',
            'path' => '/gioi-thieu/',
            'title' => 'Giới thiệu Phúc Thịnh Solar',
            'seo' => [
                'title' => self::text(LegacyText::flat($seoRow['titlevi'] ?? '')),
                'description' => self::text(LegacyText::flat($seoRow['descriptionvi'] ?? '')),
                'keywords' => self::text(LegacyText::flat($seoRow['keywordsvi'] ?? '')),
                'image' => $this->urls->visual($seoRow['photo'] ?? '', 'upload/seopage/'),
            ],
            'story' => ['eyebrow' => $l['storyEyebrow'], 'title' => $l['storyTitle'], 'intro' => $intro, 'body' => $body, 'image' => $this->image($story['photo'] ?? '', 'upload/news/')],
            'history' => ['title' => self::text($historyRecord['title_indexvi'] ?? $historyRecord['namevi'] ?? ''), 'image' => $this->image($historyRecord['photo'] ?? '', 'upload/news/'), 'items' => []],
            'people' => [
                'eyebrow' => self::text($people['title_indexvi'] ?? '') ?: $l['peopleEyebrow'],
                'title' => self::text($people['namevi'] ?? '') ?: $l['peopleTitle'],
                'intro' => self::text($people['descvi'] ?? '') ?: $l['peopleFallback'],
                'body' => self::sentences($people['contentvi'] ?? '', 2),
                'image' => $peopleImage,
                'principles' => $principles,
            ],
            'trust' => ['title' => $l['trustTitle'], 'items' => $trust],
            'compass' => ['title' => $l['compassTitle'], 'items' => $compass],
            'certificates' => ['title' => $l['certTitle'], 'intro' => $l['certIntro'], 'items' => $this->certificates()],
            'cta' => [
                'title' => self::text($cta['namevi'] ?? '') ?: $l['contactTitle'],
                'description' => self::text($cta['descvi'] ?? '') ?: $l['contactIntro'],
                'image' => $this->image($cta['photo'] ?? '', 'upload/photo/'),
                'href' => self::safeLink($cta['link'] ?? '') ?: null,
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
            'showroomImage' => $this->image($showroom->photo ?? '', 'upload/photo/'),
        ];
    }

    /** Featured certificates first (up to three), then the rest in Admin order. */
    private function certificates(): array
    {
        $rows = $this->rows("select id,namevi,descvi,link,photo,status from table_photo where type='chungnhan' and find_in_set('hienthi',status) order by numb,id desc");
        $flags = fn ($row) => array_filter(array_map('trim', explode(',', (string) ($row['status'] ?? ''))));
        $featured = array_values(array_filter($rows, fn ($row) => in_array('noibat', $flags($row), true)));
        $cards = array_slice($featured ?: $rows, 0, 3);
        $cardIds = array_column($cards, 'id');
        $items = [];
        foreach (array_merge($cards, array_values(array_filter($rows, fn ($row) => ! in_array($row['id'], $cardIds, true)))) as $row) {
            if ((int) $row['id'] <= 0) {
                continue;
            }
            $image = $this->image($row['photo'], 'upload/photo/');
            $items[] = ['id' => (int) $row['id'], 'name' => self::text($row['namevi']), 'description' => self::text($row['descvi']), 'image' => $image,
                'href' => $image !== '' ? $image : null, 'featured' => in_array('noibat', $flags($row), true)];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private function news(string $type, int $limit = 0): array
    {
        return $this->rows("select namevi,title_indexvi,descvi,contentvi,solieu,id,photo,status from table_news where type='{$type}' and find_in_set('hienthi',status) order by numb,id desc"
            .($limit > 0 ? ' limit 0,'.$limit : ''));
    }

    /** @return list<array<string, mixed>> */
    private function rows(string $sql): array
    {
        return array_map(fn ($row) => (array) $row, $this->db->select($sql));
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

        return $plain === '' ? '' : str_replace('-', ' ', LegacyText::changeTitle($plain));
    }

    /** First row whose normalised name contains one of the needles (ptsAboutFind). */
    private static function find(array $rows, array $needles): array
    {
        foreach ($rows as $row) {
            $haystack = self::normalise($row['namevi'] ?? '');
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
