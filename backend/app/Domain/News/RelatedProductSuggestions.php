<?php

namespace App\Domain\News;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Support\Text;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Suggests catalog products for an editorial article from the equipment topic it actually
 * discusses (module, grid-tie inverter, hybrid inverter, storage battery, solar light, solar pump).
 *
 * Invariants:
 * - A topic only counts when a topic-specific phrase appears in the title, summary, SEO keywords
 *   or a heading, or at least three times in the body. Generic words ("năng lượng", "điện mặt trời")
 *   are never phrases, so they cannot cause a match on their own.
 * - General market news (category tin-moi-nhat) gets suggestions only when its title names a topic.
 * - Only live products are returned: visible, in a visible category, with a valid slug and an
 *   existing product image. Nothing here writes data; the editor confirms every suggestion.
 */
final class RelatedProductSuggestions
{
    public const MAX = 4;

    /**
     * category: catalog category slugs (descendants are included), in preference order.
     * strong: phrases specific to the topic. weak: supporting phrases that never qualify alone.
     *
     * @var array<string, array{label: string, categories: list<string>, strong: list<string>, weak: list<string>}>
     */
    public const TOPICS = [
        'pv_module' => [
            'label' => 'Tấm pin năng lượng mặt trời',
            'categories' => ['tam-pin-nang-luong-mat-troi'],
            'strong' => ['datasheet tấm pin', 'công suất tấm pin', 'công suất module', 'hiệu suất module', 'tấm pin màu',
                'module', 'pmax', 'nmot', 'bifacial', '2 mặt kính', 'all black', 'half-cut'],
            'weak' => ['tấm pin', 'wp', 'kwp'],
        ],
        'grid_inverter' => [
            'label' => 'Inverter hòa lưới',
            'categories' => ['hoa-luoi'],
            'strong' => ['inverter hòa lưới', 'biến tần hòa lưới', 'hệ hòa lưới', 'hòa lưới', 'on-grid', 'chống đảo lưới', 'anti-islanding'],
            'weak' => ['inverter', 'biến tần', 'mppt'],
        ],
        'hybrid_inverter' => [
            'label' => 'Inverter hybrid',
            'categories' => ['hybrid-luu-tru'],
            'strong' => ['inverter hybrid', 'hybrid', 'eps', 'tải dự phòng', 'backup'],
            'weak' => ['inverter', 'lưu trữ', 'dự phòng'],
        ],
        'battery_storage' => [
            'label' => 'Pin lưu trữ',
            'categories' => ['pin-battery', 'he-thong-ess', 'pin-luu-tru'],
            'strong' => ['pin lưu trữ', 'hệ thống lưu trữ', 'battery storage', 'ess', 'bess'],
            'weak' => ['lưu trữ', 'dự phòng', 'buổi tối'],
        ],
        'solar_light' => [
            'label' => 'Đèn năng lượng mặt trời',
            'categories' => ['den-duong', 'den-pha-nang-luong-mat-troi', 'den-nlmt'],
            'strong' => ['đèn năng lượng mặt trời', 'đèn nlmt', 'đèn solar', 'đèn đường', 'đèn pha', 'đèn sân vườn', 'lumen', 'lux'],
            'weak' => ['đèn', 'ip65', 'ip66'],
        ],
        'solar_pump' => [
            'label' => 'Bơm nước năng lượng mặt trời',
            'categories' => ['bien-tan-bom-nlmt', 'may-bom-nlmt', 'bien-tan-bom'],
            'strong' => ['máy bơm năng lượng mặt trời', 'bơm năng lượng mặt trời', 'bơm nlmt', 'biến tần bơm', 'bơm tưới'],
            'weak' => ['bơm', 'giếng'],
        ],
    ];

    private const PLACES = ['title' => 'tiêu đề', 'keywords' => 'từ khóa SEO', 'summary' => 'mô tả ngắn', 'headings' => 'đề mục'];

    public function __construct(private readonly ProductMediaUrls $urls) {}

