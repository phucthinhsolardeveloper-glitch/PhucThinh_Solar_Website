<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\SeoMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Apply only explicitly staged, QA-gated legacy public/solution copy locally. */
final class EditorialContentReworkApply extends Command
{
    protected $signature = 'pts:content-rework-apply
        {--baseline= : Absolute baseline JSON path}
        {--proposal=* : Absolute approved proposal JSON (repeatable)}
        {--ids=* : Explicit post IDs to consider (repeatable or comma-separated)}
        {--apply : Write staged content; dry-run is the default}
        {--allow-visible : Required when any target is currently visible}';

    protected $description = 'Dry-run or safely apply the 2026-11-12 editorial rework to legacy public and solution posts.';

    private const BACKUP_DIR = 'editorial-content-rework';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Chỉ được chạy trong local/testing; production luôn bị từ chối.');

            return self::FAILURE;
        }
        $baselinePath = (string) $this->option('baseline');
        if ($baselinePath === '' || ! str_starts_with($baselinePath, '/') || ! is_file($baselinePath)) {
            $this->error('--baseline phải là đường dẫn JSON tuyệt đối đang tồn tại.');

            return self::FAILURE;
        }
        try {
            $baseline = json_decode((string) file_get_contents($baselinePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->error('Baseline không phải JSON hợp lệ.');

            return self::FAILURE;
        }
        $records = $baseline['records'] ?? null;
        if (! is_array($records) || $records === []) {
            $this->error('Baseline thiếu records.');

            return self::FAILURE;
        }
        $targets = $this->loadTargets();
        $selected = $this->selectedIds();
        $hasSelection = $selected !== null;
        $changes = [];
        $errors = [];
        foreach ($records as $expected) {
            if (! in_array($expected['post_id'] ?? null, [100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 61, 132, 133, 134, 135], true)) {
                continue;
            }
            $id = (int) $expected['post_id'];
            if ($hasSelection && ! in_array($id, $selected, true)) {
                $this->line("SKIP {$id}: not selected");

                continue;
            }
            if ($id === 133) {
                $this->warn('SKIP 133: nav_title changed after baseline; rebaseline required before refresh.');

                continue;
            }
            $post = Post::query()->with('seo')->find($id);
            $reason = $this->structuralReason($expected, $post);
            if ($reason !== null) {
                $errors[] = "{$id}: {$reason}";

                continue;
            }
            if ($post->is_visible && ! $this->option('allow-visible')) {
                $errors[] = "{$id}: visible post requires --allow-visible";

                continue;
            }
            if (! isset($targets[$id]) || ! isset($targets[$id]['body'])) {
                $errors[] = "{$id}: staged Markdown/proposal missing";

                continue;
            }
            $target = array_merge(['nav_title' => (string) $post->nav_title], $targets[$id]);
            if (! $this->approvedStatus($target['status'] ?? null)) {
                $errors[] = "{$id}: explicit approved status required (hold/merge_candidate refused)";

                continue;
            }
            if (($target['sources_verified'] ?? false) !== true || ($target['images_verified'] ?? false) !== true) {
                $errors[] = "{$id}: verified sources and images are required";

                continue;
            }
            if (! isset($target['summary']) || trim((string) $target['summary']) === '') {
                $errors[] = "{$id}: approved summary missing";

                continue;
            }
            $target['seo_description'] ??= $target['summary'];
            if ($this->baselineReason($expected, $post) !== null) {
                $errors[] = "{$id}: baseline drift";

                continue;
            }
            $current = ['body' => hash('sha256', (string) $post->body), 'summary' => hash('sha256', (string) $post->summary), 'nav_title' => hash('sha256', (string) $post->nav_title)];
            $next = ['body' => hash('sha256', $target['body']), 'summary' => hash('sha256', $target['summary']), 'nav_title' => hash('sha256', $target['nav_title'])];
            if ($current === $next) {
                $this->line("NOOP {$id}: target already applied");

                continue;
            }
            $changes[$id] = compact('target');
            $this->line(($this->option('apply') ? 'UPDATE ' : 'WOULD UPDATE ').$id);
        }
        if ($errors !== [] && ($this->option('apply') || $hasSelection)) {
            foreach ($errors as $error) {
                $this->warn($error);
            }
            $this->error('FAIL CLOSED: không ghi đè bất kỳ bản ghi nào.');

            return self::FAILURE;
        }
        foreach ($errors as $error) {
            $this->warn($error);
        }
        if (! $this->option('apply') || $changes === []) {
            $this->info('DRY-RUN: '.count($changes).' change(s); '.count($errors).' blocked.');

            return self::SUCCESS;
        }
        $backup = [];
        foreach (array_keys($changes) as $id) {
            $post = Post::query()->with('seo')->findOrFail($id);
            $backup[$id] = ['post' => $post->getAttributes(), 'seo' => $post->seo?->getAttributes()];
        }
        $name = self::BACKUP_DIR.'/'.now()->format('YmdHis-u').'-'.bin2hex(random_bytes(4)).'.json';
        $contents = json_encode(['created_at' => now()->toIso8601String(), 'posts' => $backup], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (! Storage::disk('local')->put($name, $contents)) {
            $this->error('Không ghi được private backup; dừng trước mutation.');

            return self::FAILURE;
        }
        try {
            DB::transaction(function () use ($changes, $records): void {
                foreach ($changes as $id => $change) {
                    $post = Post::query()->with('seo')->whereKey($id)->lockForUpdate()->firstOrFail();
                    $expected = collect($records)->firstWhere('post_id', (int) $id);
                    if (! is_array($expected) || $this->structuralReason($expected, $post) !== null || $this->baselineReason($expected, $post) !== null) {
                        throw new RuntimeException("{$id}: drift inside transaction");
                    }
                    $post->update(['body' => $change['target']['body'], 'summary' => $change['target']['summary'], 'nav_title' => $change['target']['nav_title']]);
                    // seo_description is always staged (defaults to the approved summary), so SEO is always written.
                    $seo = ['description' => $change['target']['seo_description']];
                    if (array_key_exists('seo_title', $change['target'])) {
                        $seo['title'] = $change['target']['seo_title'];
                    }
                    $post->seo()->updateOrCreate([], $seo);
                }
            });
        } catch (Throwable $e) {
            $this->error('FAIL CLOSED: transaction rolled back. Backup retained.');

            return self::FAILURE;
        }
        $this->info('APPLIED: '.count($changes).' change(s); backup='.$name);

        return self::SUCCESS;
    }

    private function loadTargets(): array
    {
        $targets = [];
        $sourceErrors = [];
        $root = base_path('resources/editorial/2026-11-12');
        foreach ([100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111] as $id) {
            $this->readTarget($targets, $id, "{$root}/legacy-public/ARTICLE-{$id}.md", $sourceErrors);
        }
        foreach ([61, 132, 133, 134, 135] as $id) {
            $this->readTarget($targets, $id, "{$root}/solutions/SOLUTION-{$id}.md", $sourceErrors);
        }
        foreach ((array) $this->option('proposal') as $path) {
            if (! str_starts_with((string) $path, '/') || ! is_file($path)) {
                continue;
            }
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            foreach ($this->proposalEntries($data) as $entry) {
                if (is_array($entry)) {
                    $id = $this->proposalId($entry['post_id'] ?? $entry['id'] ?? null);
                    if ($id === null) {
                        continue;
                    }
                    $targets[$id]['summary'] = trim((string) $entry['summary']);
                    if (! isset($targets[$id]['status'])) {
                        $targets[$id]['status'] = $entry['status'] ?? $entry['qa_status'] ?? null;
                    }
                    $sources = $entry['sources'] ?? $entry['source_urls'] ?? [];
                    $targets[$id]['sources_verified'] = ($entry['sources_verified'] ?? false) === true && is_array($sources) && $sources !== [];
                    $targets[$id]['images_verified'] = ($entry['images_verified'] ?? false) === true;
                    if (isset($entry['nav_title'])) {
                        $targets[$id]['nav_title'] = trim((string) $entry['nav_title']);
                    }
                    if (isset($entry['seo_title'])) {
                        $targets[$id]['seo_title'] = trim((string) $entry['seo_title']);
                    }
                    if (isset($entry['seo_description'])) {
                        $targets[$id]['seo_description'] = trim((string) $entry['seo_description']);
                    }
                }
            }
        }
        foreach ($sourceErrors as $error) {
            $this->warn($error);
        }

        return $targets;
    }

    /** @return list<int>|null */
    private function selectedIds(): ?array
    {
        $values = (array) $this->option('ids');
        if ($values === []) {
            return null;
        }
        $ids = [];
        foreach ($values as $value) {
            foreach (explode(',', (string) $value) as $part) {
                if (ctype_digit(trim($part))) {
                    $ids[] = (int) trim($part);
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private function readTarget(array &$targets, int $id, string $path, array &$errors): void
    {
        if (! is_file($path)) {
            return;
        }
        $markdown = (string) file_get_contents($path);
        [$markdown, $frontmatter] = $this->stripFrontmatter($markdown);
        $markdown = preg_replace('/^\xEF\xBB\xBF?\s*#\s+[^\r\n]+\r?\n/u', '', $markdown, 1) ?? $markdown;
        if (preg_match('/^#{2,6}\s+(?:Ghi chú biên tập|Nguồn tham khảo cần gắn khi xuất bản)|Nguồn tham khảo cần gắn khi xuất bản|Không xuất bản độc lập/imu', $markdown)) {
            $errors[] = "{$id}: internal editorial metadata remains in Markdown";

            return;
        }
        $markdown = $this->safeMarkdown($markdown);
        $targets[$id] = [
            'body' => Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
            'status' => $frontmatter['status'] ?? null,
            'sources_verified' => filter_var($frontmatter['sources_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'images_verified' => filter_var($frontmatter['images_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /** Keep only links/images as Markdown, then render with raw HTML disabled. */
    private function safeMarkdown(string $markdown): string
    {
        $markdown = preg_replace('/<script\b[^>]*>.*?<\/script\s*>|<style\b[^>]*>.*?<\/style\s*>/is', '', $markdown) ?? $markdown;
        $markdown = preg_replace_callback('/<img\b([^>]*)>/i', function (array $match): string {
            preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $match[1], $src);
            preg_match('/\balt\s*=\s*["\']([^"\']*)["\']/i', $match[1], $alt);

            return isset($src[1]) ? '!['.($alt[1] ?? '').']('.$src[1].')' : '';
        }, $markdown) ?? $markdown;
        $markdown = preg_replace_callback('/<a\b([^>]*)>(.*?)<\/a\s*>/is', function (array $match): string {
            preg_match('/\bhref\s*=\s*["\']([^"\']+)["\']/i', $match[1], $href);

            return isset($href[1]) ? '['.strip_tags($match[2]).']('.$href[1].')' : strip_tags($match[2]);
        }, $markdown) ?? $markdown;

        return $markdown;
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function stripFrontmatter(string $markdown): array
    {
        $markdown = preg_replace('/^\xEF\xBB\xBF/', '', $markdown) ?? $markdown;
        if (! str_starts_with($markdown, "---\n") && ! str_starts_with($markdown, "---\r\n")) {
            return [$markdown, []];
        }
        if (! preg_match('/\A---\r?\n(.*?)\r?\n---\r?\n/s', $markdown, $match)) {
            return [$markdown, []];
        }
        $meta = [];
        foreach (preg_split('/\r?\n/', $match[1]) ?: [] as $line) {
            if (preg_match('/^([A-Za-z0-9_-]+):\s*["\']?([^"\']*)["\']?\s*$/', $line, $m)) {
                $meta[strtolower($m[1])] = trim($m[2]);
            }
        }

        return [substr($markdown, strlen($match[0])), $meta];
    }

    private function approvedStatus(mixed $status): bool
    {
        return in_array(strtolower(trim((string) $status)), ['approved', 'qa_approved', 'solid'], true);
    }

    private function proposalId(mixed $value): ?int
    {
        if (is_int($value) || ctype_digit((string) $value)) {
            return (int) $value;
        }

        return is_string($value) && preg_match('/(?:ARTICLE|SOLUTION)-?(\d+)/i', $value, $m) ? (int) $m[1] : null;
    }

    /** @return array<mixed> Raw JSON entries; callers must validate each item. */
    private function proposalEntries(array $data): array
    {
        foreach (['posts', 'articles', 'solutions', 'proposals'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return array_values($data[$key]);
            }
        }

        return array_is_list($data) ? $data : [];
    }

    private function structuralReason(array $expected, ?Post $post): ?string
    {
        if (! $post || (int) ($expected['post_id'] ?? 0) !== (int) $post->id || ($expected['kind'] ?? null) !== $post->kind || ($expected['slug'] ?? null) !== $post->slug || (bool) ($expected['is_visible'] ?? null) !== (bool) $post->is_visible) {
            return 'id/kind/slug/visibility mismatch';
        }

        return null;
    }

    private function baselineReason(array $expected, Post $post): ?string
    {
        foreach (['body' => $post->body, 'summary' => $post->summary, 'nav_title' => $post->nav_title] as $field => $value) {
            if (! hash_equals((string) ($expected[$field.'_sha256'] ?? ''), hash('sha256', (string) $value))) {
                return "{$field} drift";
            }
        }
        /** @var SeoMeta|null $seoMeta The relation is absent when a post has no SEO row. */
        $seoMeta = $post->seo;
        foreach (['seo_title' => data_get($seoMeta, 'title', ''), 'seo_description' => data_get($seoMeta, 'description', '')] as $field => $value) {
            if (array_key_exists($field.'_sha256', $expected) && ! hash_equals((string) $expected[$field.'_sha256'], hash('sha256', (string) $value))) {
                return "{$field} drift";
            }
        }

        return null;
    }
}
