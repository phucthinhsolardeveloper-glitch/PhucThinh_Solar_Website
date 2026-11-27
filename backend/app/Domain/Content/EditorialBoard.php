<?php

namespace App\Domain\Content;

use App\Models\EditorialPlanItem;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The editorial calendar as one list: real posts and planned topics, each in exactly one state, plus the
 * moves staff make from the calendar. A planned topic that already has a post is shown only as that post.
 */
final class EditorialBoard
{
    public const LIVE = 'live';

    public const SCHEDULED = 'scheduled';

    /** Written, switched off: someone has to read it and switch it on. */
    public const WAITING = 'waiting';

    /** Article created from a topic, still without text. */
    public const WRITING = 'writing';

    /** Switched off and not part of the approval queue: taken off the website, or never planned. */
    public const HIDDEN = 'hidden';

    /** Planned topic with no article yet. */
    public const TODO = 'todo';

    /** Planned topic with no article and its day has passed. */
    public const LATE = 'late';

    public const STATES = [
        self::LIVE => 'Đang hiện', self::SCHEDULED => 'Hẹn giờ', self::WAITING => 'Chờ duyệt',
        self::TODO => 'Chưa viết', self::LATE => 'Trễ, chưa viết', self::WRITING => 'Đang viết', self::HIDDEN => 'Đang ẩn',
    ];

    public const SLOTS = ['08:00', '12:00', '19:30'];

    private const CATEGORIES = ['kien-thuc-san-pham', 'tin-moi-nhat', 'tin-truyen-thong'];

    /** @return list<array{type: string, id: int, title: string, kind: string, at: Carbon, state: string, movable: bool}> */
    public function items(Carbon $from, Carbon $to): array
    {
        $items = [];
        $hidden = $this->hiddenStates();
        foreach (Post::query()->whereBetween('published_at', [$from, $to])->get(['id', 'kind', 'title', 'is_visible', 'published_at']) as $post) {
            $items[] = $this->postItem($post, Carbon::parse($post->published_at), $hidden);
        }
        if (Schema::hasTable('editorial_plan_items')) {
            $plans = EditorialPlanItem::query()->with('post:id,kind,title,is_visible,published_at')
                ->whereBetween('scheduled_for', [$from, $to])->get();
            foreach ($plans as $plan) {
                /** @var Post|null $post */
                $post = $plan->post;
                if ($post === null) {
                    $items[] = $this->planItem($plan);
                } elseif ($post->published_at === null) {
                    /** @var Carbon $at */
                    $at = $plan->scheduled_for;
                    $items[] = $this->postItem($post, $at, $hidden);
                }
            }
        }
        usort($items, fn (array $a, array $b) => [$a['at']->getTimestamp(), $a['id']] <=> [$b['at']->getTimestamp(), $b['id']]);

        return $items;
    }

    /** Totals across all dates, so work outside the shown month is never forgotten. */
    public function totals(): array
    {
        $plans = Schema::hasTable('editorial_plan_items');

        return [
            self::WAITING => $this->waiting()->count(),
            self::SCHEDULED => Post::query()->where('is_visible', true)->where('published_at', '>', now())->count(),
            self::LATE => $plans ? EditorialPlanItem::query()->whereNull('post_id')->where('scheduled_for', '<', now())->count() : 0,
            self::TODO => $plans ? EditorialPlanItem::query()->whereNull('post_id')->where('scheduled_for', '>=', now())->count() : 0,
        ];
    }

    /**
     * The approval queue: planned articles that have text, are switched off and were never on the website.
     *
     * @return Builder<Post>
     */
    public function waiting(): Builder
    {
        if (! Schema::hasTable('editorial_plan_items')) {
            return Post::query()->whereRaw('1 = 0');
        }

        return Post::query()->where('kind', 'article')->where('is_visible', false)
            ->whereIn('posts.id', EditorialPlanItem::query()->whereNotNull('post_id')->select('post_id'))
            ->whereRaw("TRIM(COALESCE(posts.body, '')) <> ''")
            ->whereDoesntHave('revisions', fn ($q) => $q->where('state', 'published'))
            ->orderBy('published_at')->orderBy('posts.id');
    }

    public function stateOf(Post $post): string
    {
        if ($post->is_visible) {
            return PostVisibility::isScheduled($post) ? self::SCHEDULED : self::LIVE;
        }

        return $this->hiddenStates()[(int) $post->id] ?? self::HIDDEN;
    }

