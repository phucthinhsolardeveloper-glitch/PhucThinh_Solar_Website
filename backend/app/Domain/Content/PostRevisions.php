<?php

namespace App\Domain\Content;

use App\Domain\News\NewsDetail;
use App\Domain\Routing\PublicUrls;
use App\Domain\Projects\ProjectDetail;
use App\Domain\Projects\ProjectSystem;
use App\Domain\Solutions\SolutionDetail;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Drafts and history for every kind of post (article, solution, project, policy).
 *
 * A revision holds the whole public state of a post: its columns, SEO, linked products and gallery.
 * A draft never touches the public post; publishing copies it over in one transaction. Every public
 * state is kept as a "published" revision, including saves made straight on the edit screen, so any
 * earlier version can be reopened as a draft and published again.
 */
final class PostRevisions
{
    /** Columns copied to the post on publish. */
    public const COLUMNS = ['title', 'nav_title', 'slug', 'category_id', 'summary', 'body', 'extra_body', 'image', 'cover_image',
        'is_featured', 'in_hub', 'client_name', 'address', 'province_id', 'ward_id', 'started_on', 'finished_on', 'published_at',
        'system_type', 'capacity_kwp', 'storage_kwh', 'output_kwh_month', 'saving_vnd_month', 'build_days'];

    /**
     * What a draft may change per kind. products: false = no product links, 0 = unlimited, n = at most n.
     *
     * @var array<string, array{fields: list<string>, products: int|false, media: bool}>
     */
    public const KINDS = [
        'article' => ['fields' => ['title', 'summary', 'body', 'extra_body', 'image'], 'products' => 4, 'media' => true],
        'solution' => ['fields' => ['title', 'nav_title', 'summary', 'body', 'extra_body', 'image', 'cover_image'], 'products' => false, 'media' => true],
        'project' => ['fields' => ['title', 'summary', 'body', 'client_name', 'address', 'province_id', 'ward_id', 'started_on', 'finished_on', 'image', 'cover_image',
            'system_type', 'capacity_kwp', 'storage_kwh', 'output_kwh_month', 'saving_vnd_month', 'build_days'], 'products' => 0, 'media' => true],
        'policy' => ['fields' => ['title', 'summary', 'body'], 'products' => false, 'media' => false],
    ];

    /** Labels for the comparison view. */
    public const LABELS = [
        'title' => 'Tiêu đề', 'nav_title' => 'Tên ngắn trên menu', 'slug' => 'Đường dẫn', 'category_id' => 'Nhóm', 'summary' => 'Mô tả ngắn',
        'body' => 'Nội dung chính', 'extra_body' => 'Nội dung bổ sung', 'image' => 'Ảnh đại diện', 'cover_image' => 'Ảnh bìa',
        'is_featured' => 'Nổi bật', 'in_hub' => 'Hiện ở trang tổng', 'client_name' => 'Chủ đầu tư', 'address' => 'Số nhà, đường', 'province_id' => 'Tỉnh / thành phố', 'ward_id' => 'Phường / xã',
        'started_on' => 'Khởi công', 'finished_on' => 'Hoàn thành', 'published_at' => 'Ngày đăng', 'is_visible' => 'Hiển thị',
        'system_type' => 'Loại hệ thống', 'capacity_kwp' => 'Công suất (kWp)', 'storage_kwh' => 'Lưu trữ (kWh)', 'output_kwh_month' => 'Sản lượng/tháng (kWh)',
        'saving_vnd_month' => 'Tiết kiệm/tháng (đ)', 'build_days' => 'Thi công (ngày)',
        'seo' => 'SEO', 'products' => 'Sản phẩm liên kết', 'media' => 'Thư viện ảnh / video',
    ];

    public static function profile(Post $post): array
    {
        return self::KINDS[$post->kind] ?? abort(404);
    }

    public function draft(Post $post, User $actor): PostRevision
    {
        $this->assertEditor($actor);

        return DB::transaction(function () use ($post, $actor): PostRevision {
            $locked = Post::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
            self::profile($locked);
            $existing = $locked->revisions()->where('state', 'draft')->first();
            if ($existing) {
                return $existing;
            }
            $snapshot = $this->snapshot($locked);

            return $locked->revisions()->create([
                'version' => $this->nextVersion($locked), 'state' => 'draft', 'payload' => $snapshot,
                'base_hash' => self::fingerprint($snapshot), 'created_by' => $actor->id, 'updated_by' => $actor->id,
            ]);
        });
    }

