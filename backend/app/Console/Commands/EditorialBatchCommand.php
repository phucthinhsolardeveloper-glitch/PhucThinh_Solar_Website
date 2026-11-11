<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Reproducible editorial release; draft articles are not published by this import.
 * Existing editorial edits are never overwritten. Legacy cover swaps require the exact old filename.
 */
final class EditorialBatchCommand extends Command
{
    protected $signature = 'pts:editorial-batch {--apply : Apply changes (without this: preview only)} {--skip-covers : Do not replace legacy article covers} {--refresh-unchanged-drafts : Update previously imported hidden drafts only if their original body SHA-256 still matches}';

    protected $description = 'Preview/apply the bundled 11/11/2026 articles and licensed cover photos, without overwriting edited posts.';

    public function handle(): int
    {
        $dir = base_path('resources/editorial/2026-11-11');
        $manifest = json_decode((string) file_get_contents($dir.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $apply = (bool) $this->option('apply');
        $new = 0;
        $exists = 0;
        $replaced = 0;
        $skipped = 0;

        $files = [];
        foreach (array_merge($manifest['articles'], $manifest['cover_replacements']) as $entry) {
            foreach (array_merge([$entry['image']], $entry['inline_images'] ?? []) as $image) {
                if (! is_string($image) || ! preg_match('/^pts-[a-z0-9-]+\.webp$/', $image)
                    || ! is_file($source = rtrim((string) config('site.assets_path'), '/').'/images/editorial/'.$image)) {
                    throw new RuntimeException('Missing/invalid packaged editorial image: '.(string) $image);
                }
                $files[$image] = $source;
            }
        }
        foreach ($manifest['articles'] as $entry) {
            if (! preg_match('/^[a-z0-9-]+$/', $entry['slug']) || ! is_file($dir.'/articles/'.$entry['markdown'])) {
                throw new RuntimeException('Missing article markdown: '.$entry['slug']);
            }
            if (! DB::table('post_categories')->where('kind', 'article')->where('slug', $entry['category'])->exists()) {
                throw new RuntimeException('Unknown article category: '.$entry['category']);
            }
        }

        // Validate every media source first. No partial DB mutations if a media collision exists.
        $disk = Storage::disk('upload_news');
        foreach ($files as $filename => $source) {
            if ($disk->exists($filename) && hash_file('sha256', $disk->path($filename)) !== hash_file('sha256', $source)) {
                throw new RuntimeException('Existing media filename differs from package; do not overwrite: '.$filename);
            }
        }

        if ($apply) {
            foreach ($files as $filename => $source) {
                if (! $disk->exists($filename)) {
                    // The local legacy upload mount is read-only. Laravel serves the packaged
                    // file as a fallback; production copies it to the shared upload volume.
                    if (app()->environment('local')) {
                        continue;
                    }
                    if (! $disk->put($filename, file_get_contents($source))) {
                        throw new RuntimeException('Could not import media to upload/news: '.$filename);
                    }
                }
            }
        }

        foreach ($manifest['articles'] as $entry) {
            $found = DB::table('posts')->where('kind', 'article')->where('slug', $entry['slug'])
                ->first(['id', 'body', 'is_visible', 'published_at']);
            if ($found) {
                $canRefresh = ! (bool) $found->is_visible && $found->published_at === null
                    && isset($entry['previous_html_sha256'])
                    && hash_equals($entry['previous_html_sha256'], hash('sha256', (string) $found->body));
                if ($canRefresh && $this->option('refresh-unchanged-drafts')) {
                    $this->line(($apply ? 'REFRESH unchanged draft' : 'WOULD REFRESH unchanged draft').': '.$entry['slug']);
                    if ($apply) {
                        $freshHtml = Str::markdown(file_get_contents($dir.'/articles/'.$entry['markdown']),
                            ['html_input' => 'strip', 'allow_unsafe_links' => false]);
                        DB::table('posts')->where('id', $found->id)->where('is_visible', false)
                            ->whereNull('published_at')->where('body', $found->body)
                            ->update(['body' => $freshHtml, 'updated_at' => now()]);
                    }
                } else {
                    $this->line('SKIP existing/edited content: '.$entry['slug']);
                }
                $exists++;

                continue;
            }
            $this->line(($apply ? 'IMPORT draft' : 'WOULD IMPORT draft').': '.$entry['slug']);
            $new++;
            if (! $apply) {
                continue;
            }
            $markdown = file_get_contents($dir.'/articles/'.$entry['markdown']);
            $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
            if (mb_strlen(trim(strip_tags($html))) < 900) {
                throw new RuntimeException('Article is too short: '.$entry['slug']);
            }

            DB::transaction(function () use ($entry, $html): void {
                // Recheck inside the transaction; source manifests must never replace CMS-authored content.
                if (Post::query()->where('kind', 'article')->where('slug', $entry['slug'])->exists()) {
                    return;
                }
                $categoryId = DB::table('post_categories')->where('kind', 'article')->where('slug', $entry['category'])->value('id');
                $article = Post::query()->create([
                    'kind' => 'article', 'category_id' => $categoryId, 'title' => $entry['title'],
                    'slug' => $entry['slug'], 'summary' => $entry['summary'], 'body' => $html,
                    'image' => $entry['image'], 'is_visible' => false, 'is_featured' => false,
                    'in_hub' => false, 'sort_order' => 0, 'published_at' => null,
                ]);
                $article->seo()->create([
                    'title' => $entry['meta_title'], 'description' => $entry['meta_description'],
                    'keywords' => $entry['keywords'],
                ]);
            });
        }

        if (! $this->option('skip-covers')) {
            foreach ($manifest['cover_replacements'] as $entry) {
                $post = DB::table('posts')->where('kind', 'article')->where('slug', $entry['slug'])->first(['id', 'image']);
                if (! $post) {
                    $this->warn('SKIP absent legacy post: '.$entry['slug']);
                    $skipped++;

                    continue;
                }
                if ($post->image === $entry['image']) {
                    $this->line('SKIP already upgraded cover: '.$entry['slug']);

                    continue;
                }
                if ($post->image !== $entry['expected_image']) {
                    $this->warn('SKIP cover edited since audit: '.$entry['slug']);
                    $skipped++;

                    continue;
                }
                $this->line(($apply ? 'REPLACE cover' : 'WOULD REPLACE cover').': '.$entry['slug']);
                if ($apply) {
                    DB::table('posts')->where('id', $post->id)->where('image', $entry['expected_image'])
                        ->update(['image' => $entry['image'], 'updated_at' => now()]);
                }
                $replaced++;
            }
        }

        $cleaned = 0;
        foreach ($manifest['obsolete_inline_banners'] ?? [] as $entry) {
            $row = DB::table('posts')->where('kind', 'article')->where('slug', $entry['slug'])
                ->first(['id', 'body', 'image']);
            if (! $row || $row->image !== $entry['expected_cover']
                || ! hash_equals($entry['expected_body_sha256'], hash('sha256', (string) $row->body))) {
                $this->line('SKIP edited/absent legacy inline image: '.$entry['slug']);

                continue;
            }

            $pattern = '~<p\b[^>]*>\s*<img\b[^>]*\bsrc="'.preg_quote($entry['image_src'], '~').'"[^>]*\/?>\s*</p>~iu';
            $updated = preg_replace($pattern, '', (string) $row->body, 1, $matches);
            if (! is_string($updated) || $matches !== 1) {
                $this->warn('SKIP legacy inline image without exact target: '.$entry['slug']);

                continue;
            }
            $this->line(($apply ? 'REMOVE obsolete banner' : 'WOULD REMOVE obsolete banner').': '.$entry['slug']);
            if ($apply) {
                DB::table('posts')->where('id', $row->id)->where('body', $row->body)
                    ->update(['body' => $updated, 'updated_at' => now()]);
            }
            $cleaned++;
        }

        $this->info(($apply ? 'APPLIED' : 'PREVIEW').": drafts={$new}, already-existing={$exists}, covers={$replaced}, old-inline-banners={$cleaned}, skipped={$skipped}, media=".count($files));
        $this->line('All newly inserted posts are hidden; publish individually in CMS after reviewing.');

        return self::SUCCESS;
    }
}