    /** @return array<int, string> post id => "waiting" or "writing"; every other switched-off post is plain hidden */
    private function hiddenStates(): array
    {
        if (! Schema::hasTable('editorial_plan_items')) {
            return [];
        }
        $states = Post::query()->where('kind', 'article')->where('is_visible', false)
            ->whereIn('id', EditorialPlanItem::query()->whereNotNull('post_id')->select('post_id'))
            ->whereRaw("TRIM(COALESCE(body, '')) = ''")->pluck('id')->mapWithKeys(fn ($id) => [(int) $id => self::WRITING])->all();

        return $states + $this->waiting()->pluck('posts.id')->mapWithKeys(fn ($id) => [(int) $id => self::WAITING])->all();
    }

    /** The first publishing hour from now on that no article on the website or already scheduled occupies. */
    public function nextFreeSlot(): Carbon
    {
        $taken = Post::query()->where('kind', 'article')->where('is_visible', true)->where('published_at', '>', now())
            ->pluck('published_at')->map(fn ($at) => Carbon::parse($at)->format('Y-m-d H:i'))->flip();
        for ($day = today(); ; $day = $day->addDay()) {
            foreach (self::SLOTS as $slot) {
                $at = $day->copy()->setTimeFromTimeString($slot);
                if ($at->gt(now()) && ! $taken->has($at->format('Y-m-d H:i'))) {
                    return $at;
                }
            }
        }
    }

    /**
     * Creates the hidden article behind a planned topic. Returns the existing one if it is already there.
     *
     * @param  array{body?: string, summary?: string, seo_description?: string}  $content
     */
    public function createDraft(EditorialPlanItem $plan, array $content = []): Post
    {
        return DB::transaction(function () use ($plan, $content): Post {
            $locked = EditorialPlanItem::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            if ($locked->post_id && ($existing = Post::query()->find($locked->post_id))) {
                return $existing;
            }
            $category = in_array($locked->category_slug, self::CATEGORIES, true) ? $locked->category_slug : self::CATEGORIES[0];
            $summary = trim((string) ($content['summary'] ?? ''));
            $post = Post::query()->create([
                'kind' => 'article', 'title' => $locked->title, 'slug' => $this->freeSlug((string) ($locked->suggested_slug ?: $locked->title)),
                'category_id' => DB::table('post_categories')->where('kind', 'article')->where('slug', $category)->value('id'),
                'summary' => $summary, 'body' => (string) ($content['body'] ?? ''), 'image' => (string) $locked->cover_file,
                'is_visible' => false, 'is_featured' => false, 'in_hub' => false, 'published_at' => $locked->scheduled_for,
            ]);
            $post->seo()->create([
                'title' => Str::limit((string) $locked->title, 68, ''),
                'description' => Str::limit((string) ($content['seo_description'] ?? $summary), 158, ''),
            ]);
            $locked->update(['post_id' => $post->id, 'stage' => isset($content['body']) ? 'review' : 'writing']);

            return $post;
        });
    }

    /** Switches a post on: now, or at a future time. */
    public function publish(Post $post, ?Carbon $at, User $actor): void
    {
        abort_unless($actor->canPerform('content_publish'), 403);
        if ($at !== null && $at->lte(now())) {
            throw ValidationException::withMessages(['when' => 'Giờ hẹn phải ở tương lai.']);
        }
        $checks = app(PublishChecks::class)->post(PublishChecks::postData($post), (int) $post->id);
        if ($checks['errors'] !== []) {
            throw ValidationException::withMessages(['when' => 'Chưa đăng được: '.implode('; ', $checks['errors']).'.']);
        }
        DB::transaction(function () use ($post, $at, $actor): void {
            $revisions = app(PostRevisions::class);
            $revisions->beforeDirectSave($post);
            // An article that was on the website before keeps its date when it comes back; a new one gets today.
            $before = $post->published_at ? Carbon::parse($post->published_at) : null;
            $wasPublic = $before?->lte(now()) && $post->revisions()->where('state', 'published')->exists();
            $post->update(['is_visible' => true, 'published_at' => ($at ?? ($wasPublic ? $before : now()))->format('Y-m-d H:i:s')]);
            $this->syncPlan($post, $at !== null ? 'scheduled' : 'published');
            $revisions->afterDirectSave($post, $actor);
        });
    }

