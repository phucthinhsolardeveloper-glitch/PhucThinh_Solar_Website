<?php

namespace App\Console\Commands;

use App\Models\EditorialPlanItem;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Safely refresh reviewed editorial copy without touching editorial metadata. */
final class EditorialCopyRefresh extends Command
{
    protected $signature = 'pts:editorial-copy-refresh
        {--apply : Apply changes (only allowed in local/testing)}
        {--baseline= : Absolute path to the immutable DB copy baseline JSON}';

    protected $description = 'Preview or safely refresh hidden editorial article copy with drift checks.';

    private const ROOT = 'resources/editorial/2026-11-12';

    private const BACKUP_DIR = 'editorial-copy-refresh';

    public function handle(): int
    {
        if ($this->option('apply') && ! app()->environment(['local', 'testing'])) {
            $this->error('Apply chỉ được phép trong môi trường local hoặc testing.');

            return self::FAILURE;
        }

        $baselinePath = (string) $this->option('baseline');
        if ($baselinePath === '' || ! str_starts_with($baselinePath, '/') || ! is_file($baselinePath)) {
            $this->error('--baseline phải là đường dẫn JSON tuyệt đối đang tồn tại.');

            return self::FAILURE;
        }
        $baseline = json_decode((string) file_get_contents($baselinePath), true, flags: JSON_THROW_ON_ERROR);
        $entries = $baseline['posts'] ?? null;
        if (! is_array($entries) || $entries === []) {
            $this->error('Baseline thiếu trường posts.');

            return self::FAILURE;
        }

        $base = base_path(self::ROOT);
        $targets = $this->loadTargets($base);
        $changes = [];
        $blocked = 0;
        foreach ($entries as $key => $expected) {
            if (! preg_match('/^PTS-KT-(?:00[4-9]|01[0-9]|02[01])$/', (string) $key)) {
                continue;
            }
            if (! isset($targets[$key])) {
                $this->error("Thiếu source/proposal cho {$key}.");

                return self::FAILURE;
            }
            $plan = EditorialPlanItem::query()->where('plan_key', $key)->first();
            $post = $plan?->post;
            $reason = $this->structuralReason($expected, $plan, $post);
            if ($reason !== null) {
                $this->warn("SKIP {$key}: {$reason}");
                $blocked++;

                continue;
            }
            $target = $targets[$key];
            $current = [
                'body' => hash('sha256', (string) $post->body),
                'summary' => hash('sha256', (string) $post->summary),
                'seo_description' => hash('sha256', (string) ($post->seo?->description ?? '')),
            ];
            $next = [
                'body' => hash('sha256', $target['body']),
                'summary' => hash('sha256', $target['summary']),
                'seo_description' => hash('sha256', $target['summary']),
            ];
            if ($current === $next) {
                $this->line("NOOP {$key}: current copy already matches target");

                continue;
            }
            $reason = $this->baselineReason($expected, $post);
            if ($reason !== null) {
                $this->warn("SKIP {$key}: {$reason}");
                $blocked++;

                continue;
            }
            $changes[$key] = compact('post', 'target', 'current', 'next');
            $this->line(($this->option('apply') ? 'UPDATE ' : 'WOULD UPDATE ').$key);
        }

        if ($this->option('apply') && $blocked > 0) {
            $this->error("FAIL CLOSED: {$blocked} article(s) failed preflight; no writes performed.");

            return self::FAILURE;
        }
        if (! $this->option('apply') || $changes === []) {
            $this->info(($this->option('apply') ? 'APPLIED' : 'DRY-RUN').': '.count($changes).' change(s)');

            return self::SUCCESS;
        }

        $backup = [];
        foreach ($changes as $key => $change) {
            $post = $change['post'];
            $backup[$key] = [
                'id' => $post->id,
                'body' => $post->body,
                'summary' => $post->summary,
                'seo_description' => $post->seo?->description,
            ];
        }
        $backupName = now()->format('YmdHis-u').'-'.bin2hex(random_bytes(4)).'.json';
        $backupRelativePath = self::BACKUP_DIR.'/'.$backupName;
        $backupContents = json_encode([
            'created_at' => now()->toIso8601String(), 'posts' => $backup,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (! Storage::disk('local')->put($backupRelativePath, $backupContents)) {
            $this->error('Không thể ghi backup private; dừng trước khi cập nhật DB.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($changes, $entries): void {
                foreach ($changes as $key => $change) {
                    $post = Post::query()->whereKey($change['post']->id)->lockForUpdate()->firstOrFail();
                    $plan = EditorialPlanItem::query()->where('plan_key', $key)->lockForUpdate()->firstOrFail();
                    $expected = $entries[$key];
                    $reason = $this->structuralReason($expected, $plan, $post)
                        ?? $this->baselineReason($expected, $post);
                    if ($reason !== null) {
                        throw new RuntimeException("{$key}: {$reason}");
                    }
                    $post->update(['body' => $change['target']['body'], 'summary' => $change['target']['summary']]);
                    $post->seo()->updateOrCreate([], ['description' => $change['target']['summary']]);
                }
            });
        } catch (Throwable $exception) {
            $this->error('FAIL CLOSED: preflight recheck failed; transaction rolled back.');

            return self::FAILURE;
        }
        $this->info('APPLIED: '.count($changes).' change(s); backup stored outside Git.');

        return self::SUCCESS;
    }

    /** @return array<string, array{body:string, summary:string}> */
    private function loadTargets(string $base): array
    {
        $proposals = [];
        foreach (glob($base.'/reviews/review-proposals-*.json') ?: [] as $path) {
            foreach (json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) as $entry) {
                $proposals[$entry['id']] = $entry;
            }
        }
        // The approved source-controlled metadata wins over older review proposals.
        // Review snapshots are retained for traceability, not as active CMS copy.
        $written = json_decode((string) file_get_contents($base.'/written-articles.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($written['articles'] ?? [] as $entry) {
            $proposals[$entry['id']] = $entry;
        }
        $targets = [];
        foreach ($proposals as $id => $proposal) {
            if (! preg_match('/^PTS-KT-(?:00[4-9]|01[0-9]|02[01])$/', (string) $id)) {
                continue;
            }
            $path = $base.'/articles/'.$id.'.md';
            if (! is_file($path) || ! is_string($proposal['summary'] ?? null)) {
                continue;
            }
            $markdown = (string) file_get_contents($path);
            $markdown = preg_replace('/^# [^\r\n]+\r?\n/u', '', $markdown) ?? $markdown;
            $targets[$id] = [
                'body' => Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
                'summary' => trim($proposal['summary']),
            ];
        }

        return $targets;
    }

    private function structuralReason(array $expected, ?EditorialPlanItem $plan, ?Post $post): ?string
    {
        if ($plan === null || $post === null) {
            return 'missing linked EditorialPlanItem/Post';
        }
        if ((int) ($expected['id'] ?? 0) !== (int) $post->id || (int) $plan->post_id !== (int) $post->id) {
            return 'post id mismatch';
        }
        if (($expected['slug'] ?? null) !== $post->slug) {
            return 'slug mismatch';
        }
        if (($expected['kind'] ?? null) !== 'article' || $post->kind !== 'article') {
            return 'kind mismatch';
        }
        if (($expected['visible'] ?? null) !== false || $post->is_visible) {
            return 'post is visible';
        }

        return null;
    }

    private function baselineReason(array $expected, Post $post): ?string
    {
        $hashes = ['body' => $post->body, 'summary' => $post->summary, 'seo_description' => $post->seo?->description ?? ''];
        foreach ($hashes as $field => $value) {
            if (! hash_equals((string) ($expected[$field.'_sha256'] ?? ''), hash('sha256', (string) $value))) {
                return "baseline drift: {$field}";
            }
        }

        return null;
    }
}
