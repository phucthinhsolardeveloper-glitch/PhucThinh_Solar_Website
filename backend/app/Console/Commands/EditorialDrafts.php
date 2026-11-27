<?php

namespace App\Console\Commands;

use App\Domain\Content\EditorialBoard;
use App\Models\EditorialPlanItem;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Puts written planned articles (PTS-KT-NNN.md + .meta.json) into the CMS as hidden articles linked to their
 * calendar topic. Never switches anything on and never touches a topic that already has an article.
 */
final class EditorialDrafts extends Command
{
    protected $signature = 'pts:editorial-drafts {batch : Folder under resources/editorial, e.g. 2026-11-27} {--apply : Create them (otherwise dry run)}';

    protected $description = 'Import written planned articles as hidden drafts.';

    public function handle(EditorialBoard $board): int
    {
        $batch = (string) $this->argument('batch');
        $base = base_path('resources/editorial/'.$batch);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $batch) || ! is_dir($base)) {
            $this->error('Không thấy thư mục bài: '.$batch);

            return self::FAILURE;
        }
        $files = glob($base.'/*/PTS-KT-[0-9][0-9][0-9].md') ?: [];
        usort($files, fn (string $a, string $b) => strcmp(basename($a), basename($b)));
        $created = 0;
        foreach ($files as $file) {
            $key = basename($file, '.md');
            $plan = EditorialPlanItem::query()->where('plan_key', $key)->first();
            $meta = json_decode((string) @file_get_contents(substr($file, 0, -3).'.meta.json'), true);
            if (! $plan || ! is_array($meta) || ! is_string($meta['summary'] ?? null)) {
                $this->warn('BỎ QUA '.$key.': thiếu đề tài trong lịch hoặc tệp meta');

                continue;
            }
            if ($plan->post_id) {
                $this->line('BỎ QUA '.$key.': đề tài đã có bài');

                continue;
            }
            $this->line(($this->option('apply') ? 'TẠO bài ẩn ' : 'SẼ TẠO ').$key.': '.$plan->title);
            if (! $this->option('apply')) {
                continue;
            }
            $markdown = $this->links((string) file_get_contents($file), $key);
            $board->createDraft($plan, [
                'body' => Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
                'summary' => $meta['summary'], 'seo_description' => (string) ($meta['seo_description'] ?? $meta['summary']),
            ]);
            $created++;
        }
        $this->info('Đã tạo '.$created.' bài ẩn.');

        return self::SUCCESS;
    }

    /**
     * [[139]] links to post 139 when it is on the website; [[KT-023]] links to an earlier article of the plan.
     * A later or missing article is named in quotes without a link, so no reader meets a dead link.
     */
    private function links(string $markdown, string $current): string
    {
        return (string) preg_replace_callback('/\[\[(KT-\d{3}|\d+)\]\]/', function (array $m) use ($current): string {
            if (ctype_digit($m[1])) {
                $post = Post::query()->where('is_visible', true)->find((int) $m[1]);

                return $post ? '['.$post->title.'](/'.$post->slug.'/)' : '';
            }
            $plan = EditorialPlanItem::query()->with('post')->where('plan_key', 'PTS-'.$m[1])->first();
            if (! $plan) {
                return '';
            }
            /** @var Post|null $post */
            $post = $plan->post;

            return $post && strcmp('PTS-'.$m[1], $current) < 0 ? '['.$post->title.'](/'.$post->slug.'/)' : '“'.$plan->title.'”';
        }, $markdown);
    }
}