    public function save(PostRevision $revision, array $input, User $actor): PostRevision
    {
        $this->assertEditor($actor);

        return DB::transaction(function () use ($revision, $input, $actor): PostRevision {
            $draft = PostRevision::query()->whereKey($revision->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft);
            $profile = self::profile($draft->post);
            $clean = Validator::make($input, $this->rules($profile, $input))->validate();
            $payload = $draft->payload;
            foreach ($profile['fields'] as $field) {
                if (array_key_exists($field, $clean)) {
                    $value = $clean[$field];
                    // An editor left empty gives null; keep the stored "" so an untouched draft stays byte-identical.
                    $payload[$field] = $value === null && ($payload[$field] ?? null) === '' ? '' : $value;
                }
            }
            if (isset($clean['seo'])) {
                foreach (['title', 'description', 'keywords', 'image'] as $field) {
                    if (array_key_exists($field, $clean['seo'])) {
                        $payload['seo'][$field] = $clean['seo'][$field];
                    }
                }
            }
            if ($profile['products'] !== false && array_key_exists('products', $clean)) {
                $payload['products'] = array_values(array_unique(array_map('intval', $clean['products'] ?? [])));
            }
            if ($profile['media'] && array_key_exists('media', $clean)) {
                $payload['media'] = self::normaliseMedia($clean['media'] ?? []);
            }
            $draft->update(['payload' => $payload, 'updated_by' => $actor->id]);

            return $draft->refresh();
        });
    }

    public function publish(PostRevision $revision, User $actor): PostRevision
    {
        abort_unless($actor->canPerform('content_publish'), 403);

        return DB::transaction(function () use ($revision, $actor): PostRevision {
            $post = Post::query()->whereKey($revision->post_id)->lockForUpdate()->firstOrFail();
            $profile = self::profile($post);
            $draft = PostRevision::query()->whereKey($revision->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft);
            $current = $this->snapshot($post);
            if (! hash_equals((string) $draft->base_hash, self::fingerprint($current))) {
                throw ValidationException::withMessages(['draft' => 'Nội dung công khai đã thay đổi sau khi tạo nháp. Hãy xem phần so sánh, tạo nháp mới rồi chép lại thay đổi.']);
            }
            $payload = $draft->payload + $current;
            if (trim((string) ($payload['title'] ?? '')) === '' || trim(strip_tags((string) ($payload['body'] ?? ''), '<img><iframe>')) === '') {
                throw ValidationException::withMessages(['draft' => 'Cần có tiêu đề và nội dung trước khi xuất bản.']);
            }
            $checks = app(PublishChecks::class)->post(['kind' => $post->kind] + $payload, (int) $post->id);
            if ($checks['errors'] !== []) {
                throw ValidationException::withMessages(['draft' => 'Chưa xuất bản được: '.implode('; ', $checks['errors']).'.']);
            }
            if (($payload['slug'] ?? null) !== $post->slug) {
                throw ValidationException::withMessages(['draft' => 'Đường dẫn đã khác bản công khai; không tự đổi URL khi xuất bản.']);
            }
            $this->keepBaseline($post, $current);

            $columns = array_intersect_key($payload, array_flip(self::COLUMNS));
            $columns['published_at'] = $columns['published_at'] ?: now()->format('Y-m-d H:i:s');
            $columns['is_visible'] = true;
            $post->update($columns);
            $post->seo()->updateOrCreate([], array_intersect_key((array) ($payload['seo'] ?? []), array_flip(['title', 'description', 'keywords', 'image'])));
            if ($profile['products'] !== false) {
                $this->syncProducts($post, $payload['products'] ?? []);
            }
            if ($profile['media']) {
                $this->syncMedia($post, $payload['media'] ?? []);
            }
            $draft->update(['payload' => $this->snapshot($post->refresh()), 'state' => 'published', 'published_by' => $actor->id, 'published_at' => now(), 'review_state' => null]);

            return $draft->refresh();
        });
    }

    /** Opens an earlier public version as a new draft; the live post changes only when that draft is published. */
    public function restoreAsDraft(PostRevision $revision, User $actor): PostRevision
    {
        abort_unless($actor->canPerform('content_publish'), 403);

        return DB::transaction(function () use ($revision, $actor): PostRevision {
            $post = Post::query()->whereKey($revision->post_id)->lockForUpdate()->firstOrFail();
            self::profile($post);
            $historical = $post->revisions()->whereKey($revision->id)->where('state', 'published')->firstOrFail();
            if ($post->revisions()->where('state', 'draft')->exists()) {
                throw ValidationException::withMessages(['draft' => 'Đã có bản nháp. Hãy xử lý bản nháp đó trước khi khôi phục.']);
            }
            $current = $this->snapshot($post);
            // Parts an old revision did not record yet keep their current value.
            $payload = array_merge($current, $historical->payload, ['slug' => $post->slug]);

            return $post->revisions()->create([
                'version' => $this->nextVersion($post), 'state' => 'draft', 'payload' => $payload,
                'base_hash' => self::fingerprint($current), 'created_by' => $actor->id, 'updated_by' => $actor->id,
            ]);
        });
    }

