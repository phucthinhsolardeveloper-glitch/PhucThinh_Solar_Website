<?php

namespace App\Console\Commands;

use App\Models\EditorialPlanItem;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Import the 180-slot source calendar into an internal editorial planner.
 * Only a body-backed, explicitly reviewed draft can later be enabled in CMS;
 * calendar rows never become public posts merely because their time has arrived.
 */
final class EditorialCalendarImport extends Command
{
    protected $signature = 'pts:editorial-calendar {--apply : Import records (otherwise dry run)}
        {--skip-images : Keep existing media without copying new branded covers}';

    protected $description = 'Prepare 180 source-controlled editorial slots, branded covers and any fully written hidden drafts.';

    private const ROOT = 'resources/editorial/2026-11-12';

    private const FIRST_THREE = [
        'PTS-KT-001' => 'dien-mat-troi-hoat-dong-nhu-the-nao',
        'PTS-KT-002' => 'cach-doc-datasheet-tam-pin-nang-luong-mat-troi',
        'PTS-KT-003' => 'thi-truong-dien-mat-troi-toan-cau-2026-iea-pvps',
    ];

    private const FIRST_COVERS = [
        'PTS-KT-001' => 'pts-solar-how-it-works.webp',
        'PTS-KT-002' => 'pts-solar-datasheet-guide.webp',
        'PTS-KT-003' => 'pts-global-pv-trends-2026.webp',
    ];