    public function hide(Post $post, User $actor): void
    {
        abort_unless($actor->canPerform('content_publish'), 403);
        DB::transaction(function () use ($post): void {
            app(PostRevisions::class)->beforeDirectSave($post);
            $post->update(['is_visible' => false]);
            $this->syncPlan($post, 'review');
        });
    }

    /** Moves an item to another day, keeping its time. Live posts never move: their date is public. */
    public function move(string $type, int $id, Carbon $day, User $actor): void
    {
        if ($type === 'plan') {
            $plan = EditorialPlanItem::query()->whereNull('post_id')->findOrFail($id);
            /** @var Carbon $old */
            $old = $plan->scheduled_for;
            $plan->update(['scheduled_for' => $this->futureAt($day, $old)]);

            return;
        }
        $post = Post::query()->findOrFail($id);
        $state = $this->stateOf($post);
        if ($state === self::LIVE) {
            throw ValidationException::withMessages(['when' => 'Bài đang hiện trên website nên không dời ngày từ lịch.']);
        }
        if ($state === self::SCHEDULED) {
            abort_unless($actor->canPerform('content_publish'), 403);
        }
        $at = $this->futureAt($day, $post->published_at ? Carbon::parse($post->published_at) : Carbon::parse(self::SLOTS[0]));
        $post->update(['published_at' => $at->format('Y-m-d H:i:s')]);
        $this->syncPlan($post, match ($state) {
            self::SCHEDULED => 'scheduled', self::WRITING => 'writing', default => 'review',
        });
    }

    /**
     * Puts every topic that has no article back into a queue from the given day, in the same order.
     *
     * @return int topics moved
     */
    public function requeue(Carbon $from, int $perDay): int
    {
        $perDay = max(1, min(count(self::SLOTS), $perDay));
        if ($from->copy()->startOfDay()->lt(today())) {
            throw ValidationException::withMessages(['from' => 'Chọn từ hôm nay trở đi.']);
        }

        return DB::transaction(function () use ($from, $perDay): int {
            $day = $from->copy()->startOfDay();
            $used = 0;
            $moved = 0;
            foreach (EditorialPlanItem::query()->whereNull('post_id')->orderBy('scheduled_for')->orderBy('plan_key')->lockForUpdate()->get() as $plan) {
                do {
                    if ($used >= $perDay) {
                        $day->addDay();
                        $used = 0;
                    }
                    $at = $day->copy()->setTimeFromTimeString(self::SLOTS[$used]);
                    $used++;
                } while ($at->lte(now()));
                $plan->update(['scheduled_for' => $at]);
                $moved++;
            }

            return $moved;
        });
    }

    private function futureAt(Carbon $day, Carbon $time): Carbon
    {
        $at = $day->copy()->setTime($time->hour, $time->minute);
        if ($at->lte(now())) {
            throw ValidationException::withMessages(['when' => 'Chỉ dời tới thời điểm chưa qua.']);
        }

        return $at;
    }

    private function syncPlan(Post $post, string $stage): void
    {
        if (Schema::hasTable('editorial_plan_items')) {
            EditorialPlanItem::query()->where('post_id', $post->id)->get()
                ->each->update(['stage' => $stage, 'scheduled_for' => $post->published_at]);
        }
    }

    private function freeSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'bai-viet';
        $checks = app(PublishChecks::class);
        $slug = $base;
        for ($i = 2; in_array('Đường dẫn trùng với một trang khác trên website', $checks->post(['kind' => 'article', 'title' => 'x', 'slug' => $slug, 'body' => 'x'])['errors'], true); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    /** @param array<int, string> $hidden */
    private function postItem(Post $post, Carbon $at, array $hidden): array
    {
        $state = $post->is_visible ? $this->stateOf($post) : ($hidden[(int) $post->id] ?? self::HIDDEN);

        return ['type' => 'post', 'id' => (int) $post->id, 'title' => (string) $post->title, 'kind' => (string) $post->kind,
            'at' => $at, 'state' => $state, 'movable' => $state !== self::LIVE];
    }

    private function planItem(EditorialPlanItem $plan): array
    {
        /** @var Carbon $at */
        $at = $plan->scheduled_for;

        return ['type' => 'plan', 'id' => (int) $plan->id, 'title' => (string) $plan->title, 'kind' => 'plan',
            'at' => $at, 'state' => $at->isPast() ? self::LATE : self::TODO, 'movable' => true];
    }
}
