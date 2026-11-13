<?php

namespace App\Console\Commands;

use App\Domain\News\RelatedProductSuggestions;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Links the reviewed related products of the 15 written drafts (PTS-KT-001..015).
 *
 * Invariants: only hidden articles without any product selection (live or in a pending draft
 * version) are touched; a product is linked only when it is live and belongs to a topic the
 * article actually discusses; products are never created and posts are never published.
 * Running it again changes nothing.
 */
final class EditorialProductLinksCommand extends Command
{
    protected $signature = 'pts:editorial-product-links
        {--apply : Write the links (otherwise dry run)}
        {--ids= : Comma-separated plan keys to inspect/apply}
        {--report= : Also write the Markdown cross-check report to this path}
        {--backup= : Private backup directory used before writes}';

    protected $description = 'Preview/apply reviewed related products for editorial drafts PTS-KT-001..015 without overwriting manual choices.';

    public const MANIFEST = 'resources/editorial/2026-11-12/related-products.json';

    public function handle(RelatedProductSuggestions $suggestions): int
    {
        $apply = (bool) $this->option('apply');
        if ($apply && app()->environment('production')) {
            $this->error('Môi trường production: cần duyệt riêng và thêm --production-approved.');

            return self::FAILURE;
        }
        $entries = self::manifest();
        $selected = $this->option('ids');
        if (is_string($selected) && trim($selected) !== '') {
            $ids = array_values(array_filter(array_map('trim', explode(',', $selected))));
            $allowed = array_column($entries, 'plan_key');
            if (array_diff($ids, $allowed) !== []) {
                $this->error('Invalid plan key: '.implode(', ', array_diff($ids, $allowed)));

                return self::FAILURE;
            }
            $entries = array_values(array_filter($entries, fn (array $entry) => in_array($entry['plan_key'], $ids, true)));
        }
        $backupDir = (string) ($this->option('backup') ?: storage_path('app/private/editorial-product-links'));
        $planPosts = Schema::hasTable('editorial_plan_items')
            ? DB::table('editorial_plan_items')->whereIn('plan_key', array_column($entries, 'plan_key'))->pluck('post_id', 'plan_key')
            : collect();

        $rows = [];
        $linked = 0;
        foreach ($entries as $entry) {
            $postId = $planPosts->get($entry['plan_key']);
            $post = ($postId ? Post::query()->where('kind', 'article')->find($postId) : null)
                ?? Post::query()->where('kind', 'article')->where('slug', $entry['slug'])->first();
            $row = ['plan_key' => $entry['plan_key'], 'post' => $post?->id, 'title' => $post ? (string) $post->title : $entry['slug'],
                'decision' => $entry['decision'], 'reason' => $entry['reason'], 'products' => [], 'status' => ''];

            $resolved = $this->resolve($entry, $post, $suggestions);
            $row['products'] = $resolved;
            $valid = array_values(array_filter($resolved, fn (array $p) => $p['ok']));

            if (! $post) {
                $row['status'] = 'CHƯA CÓ BÀI — chạy pts:editorial-calendar trước';
            } elseif ($entry['decision'] === 'none') {
                $row['status'] = 'KHÔNG GẮN SẢN PHẨM';
            } elseif (($blocked = self::blocked($post)) !== null) {
                $row['status'] = $blocked;
            } elseif ($valid === []) {
                $row['status'] = 'KHÔNG CÓ SẢN PHẨM HỢP LỆ';
            } elseif (! $apply) {
                $row['status'] = 'SẼ GẮN '.count($valid).' sản phẩm';
            } else {
                $done = DB::transaction(function () use ($post, $valid, $backupDir): bool {
                    $locked = Post::query()->whereKey($post->id)->lockForUpdate()->first();
                    if (! $locked || self::blocked($locked) !== null) {
                        return false;
                    }
                    $current = DB::table('post_products')->where('post_id', $locked->id)
                        ->orderBy('sort_order')->orderBy('id')->get(['id', 'product_id', 'is_visible', 'sort_order'])->all();
                    if ($current !== []) {
                        return false;
                    }
                    if (! is_dir($backupDir) && ! mkdir($backupDir, 0700, true) && ! is_dir($backupDir)) {
                        throw new RuntimeException('Cannot create private backup directory');
                    }
                    file_put_contents($backupDir.'/post-'.$locked->id.'-'.now()->format('YmdHisv').'.json', json_encode([
                        'post_id' => $locked->id, 'rows' => $current, 'created_at' => now()->toIso8601String(),
                    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), LOCK_EX);
                    $attach = [];
                    foreach ($valid as $i => $product) {
                        $attach[$product['id']] = ['sort_order' => $i, 'is_visible' => true];
                    }
                    $locked->products()->attach($attach);

                    return true;
                });
                $row['status'] = $done ? 'ĐÃ GẮN '.count($valid).' sản phẩm' : 'BỎ QUA — dữ liệu vừa thay đổi';
                $linked += $done ? 1 : 0;
            }
            $rows[] = $row;
        }

        foreach ($rows as $row) {
            $this->line(sprintf('%s  #%s  %s', $row['plan_key'], $row['post'] ?? '-', $row['status']));
            foreach ($row['products'] as $product) {
                $this->line(sprintf('    %s %s  %s%s', $product['ok'] ? '+' : 'x', $product['id'] ?? '-', $product['slug'],
                    $product['ok'] ? '' : '  ('.$product['problem'].')'));
            }
        }
        $this->info(($apply ? 'Đã gắn sản phẩm cho ' : 'Chạy thử, chưa ghi gì. Số bài sẽ được xét: ').($apply ? $linked : count($rows)).' bài.');

        if (is_string($report = $this->option('report')) && $report !== '') {
            file_put_contents($report, self::markdown($rows, $apply));
            $this->line('Báo cáo: '.$report);
        }

        return self::SUCCESS;
    }

    /** Why a post must not receive imported links, or null when it may. */
    public static function blocked(Post $post): ?string
    {
        if ($post->is_visible) {
            return 'BỎ QUA — bài đang hiển thị, chỉ áp dụng cho bản nháp ẩn';
        }
        if (DB::table('post_products')->where('post_id', $post->id)->exists()) {
            return 'GIỮ NGUYÊN — bài đã có sản phẩm liên quan (chọn tay hoặc lần nhập trước)';
        }
        foreach (DB::table('post_revisions')->where('post_id', $post->id)->where('state', 'draft')->pluck('payload') as $payload) {
            $data = json_decode((string) $payload, true);
            if (is_array($data) && ! empty($data['products'])) {
                return 'GIỮ NGUYÊN — bản nháp đang chờ duyệt đã có sản phẩm';
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return list<array{id: ?int, expected_id: ?int, slug: string, name: string, topic: string, reason: string, ok: bool, problem: string}>
     */
    private function resolve(array $entry, ?Post $post, RelatedProductSuggestions $suggestions): array
    {
        $topics = $post ? $suggestions->topics([
            'title' => $post->title, 'summary' => $post->summary, 'keywords' => $post->seo?->keywords,
            'body' => $post->body,
            'category' => $post->category_id ? (string) DB::table('post_categories')->where('id', $post->category_id)->value('slug') : '',
        ]) : [];
        $out = [];
        foreach ($entry['products'] as $item) {
            $id = DB::table('products')->where('slug', $item['slug'])->value('id');
            $live = $id ? $suggestions->eligible([(int) $id]) : [];
            $product = $live[(int) $id] ?? null;
            $problem = match (true) {
                ! $id => 'không tìm thấy slug',
                $product === null => 'sản phẩm ẩn, thiếu ảnh hoặc slug không hợp lệ',
                ! in_array($product['category_slug'], $suggestions->topicCategorySlugs($item['topic']), true) => 'danh mục không thuộc chủ đề '.$item['topic'],
                $post !== null && ! isset($topics[$item['topic']]) => 'nội dung bài hiện tại không bàn chủ đề '.$item['topic'],
                default => '',
            };
            $out[] = ['id' => $id ? (int) $id : null, 'expected_id' => $item['expected_id'] ?? null, 'slug' => $item['slug'],
                'name' => $product['name'] ?? (string) DB::table('products')->where('slug', $item['slug'])->value('name'),
                'topic' => $item['topic'], 'reason' => $item['reason'], 'ok' => $problem === '', 'problem' => $problem];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function manifest(): array
    {
        $data = json_decode((string) file_get_contents(base_path(self::MANIFEST)), true, flags: JSON_THROW_ON_ERROR);
        $entries = $data['articles'] ?? null;
        if (! is_array($entries)) {
            throw new RuntimeException('Invalid related-products manifest');
        }
        $keys = array_column($entries, 'plan_key');
        $expected = array_map(fn (int $i) => sprintf('PTS-KT-%03d', $i), range(1, 21));
        if ($keys !== $expected) {
            throw new RuntimeException('Manifest must list PTS-KT-001..021 once, in order');
        }
        foreach ($entries as $entry) {
            $products = $entry['products'] ?? null;
            $slugs = is_array($products) ? array_column($products, 'slug') : [];
            if (! in_array($entry['decision'] ?? '', ['link', 'none'], true) || ! is_array($products)
                || ! is_string($entry['reason'] ?? null) || trim($entry['reason']) === ''
                || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) ($entry['slug'] ?? ''))
                || ($entry['decision'] === 'none' && $products !== [])
                || ($entry['decision'] === 'link' && ($products === [] || count($products) > RelatedProductSuggestions::MAX))
                || count(array_unique($slugs)) !== count($slugs)) {
                throw new RuntimeException('Invalid manifest entry '.($entry['plan_key'] ?? '?'));
            }
            foreach ($products as $item) {
                if (! isset(RelatedProductSuggestions::TOPICS[$item['topic'] ?? '']) || trim((string) ($item['reason'] ?? '')) === ''
                    || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) ($item['slug'] ?? ''))) {
                    throw new RuntimeException('Invalid product in manifest entry '.$entry['plan_key']);
                }
            }
        }

        return $entries;
    }

    /** @param  list<array<string, mixed>>  $rows */
    private static function markdown(array $rows, bool $apply): string
    {
        $env = app()->environment();
        $lines = ['# Đối chiếu sản phẩm liên quan PTS-KT-001..015', '',
            '- Nguồn: `backend/'.self::MANIFEST.'`',
            '- Lệnh: `php artisan pts:editorial-product-links'.($apply ? ' --apply' : '').'` (môi trường '.$env.', DB `'.DB::connection()->getDatabaseName().'`, '.now()->format('d/m/Y H:i').' UTC)',
            '- Điều kiện gắn: bài ẩn, chưa có sản phẩm (kể cả trong bản nháp chờ duyệt); sản phẩm hiển thị, danh mục hiển thị, có ảnh và slug hợp lệ; chủ đề phải được phát hiện trong nội dung bài.', ''];
        foreach ($rows as $row) {
            $lines[] = '## '.$row['plan_key'].' — '.$row['title'];
            $lines[] = '';
            $lines[] = '- Bài #'.($row['post'] ?? '-').' · Quyết định: '.($row['decision'] === 'link' ? 'gắn sản phẩm' : 'không gắn').' · Trạng thái: '.$row['status'];
            $lines[] = '- Lý do: '.$row['reason'];
            if ($row['products'] !== []) {
                $lines[] = '';
                $lines[] = '| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |';
                $lines[] = '|---|---|---|---|---|';
                foreach ($row['products'] as $p) {
                    $check = $p['ok'] ? 'hợp lệ' : $p['problem'];
                    if ($p['ok'] && $p['expected_id'] !== null && $p['expected_id'] !== $p['id']) {
                        $check .= ' (ID khác dự kiến '.$p['expected_id'].')';
                    }
                    $lines[] = '| '.($p['id'] ?? '-').' | ['.str_replace('|', '/', $p['name']).'](/san-pham/'.$p['slug'].') | '
                        .RelatedProductSuggestions::TOPICS[$p['topic']]['label'].' | '.str_replace('|', '/', $p['reason']).' | '.$check.' |';
                }
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