    public function handle(): int
    {
        if (! Schema::hasTable('editorial_plan_items')) {
            $this->error('Chưa có bảng editorial_plan_items. Chạy migration của release trước.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $base = base_path(self::ROOT);
        $handle = fopen($base.'/calendar-180.csv', 'rb');
        if ($handle === false) {
            throw new RuntimeException('Missing source calendar CSV');
        }
        try {
            $head = fgetcsv($handle, 0, ',', '"', '');
            if (! is_array($head)) {
                throw new RuntimeException('Invalid CSV header');
            }
            $rows = [];
            while (($cells = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (count($cells) !== count($head)) {
                    throw new RuntimeException('CSV row has wrong column count: '.count($cells));
                }
                $row = array_combine($head, $cells);
                if (! is_array($row) || ! preg_match('/^PTS-KT-\d{3}$/', (string) $row['id'])) {
                    throw new RuntimeException('Invalid row ID in editorial calendar');
                }
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }
        if (count($rows) !== 180 || count(array_unique(array_column($rows, 'id'))) !== 180) {
            throw new RuntimeException('Expected 180 distinct editorial entries, found '.count($rows));
        }
        $prewritten = json_decode((string) file_get_contents($base.'/written-articles.json'), true, flags: JSON_THROW_ON_ERROR);
        $writtenById = collect($prewritten['articles'] ?? [])->keyBy('id');
        $disk = Storage::disk('upload_news');
        $assets = rtrim((string) config('site.assets_path'), '/');
        $created = 0;
        $already = 0;
        $postsCreated = 0;
        $mediaCount = 0;
        $times = [];
        $keys = [];

        // Validate all source and prepared assets before making any DB changes.
        foreach ($rows as $i => $row) {
            $key = $row['id'];
            $day = Carbon::parse($row['date'])->format('Y-m-d'); // Dates in CSV already reflect the final publishing schedule.
            $time = (string) $row['time'];
            if (! in_array($time, ['08:00', '12:00', '19:30'], true)
                || $day !== Carbon::parse('2026-11-13')->addDays(intdiv($i, 3))->toDateString()
                || ! in_array($row['menu'], ['Kiến thức sản phẩm', 'Tin mới nhất'], true)) {
                throw new RuntimeException('Invalid day/slot/category on '.$key);
            }
            $times[] = $day.' '.$time;
            $keys[] = $key;
            $image = self::FIRST_COVERS[$key] ?? 'pts-plan-'.substr($key, -3).'.webp';
            $path = $assets.'/images/editorial/'.$image;
            if (! is_file($path) || ! preg_match('/^pts-[a-z0-9-]+\.webp$/', $image)) {
                throw new RuntimeException('Missing editorial image: '.$path);
            }
            if (! $this->option('skip-images') && $disk->exists($image)
                && hash_file('sha256', $disk->path($image)) !== hash_file('sha256', $path)) {
                throw new RuntimeException('Media checksum conflict; manual review required: '.$image);
            }
            if ($writtenById->has($key)) {
                $written = $writtenById->get($key);
                if (! is_array($written) || ! is_file($base.'/articles/'.$key.'.md')) {
                    throw new RuntimeException('Missing prepared article: '.$key);
                }
            }
        }
        if (count(array_unique($times)) !== 180) {
            throw new RuntimeException('Duplicate publishing slots');
        }

        foreach ($rows as $row) {
            $key = $row['id'];
            $scheduled = Carbon::parse($row['date'].' '.$row['time'], 'Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s');
            $image = self::FIRST_COVERS[$key] ?? 'pts-plan-'.substr($key, -3).'.webp';
            $written = $writtenById->get($key);
            $actualSlug = self::FIRST_THREE[$key] ?? $row['slug_draft'];
            $post = Post::query()->where('kind', 'article')->where('slug', $actualSlug)->first();
            $hasWritten = isset(self::FIRST_THREE[$key]) || is_array($written);
            if ($hasWritten && ! $post && is_array($written)) {
                $this->line(($apply ? 'CREATE hidden draft ' : 'WOULD CREATE draft ').$key.': '.$actualSlug);
                if ($apply) {
                    // The public page already renders the article title as H1.
                    // Remove the Markdown H1 to avoid a duplicate heading in the body.
                    $sourceMarkdown = (string) file_get_contents($base.'/articles/'.$key.'.md');
                    $sourceMarkdown = preg_replace('/^# [^\r\n]+\r?\n/u', '', $sourceMarkdown) ?? $sourceMarkdown;
                    $body = Str::markdown($sourceMarkdown,
                        ['html_input' => 'strip', 'allow_unsafe_links' => false]);
                    if (mb_strlen(trim(strip_tags($body))) < 1200) {
                        throw new RuntimeException('Article too short for publication pipeline: '.$key);
                    }
                    $post = DB::transaction(function () use ($row, $written, $body, $image, $scheduled, $actualSlug): Post {
                        $model = Post::query()->firstOrCreate(
                            ['kind' => 'article', 'slug' => $actualSlug],
                            [
                                'title' => $row['title'], 'kind' => 'article', 'summary' => $written['summary'],
                                'body' => $body, 'image' => $image,
                                'category_id' => DB::table('post_categories')
                                    ->where('kind', 'article')
                                    ->where('slug', $row['menu'] === 'Tin mới nhất' ? 'tin-moi-nhat' : 'kien-thuc-san-pham')
                                    ->value('id'),
                                'is_visible' => false, 'published_at' => $scheduled,
                                'is_featured' => false, 'in_hub' => false,
                            ],
                        );
                        $model->seo()->firstOrCreate([], [
                            'title' => Str::limit((string) $row['title'], 68, ''),
                            'description' => Str::limit((string) $written['summary'], 158, ''),
                            'keywords' => $written['keywords'] ?? '',
                        ]);

                        return $model;
                    });
                    $postsCreated++;
                }
            }

            // A publish timestamp on the three existing first-day drafts is still safe:
            // is_visible remains false until a publisher explicitly approves and enables it.
            if ($apply && $post && isset(self::FIRST_THREE[$key])
                && ! $post->is_visible && ! $post->published_at) {
                $post->update(['published_at' => $scheduled]);
            }

            $attributes = [
                'scheduled_for' => $scheduled,
                'title' => trim((string) $row['title']),
                'suggested_slug' => $actualSlug,
                'category_slug' => $row['menu'] === 'Tin mới nhất' ? 'tin-moi-nhat' : 'kien-thuc-san-pham',
                'stream' => $row['stream'],
                'pillar' => $row['pillar'],
                'priority' => $row['priority'],
                'stage' => $hasWritten ? 'review' : 'idea',
                'angle' => $row['angle'],
                'primary_source_name' => $row['primary_source_name'],
                'primary_source_url' => $row['primary_source_url'],
                'secondary_source_url' => $row['secondary_source_url'],
                'image_brief' => $row['image_brief'],
                'image_search_url' => $row['image_search_url'],
                'cover_file' => $image,
                'image_status' => isset(self::FIRST_COVERS[$key]) ? 'approved' : 'mockup',
                'logo_variant' => 'wide',
                'post_id' => $post?->id,
            ];

            if (! EditorialPlanItem::query()->where('plan_key', $key)->exists()) {
                $created++;
                if ($apply) {
                    EditorialPlanItem::query()->create(['plan_key' => $key] + $attributes);
                }
            } else {
                // Never overwrite an editor's changed date, source, status, image or notes.
                $already++;
            }

            if ($apply && ! $this->option('skip-images') && ! $disk->exists($image)) {
                if (! app()->environment('local')) {
                    if (! $disk->put($image, file_get_contents($assets.'/images/editorial/'.$image))) {
                        throw new RuntimeException('Could not copy image to upload/news: '.$image);
                    }
                } // On local ProductMediaUrls resolves the read-only upload fallback.
                $mediaCount++;
            }
        }

        $this->info(($apply ? 'APPLIED' : 'PREVIEW').": slots_new={$created}, existing={$already}, drafts_new={$postsCreated}, media_missing={$mediaCount}");
        $this->line('60 days, 3 slots/day: 13/11/2026 – 11/01/2027, Asia/Ho_Chi_Minh.');
        $this->line('Plan status NEVER auto-publishes a post. Reviewed articles still require is_visible enabled by a publisher.');

        return self::SUCCESS;
    }
}
