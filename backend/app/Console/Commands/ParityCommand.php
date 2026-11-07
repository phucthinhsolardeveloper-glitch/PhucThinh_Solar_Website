<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Shadow comparison: asks the PHP endpoint and the Laravel endpoint the same question for every case
 * and reports differing JSON paths (list indexes folded to [*]). Read-only on both sides.
 */
class ParityCommand extends Command
{
    protected $signature = 'pts:parity {endpoint=product-detail} {--limit=0 : Stop after this many cases} {--show=3 : Example cases per path}';

    protected $description = 'Compare a Laravel v1 endpoint with its PHP original, case by case.';

    /** Fields that differ on every request by design (form tokens), left out of the comparison. */
    private const VOLATILE = ['product-extras' => ['csrfToken'], 'lead-form' => ['csrfToken'], 'cart' => ['csrfToken'], 'checkout' => ['csrfToken', 'checkoutToken']];

    public function handle(): int
    {
        $endpoint = (string) $this->argument('endpoint');
        $cases = match ($endpoint) {
            'product-detail' => DB::connection('legacy')->table('table_product')->where('type', 'san-pham')
                ->whereRaw("find_in_set('hienthi', status)")->orderBy('id')->pluck('slugvi')->map(fn ($slug) => ['slug' => (string) $slug])->all(),
            'catalog' => $this->catalogCases(),
            'site', 'home', 'about' => [[]],
            'contact', 'dealer' => [[], ['x' => '1']],
            'news' => $this->newsCases(),
            'projects' => array_merge([[], ['page' => '2'], ['per_page' => '5', 'page' => '2'], ['page' => '99'], ['per_page' => '49'], ['page' => '0'], ['category' => 'khong-co'], ['x' => '1']],
                DB::connection('legacy')->table('table_news_list')->where('type', 'du-an')->pluck('slugvi')->flatMap(fn ($s) => [['category' => (string) $s], ['category' => (string) $s, 'page' => '2']])->all()),
            'project-detail' => $this->slugCases('du-an'),
            'solutions' => array_merge([[], ['category' => 'khong-co'], ['category' => 'Bad'], ['x' => '1']],
                DB::connection('legacy')->table('table_news_list')->where('type', 'giai-phap')->pluck('slugvi')->map(fn ($s) => ['category' => (string) $s])->all()),
            'solution-detail' => $this->slugCases('giai-phap'),
            'gallery', 'video' => [[], ['p' => '2'], ['p' => '0'], ['p' => 'x'], ['q' => '1']],
            'gallery-detail' => array_merge(DB::connection('legacy')->table('table_product')->where('type', 'thu-vien-anh')->pluck('slugvi')->map(fn ($s) => ['slug' => (string) $s])->all(),
                [['slug' => 'khong-co'], ['slug' => 'Bad'], [], ['slug' => 'a', 'x' => '1']]),
            'brand-detail' => array_merge(
                DB::connection('legacy')->table('table_product_brand')->where('type', 'san-pham')->pluck('slugvi')->flatMap(fn ($s) => [['slug' => (string) $s], ['slug' => (string) $s, 'sort' => 'abc'],
                    ['slug' => (string) $s, 'sort' => 'price-desc', 'price' => '2,3'], ['slug' => (string) $s, 'page' => '2', 'per_page' => '5']])->all(),
                [['slug' => 'khong-co'], ['slug' => 'a', 'price' => '1,1'], ['slug' => 'a', 'x' => '1'], [], ['slug' => 'a', 'sort' => 'A']]),
            'search' => array_merge(array_map(fn ($k) => ['keyword' => $k], ['đèn', 'Blue Carbon', 'BCT-WR10', 'bctwr10', 'inverter', 'pin lưu trữ', '100W', 'IP67', 'Đèn đường', '50%', 'a_b', 'zzzz', 'Deye', 'sun2000', '  đèn  ']),
                [['keyword' => 'đèn', 'page' => '2'], ['keyword' => 'đèn', 'sort' => 'abc'], ['keyword' => 'đèn', 'sort' => 'price'], ['keyword' => 'đèn', 'brand' => '1,2,3'], ['keyword' => 'đèn', 'price' => '1'],
                    ['keyword' => ''], [], ['keyword' => '!!!'], ['keyword' => str_repeat('a', 101)], ['keyword' => 'a', 'sort' => 'x'], ['keyword' => 'a', 'x' => '1'], ['keyword' => 'a', 'page' => '0']]),
            'product-extras' => $this->extrasCases(),
            'public-route-resolve' => $this->routeCases(),
            'lead-form' => [[], ['x' => '1']],
            'checkout-wards' => array_merge(DB::connection('legacy')->table('table_city')->orderBy('id')->pluck('id')->map(fn ($id) => ['city' => (string) $id])->all(),
                [['city' => '0'], ['city' => '999999'], ['city' => 'x'], [], ['city' => '1', 'x' => '1']]),
            'cart', 'checkout', 'checkout-notice' => [[]],
            'order-thanks' => [[], ['token' => 'x'], ['token' => str_repeat('a', 48)], ['token' => str_repeat('a', 48), 'x' => '1']],
            'news-detail' => $this->slugCases('tin-tuc'),
            'policy-detail' => $this->slugCases('chinh-sach'),
            default => null,
        };
        if ($cases === null) {
            $this->error("Unknown endpoint {$endpoint}");

            return self::INVALID;
        }
        if ($endpoint === 'product-detail') {
            $cases = array_merge($cases, [['slug' => 'khong-ton-tai-xyz'], ['slug' => 'Bad Slug'], ['slug' => 'a', 'extra' => '1']]);
        }
        if ((int) $this->option('limit') > 0) {
            $cases = array_slice($cases, 0, (int) $this->option('limit'));
        }

        $legacyBase = rtrim((string) config('legacy.api_base'), '/');
        $paths = [];
        $identical = 0;
        foreach ($cases as $query) {
            $php = Http::timeout(20)->get($legacyBase.'/api/pts_v1_'.str_replace('-', '_', $endpoint).'.php', $query);
            $laravel = app()->handle(Request::create('/api/v1/'.$endpoint, 'GET', $query));
            $left = ['status' => $php->status(), 'body' => $php->json()];
            $right = ['status' => $laravel->getStatusCode(), 'body' => json_decode((string) $laravel->getContent(), true)];
            foreach (self::VOLATILE[$endpoint] ?? [] as $key) {
                unset($left['body'][$key], $right['body'][$key]);
            }
            $diffs = [];
            $this->diff($left, $right, '', $diffs);
            if (! $diffs) {
                $identical++;
            }
            foreach (array_unique($diffs) as $path) {
                $paths[$path][] = (string) json_encode($query, JSON_UNESCAPED_UNICODE);
            }
        }

        $this->info(sprintf('%s: %d/%d cases identical', $endpoint, $identical, count($cases)));
        arsort($paths);
        uasort($paths, fn ($a, $b) => count($b) <=> count($a));
        $rows = [];
        foreach ($paths as $path => $examples) {
            $rows[] = [$path, count($examples), implode(', ', array_slice($examples, 0, (int) $this->option('show')))];
        }
        if ($rows) {
            $this->table(['JSON path lệch', 'Số ca', 'Ví dụ'], $rows);
        }

        return $identical === count($cases) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Every category path, then filters, sorts, paging, keyword and malformed queries on a few of them.
     *
     * @return list<array<string, string>>
     */
    private function catalogCases(): array
    {
        $db = DB::connection('legacy');
        $visible = "find_in_set('hienthi', %s.status)";
        $paths = [''];
        foreach ($db->table('table_product_list as l')->where('l.type', 'san-pham')->whereRaw(sprintf($visible, 'l'))->orderBy('l.id')->get(['l.id', 'l.slugvi']) as $list) {
            $paths[] = $list->slugvi;
            foreach ($db->table('table_product_cat as c')->where('c.id_list', $list->id)->where('c.type', 'san-pham')->whereRaw(sprintf($visible, 'c'))->orderBy('c.id')->pluck('slugvi') as $cat) {
                $paths[] = $list->slugvi.'/'.$cat;
            }
        }
        $cases = array_map(fn ($path) => $path === '' ? [] : ['category' => (string) $path], $paths);
        $brands = $db->table('table_product_brand')->where('type', 'san-pham')->orderBy('id')->limit(3)->pluck('id')->implode(',');
        $lists = array_slice(array_filter($paths, fn ($p) => $p !== '' && ! str_contains((string) $p, '/')), 0, 4);
        $subcats = $db->table('table_product_cat')->where('type', 'san-pham')->orderBy('id')->limit(3)->pluck('id')->implode(',');
        foreach (array_merge([''], $lists) as $path) {
            $base = $path === '' ? [] : ['category' => (string) $path];
            foreach ([
                ['page' => '2'], ['per_page' => '7', 'page' => '3'], ['page' => '999'],
                ['sort' => 'abc'], ['sort' => 'abc-desc'], ['sort' => 'price'], ['sort' => 'price-desc'], ['sort' => 'new'], ['sort' => 'new-desc'], ['sort' => 'other'],
                ['brand' => $brands], ['price' => '1'], ['price' => '2,3'], ['subcategory' => $subcats],
                ['is_need' => 'du-an-cong-trinh'], ['is_need' => 'chieu-sang-gia-dinh,san-vuon-canh-quan'],
                ['keyword' => 'đèn'], ['keyword' => 'Blue Carbon'], ['keyword' => '100W'], ['keyword' => 'zzzz'],
            ] as $extra) {
                $cases[] = $base + $extra;
            }
        }
        foreach ([
            ['category' => 'khong-ton-tai'], ['category' => 'den-nlmt/khong-ton-tai'], ['category' => 'Bad Path'], ['page' => '0'], ['per_page' => '49'],
            ['page' => '10001'], ['brand' => '1,,2'], ['price' => '4'], ['sort' => 'ABC'], ['is_need' => 'du-an-cong-trinh,du-an-cong-trinh'],
            ['is_need' => 'khac'], ['keyword' => str_repeat('a', 101)], ['keyword' => ' '], ['other' => '1'],
        ] as $bad) {
            $cases[] = $bad;
        }

        return $cases;
    }

    /**
     * Every row of a news type by slug (hidden ones included, they must 404), plus malformed queries.
     *
     * @return list<array<string, string>>
     */
    private function slugCases(string $type): array
    {
        $cases = DB::connection('legacy')->table('table_news')->where('type', $type)->where('slugvi', '<>', '')->orderBy('id')
            ->pluck('slugvi')->map(fn ($slug) => ['slug' => (string) $slug])->all();

        return array_merge($cases, [['slug' => 'khong-ton-tai'], ['slug' => 'Bad Slug'], ['slug' => ''], [], ['slug' => 'a', 'x' => '1'], ['slug' => '/a/']]);
    }

    /** Every slug of every routed table, reserved words and malformed input. */
    private function routeCases(): array
    {
        $db = DB::connection('legacy');
        $slugs = [];
        foreach (['product_list', 'product_cat', 'product_item', 'product_sub', 'product_brand', 'product', 'news_list', 'news_cat', 'news_item', 'news_sub', 'news'] as $table) {
            foreach ($db->table('table_'.$table)->where('slugvi', '<>', '')->pluck('slugvi') as $slug) {
                $slugs[(string) $slug] = true;
            }
        }
        $cases = array_map(fn ($slug) => ['slug' => (string) $slug], array_keys($slugs));

        return array_merge($cases, [['slug' => 'lien-he'], ['slug' => 'san-pham'], ['slug' => 'khong-ton-tai-xyz'], ['slug' => 'Bad'], [], ['slug' => 'a', 'x' => '1']]);
    }

    /** @return list<array<string, string>> */
    private function extrasCases(): array
    {
        $db = DB::connection('legacy');
        $cases = $db->table('table_product')->where('type', 'san-pham')->whereRaw("find_in_set('hienthi', status)")->orderBy('id')->pluck('slugvi')->map(fn ($s) => ['slug' => (string) $s])->all();
        foreach ($db->table('table_comment as c')->join('table_product as p', 'p.id', '=', 'c.id_variant')->where('c.id_parent', 0)->select('p.slugvi', 'c.id')->limit(5)->get() as $review) {
            $cases[] = ['slug' => $review->slugvi, 'replyParent' => (string) $review->id, 'replyOffset' => '0'];
            $cases[] = ['slug' => $review->slugvi, 'reviewOffset' => '10'];
        }

        return array_merge($cases, [['slug' => 'khong-co'], ['slug' => 'a', 'replyParent' => '1'], ['slug' => 'a', 'reviewOffset' => '-1'], ['slug' => 'a', 'x' => '1'], []]);
    }

    /** @return list<array<string, string>> */
    private function newsCases(): array
    {
        $db = DB::connection('legacy');
        $cases = [[], ['p' => '2'], ['p' => '999'], ['q' => 'năng lượng'], ['q' => 'pin'], ['q' => 'zzzz'], ['q' => '50%'], ['q' => ''], ['q' => 'đèn', 'p' => '2']];
        foreach ($db->table('table_news_list')->where('type', 'tin-tuc')->pluck('slugvi', 'id') as $id => $list) {
            $cases[] = ['category' => (string) $list];
            $cases[] = ['category' => (string) $list, 'p' => '2'];
            foreach ($db->table('table_news_cat')->where('type', 'tin-tuc')->where('id_list', $id)->pluck('slugvi') as $cat) {
                $cases[] = ['category_type' => 'cat', 'category_path' => $list.'/'.$cat];
            }
        }
        foreach ([['category' => 'khong-co'], ['category' => 'Bad'], ['p' => '0'], ['category_type' => 'cat'], ['category_type' => 'x', 'category' => 'a'],
            ['category_path' => 'a/b'], ['category_type' => 'cat', 'category_path' => 'a//b'], ['category_type' => 'list', 'category_path' => 'a/b'], ['other' => '1']] as $bad) {
            $cases[] = $bad;
        }

        return $cases;
    }

    /** @param list<string> $out */
    private function diff(mixed $a, mixed $b, string $path, array &$out): void
    {
        if (is_array($a) && is_array($b)) {
            $list = array_is_list($a) && array_is_list($b);
            if ($list && count($a) !== count($b)) {
                $out[] = $path.'.length';
            }
            foreach (array_unique(array_merge(array_keys($a), array_keys($b))) as $key) {
                $child = $list ? $path.'[*]' : $path.'.'.$key;
                if (! array_key_exists($key, $a) || ! array_key_exists($key, $b)) {
                    if (! $list) {
                        $out[] = $child.(array_key_exists($key, $a) ? ' (chỉ PHP có)' : ' (chỉ Laravel có)');
                    }

                    continue;
                }
                $this->diff($a[$key], $b[$key], $child, $out);
            }

            return;
        }
        if ($a !== $b) {
            $out[] = $path;
        }
    }
}
