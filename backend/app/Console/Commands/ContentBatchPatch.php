<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\SeoMeta;
use App\Support\RichText;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Applies explicitly human-approved article copy against immutable content hashes. */
final class ContentBatchPatch extends Command
{
    protected $signature = 'pts:content-batch-patch
        {--manifest= : Absolute manifest JSON path}
        {--ids=* : Post IDs for baseline export (repeatable or comma-separated)}
        {--export-baseline= : Absolute output path for a read-only baseline export}
        {--apply : Apply approved copy; preview is the default}
        {--allow-visible : Required to apply a currently visible article}
        {--allow-production : Explicitly allow production apply}
        {--approval-ref= : Human approval reference required for production apply}';

    protected $description = 'Preview or safely apply an approved batch of article copy updates.';

    private const BACKUP_DIR = 'content-batch-patch';

    public function handle(): int
    {
        if ($this->option('export-baseline') !== null && $this->option('export-baseline') !== '') {
            return $this->exportBaseline();
        }

        $apply = (bool) $this->option('apply');
        if ($apply && ! $this->applyEnvironmentAllowed()) {
            return self::FAILURE;
        }
        if ($apply && app()->environment('production')
            && (! $this->option('allow-production') || trim((string) $this->option('approval-ref')) === '')) {
            $this->error('Production apply yêu cầu cả --allow-production và --approval-ref=<mã duyệt của người thật>.');

            return self::FAILURE;
        }
        if (! $apply && ($this->option('allow-production') || $this->option('approval-ref'))) {
            $this->error('--allow-production và --approval-ref chỉ hợp lệ cùng --apply.');

            return self::FAILURE;
        }

        $manifestPath = (string) $this->option('manifest');
        if (! $this->absoluteFile($manifestPath)) {
            $this->error('--manifest phải là file JSON tại đường dẫn tuyệt đối.');

            return self::FAILURE;
        }
        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $this->error('Manifest không phải JSON hợp lệ.');

            return self::FAILURE;
        }
        if (! is_array($manifest) || ($manifest['schema_version'] ?? null) !== 1
            || ! is_array($manifest['items'] ?? null) || $manifest['items'] === []) {
            $this->error('Manifest cần schema_version=1 và items không rỗng.');

            return self::FAILURE;
        }

        $prepared = [];
        $blockers = [];
        $ids = [];
        foreach ($manifest['items'] as $index => $item) {
            $number = $index + 1;
            if (! is_array($item)) {
                $blockers[] = "item {$number}: cấu trúc không hợp lệ";

                continue;
            }
            $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (! $id || isset($ids[$id])) {
                $blockers[] = "item {$number}: id thiếu/không hợp lệ hoặc bị trùng";

                continue;
            }
            $ids[$id] = true;
            $post = Post::query()->with('seo')->find($id);
            $reason = $this->structureReason($item, $post);
            if ($reason !== null) {
                $blockers[] = "{$id}: {$reason}";

                continue;
            }
            if ($post->is_visible && $apply && ! $this->option('allow-visible')) {
                $blockers[] = "{$id}: bài đang hiển thị, apply cần --allow-visible";

                continue;
            }
            $qa = $item['qa'] ?? null;
            if (! is_array($qa) || ($qa['state'] ?? null) !== 'approved'
                || ($qa['sources_verified'] ?? null) !== true || ($qa['images_verified'] ?? null) !== true
                || ($qa['human_approved'] ?? null) !== true || ! is_string($qa['reviewer'] ?? null)
                || trim($qa['reviewer']) === '') {
                $blockers[] = "{$id}: cần QA approved, nguồn/ảnh đã xác minh và xác nhận của reviewer là người thật";

                continue;
            }
            $original = $item['original'] ?? null;
            if (! is_array($original) || ! $this->validHashes($original)) {
                $blockers[] = "{$id}: thiếu baseline SHA-256 hợp lệ";

                continue;
            }
            $proposed = $item['proposed'] ?? null;
            $target = $this->loadTarget($proposed);
            if (isset($target['error'])) {
                $blockers[] = "{$id}: {$target['error']}";

                continue;
            }
            $currentHashes = $this->contentHashes($post);
            $targetHashes = $this->targetHashes($target);
            $drift = $this->driftReason($original, $currentHashes, $targetHashes);
            if ($drift !== null) {
                $blockers[] = "{$id}: {$drift}";

                continue;
            }
            $noop = $this->targetMatches($currentHashes, $targetHashes);
            $prepared[] = [
                'id' => (int) $id,
                'post' => $post,
                'original' => $original,
                'target' => $target,
                'target_hashes' => $targetHashes,
                'noop' => $noop,
            ];
        }