    /**
     * Topics an article discusses, strongest first.
     *
     * @param  array{title?: ?string, summary?: ?string, keywords?: ?string, body?: ?string, category?: ?string}  $article
     * @return array<string, array{score: int, evidence: string}>
     */
    public function topics(array $article): array
    {
        $body = (string) ($article['body'] ?? '');
        preg_match_all('~<h[2-4][^>]*>(.*?)</h[2-4]>~isu', $body, $headings);
        $parts = [
            'title' => self::normalize((string) ($article['title'] ?? '')),
            'keywords' => self::normalize((string) ($article['keywords'] ?? '')),
            'summary' => self::normalize((string) ($article['summary'] ?? '')),
            'headings' => self::normalize(implode(' . ', $headings[1])),
        ];
        $text = self::normalize($body);
        $marketNews = ($article['category'] ?? '') === 'tin-moi-nhat';

        $found = [];
        foreach (self::TOPICS as $key => $topic) {
            $score = 0;
            $evidence = '';
            $qualified = false;
            foreach ($topic['strong'] as $phrase) {
                foreach ($parts as $place => $value) {
                    if (self::count($value, $phrase) > 0) {
                        $score += $place === 'title' ? 9 : ($place === 'headings' ? 4 : 6);
                        $qualified = $qualified || ! $marketNews || $place === 'title';
                        $evidence = $evidence !== '' ? $evidence : '“'.$phrase.'” ở '.self::PLACES[$place];
                    }
                }
                $inBody = self::count($text, $phrase);
                $score += min($inBody, 5) * 2;
                if (! $marketNews && $inBody >= 3) {
                    $qualified = true;
                    $evidence = $evidence !== '' ? $evidence : '“'.$phrase.'” lặp lại '.$inBody.' lần trong bài';
                }
            }
            if (! $qualified) {
                continue;
            }
            foreach ($topic['weak'] as $phrase) {
                $score += min(self::count($text.' '.implode(' ', $parts), $phrase), 5);
            }
            $found[$key] = ['score' => $score, 'evidence' => $evidence];
        }
        uasort($found, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $found;
    }

    /**
     * Up to $limit suggestions spread over the detected topics, skipping $exclude.
     *
     * @param  array{title?: ?string, summary?: ?string, keywords?: ?string, body?: ?string, category?: ?string}  $article
     * @param  list<int>  $exclude
     * @return list<array{id: int, name: string, slug: string, url: string, image: string, category: string, brand: string, topic: string, reason: string}>
     */
    public function suggest(array $article, array $exclude = [], int $limit = self::MAX): array
    {
        $topics = $this->topics($article);
        if ($topics === [] || $limit < 1) {
            return [];
        }
        $text = self::normalize(implode(' ', array_map('strval', [$article['title'] ?? '', $article['summary'] ?? '', $article['keywords'] ?? '', $article['body'] ?? ''])));
        $numbers = [];
        preg_match_all('/(?<![\d.,])(\d{2,4})\s?w(?:p)?(?![\p{L}\p{N}])/u', $text, $matches);
        foreach ($matches[1] as $value) {
            $numbers[(int) $value] = true;
        }

        $pools = [];
        foreach ($topics as $key => $info) {
            $pool = [];
            foreach ($this->liveProducts(self::TOPICS[$key]['categories']) as $product) {
                if (in_array($product['id'], $exclude, true)) {
                    continue;
                }
                $notes = [];
                $rank = $product['category_rank'] * 10 - ($product['is_bestseller'] ? 3 : 0);
                if ($product['brand'] !== '' && self::count($text, self::normalize($product['brand'])) > 0) {
                    $rank -= 20;
                    $notes[] = 'thương hiệu '.$product['brand'].' được nhắc trong bài';
                }
                if ($key === 'pv_module' && $product['power'] > 0 && isset($numbers[$product['power']])) {
                    $rank -= 30;
                    $notes[] = 'công suất '.$product['power'].' Wp trùng mức được nêu trong bài';
                } elseif ($key === 'pv_module' && $product['power'] < 400) {
                    // Small 12/24 V modules belong to lights and off-grid kits, not rooftop sizing topics.
                    $rank += 15;
                }
                $product['rank'] = $rank;
                $product['topic'] = $key;
                $product['reason'] = self::TOPICS[$key]['label'].': '.$info['evidence'].'; sản phẩm thuộc danh mục '
                    .$product['category'].($notes !== [] ? '; '.implode('; ', $notes) : '').'.';
                $pool[] = $product;
            }
            // Newer catalog entries first when nothing else separates two products.
            usort($pool, fn (array $a, array $b) => [$a['rank'], $a['sort_order'], $b['id']] <=> [$b['rank'], $b['sort_order'], $a['id']]);
            if ($pool !== []) {
                $pools[$key] = $pool;
            }
        }

        // Round-robin over topics so a multi-topic article gets each kind of device; within a
        // topic prefer a brand not picked yet.
        $picked = [];
        $brands = [];
        while (count($picked) < $limit && $pools !== []) {
            foreach (array_keys($pools) as $key) {
                if (count($picked) >= $limit) {
                    break;
                }
                $index = 0;
                foreach ($pools[$key] as $i => $candidate) {
                    if (! isset($brands[$key][$candidate['brand']])) {
                        $index = $i;
                        break;
                    }
                }
                $candidate = $pools[$key][$index];
                array_splice($pools[$key], $index, 1);
                if ($pools[$key] === []) {
                    unset($pools[$key]);
                }
                $brands[$key][$candidate['brand']] = true;
                $picked[] = $candidate;
            }
        }

        return array_map(fn (array $p) => [
            'id' => $p['id'], 'name' => $p['name'], 'slug' => $p['slug'], 'url' => '/san-pham/'.$p['slug'],
            'image' => $p['image'], 'category' => $p['category'], 'brand' => $p['brand'],
            'topic' => $p['topic'], 'reason' => $p['reason'],
        ], $picked);
    }

    /**
     * The subset of $ids that may be linked publicly, keyed by id, in the given order.
     *
     * @param  list<int>  $ids
     * @return array<int, array{id: int, name: string, slug: string, image: string, category_slug: string, category: string}>
     */
    public function eligible(array $ids): array
    {
        $rows = $ids === [] ? [] : $this->query()->whereIn('p.id', $ids)->get()->all();
        $found = [];
        foreach ($rows as $row) {
            if (($product = $this->present($row)) !== null) {
                $found[$product['id']] = $product;
            }
        }
        $out = [];
        foreach ($ids as $id) {
            if (isset($found[(int) $id])) {
                $out[(int) $id] = $found[(int) $id];
            }
        }

        return $out;
    }

    /** Category slugs of a topic including all descendants. @return list<string> */
    public function topicCategorySlugs(string $topic): array
    {
        return array_keys($this->categoryRanks(self::TOPICS[$topic]['categories'] ?? []));
    }

    /** @return list<array<string, mixed>> */
    private function liveProducts(array $categorySlugs): array
    {
        $ranks = $this->categoryRanks($categorySlugs);
        if ($ranks === []) {
            return [];
        }
        $out = [];
        foreach ($this->query()->whereIn('c.slug', array_keys($ranks))->get() as $row) {
            if (($product = $this->present($row)) !== null) {
                $out[] = $product + [
                    'category_rank' => $ranks[$product['category_slug']],
                    'brand' => (string) ($row->brand ?? ''),
                    'power' => (int) round((float) ($row->power_watts ?? 0)),
                    'is_bestseller' => (bool) $row->is_bestseller,
                    'sort_order' => (int) $row->sort_order,
                ];
            }
        }

        return $out;
    }

    /** Requested slugs and every descendant category, with the rank of the requested root. @return array<string, int> */
    private function categoryRanks(array $slugs): array
    {
        $all = DB::table('categories')->where('is_visible', true)->get(['id', 'slug', 'parent_id']);
        $ranks = [];
        foreach ($slugs as $rank => $slug) {
            $frontier = $all->where('slug', $slug)->pluck('id')->all();
            while ($frontier !== []) {
                $next = [];
                foreach ($all->whereIn('id', $frontier) as $category) {
                    $ranks[$category->slug] ??= $rank;
                }
                foreach ($all->whereIn('parent_id', $frontier) as $child) {
                    if (! isset($ranks[$child->slug])) {
                        $next[] = $child->id;
                    }
                }
                $frontier = $next;
            }
        }

        return $ranks;
    }

    private function query(): Builder
    {
        return DB::table('products as p')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->where('p.is_visible', true)->where('c.is_visible', true)
            ->select(['p.id', 'p.name', 'p.slug', 'p.image', 'p.power_watts', 'p.is_bestseller', 'p.sort_order',
                'c.slug as category_slug', 'c.name as category', 'b.name as brand']);
    }

    /** @return array{id: int, name: string, slug: string, image: string, category_slug: string, category: string}|null */
    private function present(object $row): ?array
    {
        $slug = Text::slug($row->slug ?? '');
        $image = $this->urls->product($row->image ?? '');
        $name = trim((string) ($row->name ?? ''));
        if ($slug === '' || $slug !== (string) $row->slug || $image === '' || $name === '') {
            return null;
        }

        return ['id' => (int) $row->id, 'name' => $name, 'slug' => $slug, 'image' => $image,
            'category_slug' => (string) $row->category_slug, 'category' => (string) $row->category];
    }

    private static function normalize(string $value): string
    {
        $value = html_entity_decode(strip_tags(str_replace('<', ' <', $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower(\Normalizer::normalize($value, \Normalizer::FORM_C) ?: $value)));
    }

    private static function count(string $haystack, string $phrase): int
    {
        if ($haystack === '' || $phrase === '') {
            return 0;
        }

        return (int) preg_match_all('/(?<![\p{L}\p{N}])'.preg_quote($phrase, '/').'(?![\p{L}\p{N}])/u', $haystack);
    }
}
