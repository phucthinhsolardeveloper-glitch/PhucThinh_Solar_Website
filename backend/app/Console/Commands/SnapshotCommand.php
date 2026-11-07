<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;

/**
 * Regression check for the public API: every stored case (storage/app/snapshots/<api>.json) is asked
 * again and compared field by field with the stored answer. --save replaces the stored answers after
 * a deliberate change has been reviewed.
 */
class SnapshotCommand extends Command
{
    protected $signature = 'pts:snapshot {api? : One API, e.g. product-detail; all when left out}
        {--save : Store the current answers as the new reference}
        {--show=3 : Example cases per differing path}';

    protected $description = 'Compare public API answers with the stored reference snapshots.';

    /** Fields that differ on every request by design (form tokens). */
    private const VOLATILE = ['product-extras' => ['csrfToken'], 'lead-form' => ['csrfToken'], 'cart' => ['csrfToken'], 'checkout' => ['csrfToken', 'checkoutToken']];

    public function handle(): int
    {
        $dir = storage_path('app/snapshots');
        $apis = $this->argument('api') !== null ? [(string) $this->argument('api')] : array_map(fn ($file) => basename($file, '.json'), glob($dir.'/*.json') ?: []);
        $failed = false;
        foreach ($apis as $api) {
            $failed = ! $this->check($api, $dir.'/'.$api.'.json') || $failed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function check(string $api, string $file): bool
    {
        $stored = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (! is_array($stored)) {
            $this->error("No snapshot for {$api}");

            return false;
        }
        $paths = [];
        $identical = 0;
        $saved = [];
        foreach ($stored as $case) {
            $query = $case['query'];
            $response = app()->handle(Request::create('/api/v1/'.$api, 'GET', $query));
            $now = ['status' => $response->getStatusCode(), 'body' => json_decode((string) $response->getContent(), true)];
            $saved[] = ['query' => $query] + $now;
            $before = ['status' => $case['status'], 'body' => $case['body']];
            foreach (self::VOLATILE[$api] ?? [] as $key) {
                unset($before['body'][$key], $now['body'][$key]);
            }
            $diffs = [];
            $this->diff($before, $now, '', $diffs);
            if (! $diffs) {
                $identical++;
            }
            foreach (array_unique($diffs) as $path) {
                $paths[$path][] = (string) json_encode($query, JSON_UNESCAPED_UNICODE);
            }
        }
        if ($this->option('save')) {
            file_put_contents($file, json_encode($saved, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $this->info(sprintf('%s: %d/%d cases identical%s', $api, $identical, count($stored), $this->option('save') ? ' (saved)' : ''));
        uasort($paths, fn ($a, $b) => count($b) <=> count($a));
        $rows = [];
        foreach ($paths as $path => $examples) {
            $rows[] = [$path, count($examples), implode(', ', array_slice($examples, 0, (int) $this->option('show')))];
        }
        if ($rows) {
            $this->table(['JSON path lệch', 'Số ca', 'Ví dụ'], $rows);
        }

        return $identical === count($stored);
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
                        $out[] = $child.(array_key_exists($key, $a) ? ' (chỉ bản lưu có)' : ' (chỉ bản mới có)');
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