        foreach ($prepared as $item) {
            $this->line(($item['noop'] ? 'NOOP ' : ($apply ? 'UPDATE ' : 'WOULD UPDATE ')).$item['id']);
        }
        foreach ($blockers as $blocker) {
            $this->warn('BLOCKED '.$blocker);
        }
        if ($blockers !== []) {
            $this->error('FAIL CLOSED: có blocker; không ghi bài nào.');
            $this->line('Run report: '.json_encode([
                'mode' => $apply ? 'apply' : 'dry-run',
                'items' => array_map(fn (array $item): array => ['id' => $item['id'], 'status' => $item['noop'] ? 'noop' : 'blocked_by_batch'], $prepared),
                'blockers' => $blockers,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return self::FAILURE;
        }
        $changes = array_values(array_filter($prepared, fn (array $item): bool => ! $item['noop']));
        if (! $apply || $changes === []) {
            $this->info(($apply ? 'APPLIED' : 'DRY-RUN').': '.count($changes).' change(s), '.count($prepared).' item(s) checked.');
            $this->line('Run report: '.json_encode([
                'mode' => $apply ? 'apply' : 'dry-run',
                'items' => array_map(fn (array $item): array => ['id' => $item['id'], 'status' => $item['noop'] ? 'noop' : ($apply ? 'updated' : 'would_update')], $prepared),
                'blockers' => [],
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        try {
            $backupPath = $this->writeBackup($changes);
            DB::transaction(function () use ($changes): void {
                foreach ($changes as $item) {
                    $post = Post::query()->whereKey($item['id'])->lockForUpdate()->firstOrFail();
                    $seo = SeoMeta::query()->where('owner_type', Post::class)->where('owner_id', $item['id'])->lockForUpdate()->first();
                    $reason = $this->structureReasonForLocked($post, $seo, $item['post']->slug);
                    if ($reason !== null) {
                        throw new RuntimeException($item['id'].': '.$reason);
                    }
                    if ($post->is_visible && ! $this->option('allow-visible')) {
                        throw new RuntimeException($item['id'].': bài chuyển sang visible; apply cần --allow-visible');
                    }
                    $current = $this->contentHashes($post, $seo);
                    $drift = $this->driftReason($item['original'], $current, $item['target_hashes']);
                    if ($drift !== null) {
                        throw new RuntimeException($item['id'].': '.$drift);
                    }
                    if ($this->targetMatches($current, $item['target_hashes'])) {
                        continue;
                    }
                    $post->body = $item['target']['body'];
                    $post->summary = $item['target']['summary'];
                    $post->save();
                    $seo->description = $item['target']['seo_description'];
                    if (array_key_exists('seo_title', $item['target'])) {
                        $seo->title = $item['target']['seo_title'];
                    }
                    $seo->save();
                }
            });
        } catch (Throwable $exception) {
            $this->error('FAIL CLOSED: transaction đã rollback; backup nếu đã tạo được giữ tại storage/app/private.');
            report($exception);

            return self::FAILURE;
        }
        $this->info('APPLIED: '.count($changes).' change(s); backup: '.$backupPath);
        $this->line('Run report: '.json_encode([
            'mode' => 'apply', 'backup' => $backupPath,
            'approval_ref' => app()->environment('production') ? trim((string) $this->option('approval-ref')) : null,
            'items' => array_map(fn (array $item): array => ['id' => $item['id'], 'status' => 'updated'], $changes),
            'blockers' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }

    private function applyEnvironmentAllowed(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }
        if (app()->environment('production') && $this->option('allow-production')
            && trim((string) $this->option('approval-ref')) !== '') {
            return true;
        }
        $this->error('Apply bị từ chối: chỉ local/testing, hoặc production với opt-in và approval-ref tường minh.');

        return false;
    }

    private function exportBaseline(): int
    {
        $path = (string) $this->option('export-baseline');
        if (! str_starts_with($path, '/') || file_exists($path) || ! is_dir(dirname($path)) || ! is_writable(dirname($path))) {
            $this->error('--export-baseline phải là đường dẫn tuyệt đối mới, trong thư mục tồn tại và ghi được.');

            return self::FAILURE;
        }
        $ids = $this->parseIds();
        if ($ids === null || $ids === []) {
            $this->error('Export baseline yêu cầu --ids với ID dương, duy nhất.');

            return self::FAILURE;
        }
        $records = [];
        foreach ($ids as $id) {
            $post = Post::query()->with('seo')->find($id);
            $reason = $this->structureReason(['id' => $id, 'kind' => 'article', 'slug' => $post?->slug], $post);
            if ($reason !== null) {
                $this->error("{$id}: {$reason}");

                return self::FAILURE;
            }
            $records[] = [
                'id' => $post->id,
                'kind' => $post->kind,
                'slug' => $post->slug,
                'visible' => (bool) $post->is_visible,
                'original' => $this->contentHashes($post),
            ];
        }
        $payload = json_encode(['schema_version' => 1, 'exported_at' => now()->toIso8601String(), 'items' => $records], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $payload, LOCK_EX) === false) {
            $this->error('Không ghi được baseline.');

            return self::FAILURE;
        }
        chmod($path, 0600);
        $this->info('Baseline raw SHA exported (không có body): '.$path);

        return self::SUCCESS;
    }

    private function parseIds(): ?array
    {
        $ids = [];
        foreach ((array) $this->option('ids') as $part) {
            foreach (explode(',', (string) $part) as $value) {
                $id = filter_var(trim($value), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if (! $id || in_array((int) $id, $ids, true)) {
                    return null;
                }
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    private function absoluteFile(string $path): bool
    {
        return str_starts_with($path, '/') && is_file($path) && is_readable($path);
    }

    private function structureReason(array $item, ?Post $post): ?string
    {
        if ($post === null) {
            return 'không tìm thấy bài';
        }
        if (($item['kind'] ?? null) !== 'article' || $post->kind !== 'article') {
            return 'kind phải là article';
        }
        if (! is_string($item['slug'] ?? null) || $item['slug'] !== $post->slug) {
            return 'slug không khớp';
        }
        if (! $post->seo || $post->seo->owner_type !== Post::class || (int) $post->seo->owner_id !== (int) $post->id) {
            return 'thiếu hoặc sai SEO morph row';
        }

        return null;
    }

    private function structureReasonForLocked(Post $post, ?SeoMeta $seo, string $slug): ?string
    {
        if ($post->kind !== 'article' || $post->slug !== $slug) {
            return 'kind/slug thay đổi trong lúc apply';
        }
        if (! $seo || $seo->owner_type !== Post::class || (int) $seo->owner_id !== (int) $post->id) {
            return 'thiếu hoặc sai SEO morph row trong lúc apply';
        }

        return null;
    }

    private function validHashes(array $original): bool
    {
        foreach (['body_sha256', 'summary_sha256', 'seo_title_sha256', 'seo_description_sha256'] as $key) {
            if (! is_string($original[$key] ?? null) || ! preg_match('/^[a-f0-9]{64}$/i', $original[$key])) {
                return false;
            }
        }

        return true;
    }

    private function loadTarget(mixed $proposed): array
    {
        if (! is_array($proposed) || ! is_string($proposed['markdown_file'] ?? null)
            || ! is_string($proposed['summary'] ?? null) || ! is_string($proposed['seo_description'] ?? null)) {
            return ['error' => 'proposed thiếu markdown_file, summary hoặc seo_description'];
        }
        foreach (['summary', 'seo_description'] as $field) {
            if (trim($proposed[$field]) === '') {
                return ['error' => "{$field} không được trống"];
            }
        }
        if (array_key_exists('seo_title', $proposed) && (! is_string($proposed['seo_title']) || trim($proposed['seo_title']) === '')) {
            return ['error' => 'seo_title nếu có phải là chuỗi không trống'];
        }
        $relative = $proposed['markdown_file'];
        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, "\\")
            || in_array('..', explode('/', $relative), true)) {
            return ['error' => 'markdown_file phải là đường dẫn tương đối an toàn'];
        }
        $root = realpath(base_path('resources/editorial'));
        $path = realpath(base_path('resources/editorial/'.$relative));
        if ($root === false || $path === false || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR) || ! is_file($path) || ! is_readable($path)) {
            return ['error' => 'markdown_file nằm ngoài resources/editorial hoặc không đọc được'];
        }
        $markdown = (string) file_get_contents($path);
        if (trim($markdown) === '' || preg_match('/\b(?:TODO|TBD|PLACEHOLDER|INSERT[_ ](?:LINK|SOURCE|TEXT)|example\.com)\b/i', $markdown)
            || preg_match('/\]\(\s*(?:#|\s*\)|[^)]*(?:placeholder|your[-_ ]?(?:url|link)|example\.com|TODO|TBD)[^)]*\))/i', $markdown)) {
            return ['error' => 'Markdown rỗng hoặc còn link/text placeholder'];
        }
        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $body = RichText::sanitize($html);
        $plainText = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($body === '' || $plainText === '') {
            return ['error' => 'Markdown không tạo nội dung HTML an toàn có chữ'];
        }
        $target = ['body' => $body, 'summary' => trim($proposed['summary']), 'seo_description' => trim($proposed['seo_description'])];
        if (array_key_exists('seo_title', $proposed)) {
            $target['seo_title'] = trim($proposed['seo_title']);
        }

        return $target;
    }

    private function contentHashes(Post $post, ?SeoMeta $seo = null): array
    {
        $seo ??= $post->seo;

        return [
            'body_sha256' => hash('sha256', (string) $post->body),
            'summary_sha256' => hash('sha256', (string) $post->summary),
            'seo_title_sha256' => hash('sha256', (string) ($seo?->title ?? '')),
            'seo_description_sha256' => hash('sha256', (string) ($seo?->description ?? '')),
        ];
    }

    private function targetHashes(array $target): array
    {
        $hashes = [
            'body_sha256' => hash('sha256', $target['body']),
            'summary_sha256' => hash('sha256', $target['summary']),
            'seo_description_sha256' => hash('sha256', $target['seo_description']),
        ];
        if (array_key_exists('seo_title', $target)) {
            $hashes['seo_title_sha256'] = hash('sha256', $target['seo_title']);
        }

        return $hashes;
    }

    private function driftReason(array $original, array $current, array $target): ?string
    {
        foreach (['body_sha256', 'summary_sha256', 'seo_title_sha256', 'seo_description_sha256'] as $field) {
            $allowed = [$original[$field]];
            if (isset($target[$field])) {
                $allowed[] = $target[$field];
            }
            if (! in_array($current[$field], $allowed, true)) {
                return 'baseline drift: '.$field;
            }
        }

        return null;
    }

    private function targetMatches(array $current, array $target): bool
    {
        foreach ($target as $field => $hash) {
            if (($current[$field] ?? null) !== $hash) {
                return false;
            }
        }

        return true;
    }

    private function writeBackup(array $changes): string
    {
        $directory = storage_path('app/private/'.self::BACKUP_DIR);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Không tạo được thư mục backup private.');
        }
        $records = [];
        foreach ($changes as $item) {
            $post = DB::table('posts')->where('id', $item['id'])->first();
            $seo = DB::table('seo_metas')->where('owner_type', Post::class)->where('owner_id', $item['id'])->first();
            if (! $post || ! $seo) {
                throw new RuntimeException('Không thể backup đủ raw post và SEO record.');
            }
            $records[] = ['post' => (array) $post, 'seo_meta' => (array) $seo];
        }
        $path = $directory.'/'.now()->format('Ymd-His-u').'-'.Str::lower(Str::random(10)).'.json';
        $payload = json_encode(['created_at' => now()->toIso8601String(), 'records' => $records], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $payload, LOCK_EX) === false || ! chmod($path, 0600)) {
            throw new RuntimeException('Không ghi/bảo vệ được backup raw trước transaction.');
        }

        return $path;
    }
}