    /** Before a direct save: make sure the version about to be replaced is in the history. */
    public function beforeDirectSave(Post $post): void
    {
        if ($post->exists && $post->is_visible && isset(self::KINDS[$post->kind])) {
            $this->keepBaseline($post, $this->snapshot($post));
        }
    }

    /** After a direct save of a public post: record the new public state as a version. */
    public function afterDirectSave(Post $post, ?User $actor): void
    {
        if (! $post->is_visible || ! isset(self::KINDS[$post->kind])) {
            return;
        }
        $snapshot = $this->snapshot($post->refresh());
        $latest = $post->revisions()->where('state', 'published')->orderByDesc('version')->first();
        if ($latest && self::fingerprint($latest->payload) === self::fingerprint($snapshot)) {
            return;
        }
        $post->revisions()->create(['version' => $this->nextVersion($post), 'state' => 'published', 'payload' => $snapshot,
            'created_by' => $actor?->id, 'updated_by' => $actor?->id, 'published_by' => $actor?->id, 'published_at' => now()]);
    }

    /**
     * Parts of a revision that differ from the live post.
     *
     * @return array<string, array{0: mixed, 1: mixed}> field => [live, revision]
     */
    public function changes(PostRevision $revision): array
    {
        $current = $this->snapshot($revision->post);
        $changes = [];
        foreach ($revision->payload as $field => $value) {
            if (array_key_exists($field, $current) && self::fingerprint([$current[$field]]) !== self::fingerprint([$value])) {
                $changes[$field] = [$current[$field], $value];
            }
        }

        return $changes;
    }

    /** Signed, short-lived link to the Nuxt preview of an article draft. */
    /** Nuxt preview page of each kind; the link works for 15 minutes. */
    public const PREVIEW_PATHS = ['article' => 'bai-viet', 'solution' => 'giai-phap', 'project' => 'du-an', 'policy' => 'chinh-sach'];

    public function previewUrl(PostRevision $revision): string
    {
        $kind = (string) $revision->post?->kind;
        abort_unless(isset(self::PREVIEW_PATHS[$kind]), 404);
        $route = $kind === 'article' ? 'article-draft.preview' : 'post-draft.preview';
        $signed = URL::temporarySignedRoute($route, now()->addMinutes(15), ['revision' => $revision->id], absolute: false);

        $path = '/xem-truoc/'.self::PREVIEW_PATHS[$kind].'/'.$revision->id;

        return PublicUrls::base().$path.'?'.parse_url($signed, PHP_URL_QUERY);
    }

    /**
     * The public detail of a solution, project or policy as it will look once this draft is published:
     * the draft is written into the tables inside a transaction, the normal public builder reads it,
     * and everything is rolled back. Nothing is cached by those builders, so nothing leaks.
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function previewPublic(PostRevision $revision): array
    {
        $post = $revision->post;
        $kind = (string) $post?->kind;
        if ($post === null || ! in_array($kind, ['solution', 'project', 'policy'], true)) {
            return [404, ['error' => ['code' => 'PREVIEW_NOT_FOUND']]];
        }
        $profile = self::profile($post);
        DB::beginTransaction();
        try {
            $payload = (array) $revision->payload + $this->snapshot($post);
            $columns = array_intersect_key($payload, array_flip(self::COLUMNS));
            $columns['slug'] = $post->slug; // publishing never changes the address
            $columns['is_visible'] = true;
            DB::table('posts')->where('id', $post->id)->update($columns);
            $seo = array_intersect_key((array) ($payload['seo'] ?? []), array_flip(['title', 'description', 'keywords', 'image']));
            DB::table('seo_metas')->updateOrInsert(['owner_type' => Post::class, 'owner_id' => $post->id], $seo);
            if ($profile['products'] !== false) {
                $this->syncProducts($post, $payload['products'] ?? []);
            }
            if ($profile['media']) {
                $this->syncMedia($post, $payload['media'] ?? []);
            }
            $query = ['slug' => (string) $post->slug];

            return match ($kind) {
                'solution' => app(SolutionDetail::class)->handle($query),
                'project' => app(ProjectDetail::class)->handle($query),
                default => app(NewsDetail::class)->policy($query),
            };
        } finally {
            DB::rollBack();
        }
    }

    /** @return array<string, mixed> the whole public state of a post */
    public function snapshot(Post $post): array
    {
        $data = [];
        foreach (self::COLUMNS as $field) {
            $data[$field] = $post->getRawOriginal($field);
        }
        $data['is_visible'] = (bool) $post->is_visible;
        $seo = $post->seo()->first();
        $data['seo'] = ['title' => $seo?->title, 'description' => $seo?->description, 'keywords' => $seo?->keywords, 'image' => $seo?->image];
        $data['products'] = $post->products()->orderBy('post_products.sort_order')->orderBy('post_products.id')
            ->pluck('products.id')->map(fn ($id) => (int) $id)->all();
        $data['media'] = self::normaliseMedia($post->media()->orderBy('sort_order')->orderBy('id')->get()->toArray());

        return $data;
    }

