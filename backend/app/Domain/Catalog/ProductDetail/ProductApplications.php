<?php

namespace App\Domain\Catalog\ProductDetail;

use App\Support\Text;

/**
 * "Where to use it" cards (ptsPdpApplicationsDto), buying cues (ptsPdpBuyingGuidesDto) and documents
 * (ptsPdpDocumentsDto). Cards come from the content section that follows an "Ứng dụng"/"Phù hợp" heading;
 * without one, the profile's stock use cases fill in, picked by product type (productPdpUseCaseVariant).
 */
class ProductApplications
{
    private const DOCUMENT_TYPES = ['datasheet', 'manual', 'warranty', 'certificate', 'other'];

    public function __construct(
        private readonly ProductSpecs $specs,
        private readonly ProductMediaUrls $urls,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $blocks  resolved content blocks
     * @return array{title: string, items: list<array{title: string, text: string, image: string}>}|null
     */
    public function cards(array $blocks, array $row, array $list, string $profileKey): ?array
    {
        $variant = $this->variant($row, $list, $profileKey);
        $items = $this->fromContent($blocks);
        $defaults = $this->defaults($profileKey, $variant);
        if (! $items && $profileKey !== 'general') {
            $items = $defaults;
        }
        if (! $items) {
            return null;
        }

        $seen = [];
        $cards = [];
        foreach ($items as $index => $item) {
            $title = $item['title'];
            $image = (string) (array_key_exists('image', $item) ? $item['image'] : $item['photo']);
            if ($image === '' && $profileKey !== 'general') {
                $image = $this->photo($profileKey, $title, $index, $variant);
            }
            $key = strtolower(trim($image));
            if ($profileKey !== 'general' && $key !== '' && isset($seen[$key])) {
                $candidates = [$this->photo($profileKey, $title, $index, $variant)];
                if ($defaults) {
                    $candidates[] = $defaults[$index % count($defaults)]['photo'];
                    $candidates[] = $defaults[($index + 1) % count($defaults)]['photo'];
                }
                foreach ($candidates as $candidate) {
                    $candidateKey = strtolower(trim((string) $candidate));
                    if ($candidateKey !== '' && ! isset($seen[$candidateKey])) {
                        [$image, $key] = [$candidate, $candidateKey];
                        break;
                    }
                }
            }
            $url = $this->urls->application($image);
            $cards[] = ['title' => Text::plain($title), 'text' => Text::plain($item['text']), 'image' => $url];
            if ($key !== '' && $url !== '') {
                $seen[$key] = true;
            }
        }

        return [
            'title' => Text::plain($this->specs->profile($profileKey)['application_title'] ?? ''),
            'items' => array_slice($cards, 0, 3),
        ];
    }

    /** @return list<array{key: string, label: string, value: string}> up to three profile fields that are not metrics */
    public function buyingGuides(array $row, string $profileKey): array
    {
        $profile = $this->specs->profile($profileKey);
        $metrics = (array) ($profile['metrics'] ?? []);
        $guides = [];
        foreach ((array) ($profile['fields'] ?? []) as $key => $label) {
            if (! preg_match('/^[a-z0-9_]+$/', (string) $key) || in_array($key, $metrics, true)) {
                continue;
            }
            $value = Text::plain($this->specs->value($row, (string) $key));
            $label = Text::plain($label);
            if ($label === '' || $value === '') {
                continue;
            }
            $guides[] = ['key' => (string) $key, 'label' => $label, 'value' => $value];
            if (count($guides) >= 3) {
                break;
            }
        }

        return $guides;
    }

    /**
     * @param  iterable<array<string, mixed>>  $rows  product documents (document_type, title, file_path)
     * @return list<array{type: string, title: string, url: string}>
     */
    public function documents(iterable $rows): array
    {
        $documents = [];
        $seen = [];
        foreach ($rows as $row) {
            $type = in_array($row['document_type'] ?? '', self::DOCUMENT_TYPES, true) ? $row['document_type'] : '';
            $title = Text::plain($row['title'] ?? '');
            $url = $this->urls->document($row['file_path'] ?? '');
            if ($type === '' || $title === '' || $url === '' || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $documents[] = ['type' => $type, 'title' => $title, 'url' => $url];
            if (count($documents) >= 30) {
                break;
            }
        }

        return $documents;
    }

    /** @return list<array{title: string, text: string, image: string}> */
    private function fromContent(array $blocks): array
    {
        $collect = false;
        $items = [];
        foreach ($blocks as $block) {
            if (! is_array($block) || empty($block['type'])) {
                continue;
            }
            $type = (string) $block['type'];
            if ($type === 'heading') {
                $isApplication = ProductContentPresenter::isApplicationHeading($block['text'] ?? '');
                if ($collect && ! $isApplication) {
                    break;
                }
                $collect = $isApplication;

                continue;
            }
            if (! $collect) {
                continue;
            }
            if ($type === 'media') {
                $image = $this->urls->application($block['photo'] ?? '');
                $title = Text::plain(strip_tags((string) ($block['title'] ?? '')));
                $text = Text::plain(strip_tags((string) ($block['text'] ?? '')));
                if ($image !== '' || $title !== '' || $text !== '') {
                    $items[] = ['title' => $title !== '' ? $title : 'Ứng dụng sản phẩm', 'text' => $text, 'image' => $image];
                }
            } elseif ($type === 'list') {
                foreach ((array) ($block['items'] ?? []) as $entry) {
                    $text = Text::plain($entry);
                    if ($text === '') {
                        continue;
                    }
                    $parts = preg_split('/\s+(?:cần|ưu tiên|phù hợp)\s+/u', $text, 2) ?: [$text];
                    $title = trim($parts[0], ' .,:;-');
                    $parts = explode(',', $title, 2);
                    if (! empty($parts[0])) {
                        $title = trim($parts[0]);
                    }
                    if (mb_strlen($title) > 58) {
                        $title = rtrim(mb_substr($title, 0, 55)).'…';
                    }
                    if ($title !== '') {
                        $items[] = ['title' => $title, 'text' => $text, 'image' => ''];
                    }
                    if (count($items) >= 3) {
                        break 2;
                    }
                }
            }
            if (count($items) >= 3) {
                break;
            }
        }

        return $items;
    }

    /** @return list<array{title: string, text: string, photo: string}> */
    private function defaults(string $profileKey, string $variant): array
    {
        $useCases = config('pdp.use_cases');

        return $useCases['variants'][$variant] ?? $useCases['profiles'][$profileKey] ?? $useCases['profiles']['lighting'];
    }

    /** Stock picture for a card title (productPdpUseCasePhoto). */
    private function photo(string $profileKey, string $title, int $index, string $variant): string
    {
        $defaults = $this->defaults($profileKey, $variant);
        $title = mb_strtolower(trim(strip_tags($title)));
        $has = fn (string ...$needles) => array_any($needles, fn ($n) => str_contains($title, $n));
        if ($variant !== '' && $defaults) {
            return $defaults[$index % count($defaults)]['photo'];
        }
        $asset = 'assets/images/site-visuals/pdp-usecase-v2/';
        if ($profileKey === 'lighting') {
            if ($has('đường nội bộ', 'bãi đỗ')) {
                return $defaults[0]['photo'];
            }
            if ($has('tường rào', 'cổng')) {
                return $defaults[1]['photo'];
            }
            if ($has('lối đi', 'sân nhà')) {
                return $asset.'lighting-side-path.webp';
            }
            if ($has('nhà vườn', 'homestay')) {
                return $asset.'lighting-garden-homestay.webp';
            }
            if ($has('khuôn viên', 'công trình', 'nhà xưởng', 'resort')) {
                return $defaults[2]['photo'];
            }
        }
        if ($profileKey === 'solar_panel') {
            if ($has('trang trại', 'farm')) {
                return $defaults[2]['photo'];
            }
            if ($has('nhà xưởng', 'thương mại', 'công nghiệp')) {
                return $defaults[1]['photo'];
            }
            if ($has('mái', 'nhà')) {
                return $defaults[0]['photo'];
            }
        }
        if ($profileKey === 'pump_controller' || $profileKey === 'pump_hardware') {
            if ($has('nhà kính', 'ao hồ', 'hồ chứa')) {
                return $defaults[2]['photo'];
            }
            if ($has('sinh hoạt', 'bồn', 'bể', 'giếng')) {
                return $defaults[1]['photo'];
            }
            if ($has('tưới', 'ruộng', 'vườn', 'nông nghiệp')) {
                return $defaults[0]['photo'];
            }
        }

        return $defaults ? $defaults[$index % count($defaults)]['photo'] : '';
    }

    /** Which stock picture set fits this product. */
    private function variant(array $row, array $list, string $profileKey): string
    {
        $name = htmlspecialchars_decode((string) ($row['name'] ?? ''), ENT_QUOTES);
        $listSlug = (string) ($list['slug'] ?? '');
        $context = '';
        foreach (['applications', 'installation', 'highlights', 'summary'] as $field) {
            if (isset($row[$field]) && trim((string) $row[$field]) !== '') {
                $context .= ' '.htmlspecialchars_decode(strip_tags((string) $row[$field]), ENT_QUOTES);
            }
        }
        $context = mb_strtolower($context);
        $identity = mb_strtolower(trim($name.' '.($row['slug'] ?? '').' '.$listSlug));
        $haystack = mb_strtolower(trim($identity.' '.$context));
        $in = fn (string $subject, string ...$needles) => array_any($needles, fn ($n) => str_contains($subject, $n));

        if ($profileKey === 'lighting') {
            return match (true) {
                $in($identity, 'trụ cổng', 'tru-cong', 'cần đèn', 'can-den') || $listSlug === 'tru-den' => 'lighting_pole',
                $in($identity, 'ufo') => 'lighting_ufo',
                $in($identity, 'treo tường', 'treo-tuong', 'tường') => 'lighting_wall',
                $in($identity, 'ốp trần', 'op-tran', 'trần') => 'lighting_ceiling',
                $listSlug === 'den-san-vuon' => 'lighting_garden',
                $in($identity, 'pha', 'đèn pha') => 'lighting_flood',
                $in($identity, 'đường', 'den-duong') || $in($context, 'đường nội bộ', 'tuyến đường', 'đường nông thôn', 'khu dân cư') => 'lighting_street',
                $in($identity, 'sân vườn', 'san-vuon') => 'lighting_garden',
                default => 'lighting_flood',
            };
        }
        if ($profileKey === 'accessory') {
            if ($in($haystack, 'điều khiển sạc', 'dieu-khien-sac', 'mppt', 'pwm', 'meter', 'metter', 'ems', 'bám tải', 'bam-tai')) {
                return 'accessory_controller';
            }
            if ($in($haystack, 'cáp', 'cap-', 'dây', 'day-', 'ống', 'ong-', 'ruột gà', 'keo', 'băng keo', 'tiếp địa', 'tiep-dia', 'mc4', 'đầu nối')) {
                return 'accessory_cable';
            }
        }

        return '';
    }
}
