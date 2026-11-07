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

    public function handle(): int
    {
        $endpoint = (string) $this->argument('endpoint');
        $cases = match ($endpoint) {
            'product-detail' => DB::connection('legacy')->table('table_product')->where('type', 'san-pham')
                ->whereRaw("find_in_set('hienthi', status)")->orderBy('id')->pluck('slugvi')->map(fn ($slug) => ['slug' => (string) $slug])->all(),
            default => null,
        };
        if ($cases === null) {
            $this->error("Unknown endpoint {$endpoint}");

            return self::INVALID;
        }
        $cases = array_merge($cases, [['slug' => 'khong-ton-tai-xyz'], ['slug' => 'Bad Slug'], ['slug' => 'a', 'extra' => '1']]);
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