    /** Hash of a state; key order does not matter. */
    public static function fingerprint(array $data): string
    {
        $sort = function (array $value) use (&$sort): array {
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map(fn ($item) => is_array($item) ? $sort($item) : $item, $value);
        };

        return hash('sha256', json_encode($sort($data), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @return list<array{kind: string, path: ?string, video_url: ?string, caption: ?string, is_visible: bool}> */
    private static function normaliseMedia(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $kind = ($item['kind'] ?? 'image') === 'video' ? 'video' : 'image';
            $path = is_string($item['path'] ?? null) && $item['path'] !== '' ? $item['path'] : null;
            $video = is_string($item['video_url'] ?? null) && $item['video_url'] !== '' ? $item['video_url'] : null;
            if ($path === null && $video === null) {
                continue;
            }
            $out[] = ['kind' => $kind, 'path' => $path, 'video_url' => $kind === 'video' ? $video : null,
                'caption' => is_string($item['caption'] ?? null) && $item['caption'] !== '' ? $item['caption'] : null,
                'is_visible' => (bool) ($item['is_visible'] ?? true)];
        }

        return $out;
    }

    private function rules(array $profile, array $input): array
    {
        $file = ['nullable', 'string', 'max:255', 'not_regex:#(^|/)\.\.(/|$)#'];

        return [
            'title' => ['required', 'string', 'max:255'],
            'nav_title' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string', 'max:1000000'],
            'extra_body' => ['nullable', 'string', 'max:1000000'],
            'image' => $file,
            'cover_image' => $file,
            'client_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'integer', Rule::exists('provinces', 'id')],
            'ward_id' => ['nullable', 'integer', Rule::exists('wards', 'id')->where(fn ($q) => $q->where('province_id', (int) ($input['province_id'] ?? 0)))],
            'started_on' => ['nullable', 'date'],
            'finished_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            ...ProjectSystem::rules(),
            'seo' => ['sometimes', 'array'],
            'seo.title' => ['nullable', 'string', 'max:255'],
            'seo.description' => ['nullable', 'string', 'max:500'],
            'seo.keywords' => ['nullable', 'string', 'max:1000'],
            'seo.image' => $file,
            'products' => $profile['products'] ? ['sometimes', 'nullable', 'array', 'max:'.$profile['products']] : ['sometimes', 'nullable', 'array'],
            'products.*' => ['integer', Rule::exists('products', 'id')->where('is_visible', true)],
            'media' => ['sometimes', 'nullable', 'array', 'max:60'],
            'media.*.kind' => ['nullable', Rule::in(['image', 'video'])],
            'media.*.path' => $file,
            'media.*.video_url' => ['nullable', 'url', 'max:255'],
            'media.*.caption' => ['nullable', 'string', 'max:255'],
            'media.*.is_visible' => ['nullable', 'boolean'],
        ];
    }

    /** The first publish of a post that was already public keeps that public state as version 0. */
    private function keepBaseline(Post $post, array $current): void
    {
        if ($post->is_visible && ! $post->revisions()->where('state', 'published')->exists()) {
            $post->revisions()->create(['version' => 0, 'state' => 'published', 'payload' => $current, 'published_at' => now()]);
        }
    }

    private function syncProducts(Post $post, array $ids): void
    {
        $post->products()->sync(collect(array_values($ids))->mapWithKeys(fn ($id, $i) => [(int) $id => ['sort_order' => $i]])->all());
    }

    private function syncMedia(Post $post, array $items): void
    {
        $post->media()->delete();
        foreach (self::normaliseMedia($items) as $i => $item) {
            $post->media()->create($item + ['sort_order' => $i]);
        }
    }

    private function nextVersion(Post $post): int
    {
        return (int) $post->revisions()->max('version') + 1;
    }

    private function assertEditor(User $actor): void
    {
        abort_unless($actor->is_active && $actor->hasPermission('content'), 403);
    }

    private function assertDraft(PostRevision $revision): void
    {
        abort_unless($revision->state === 'draft' && isset(self::KINDS[(string) $revision->post?->kind]), 404);
    }
}
