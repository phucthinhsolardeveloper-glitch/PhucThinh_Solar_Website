<?php

namespace App\Domain\Content;

use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Revisions for solution, project and policy content. Public records change only on publish. */
class PostWorkflow
{
    private const KINDS = ['solution', 'project', 'policy'];

    private const FIELDS = [
        'title', 'nav_title', 'slug', 'category_id', 'summary', 'body', 'extra_body',
        'image', 'cover_image', 'is_featured', 'in_hub', 'client_name', 'address',
        'started_on', 'finished_on', 'published_at',
    ];

    public function draft(Post $post, User $actor): PostRevision
    {
        $this->assertEditor($actor);

        return DB::transaction(function () use ($post, $actor): PostRevision {
            $locked = Post::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
            $this->assertSupported($locked);
            $existing = $locked->revisions()->where('state', 'draft')->first();
            if ($existing) {
                return $existing;
            }
            $snapshot = $this->snapshot($locked);

            return $locked->revisions()->create([
                'version' => (int) $locked->revisions()->max('version') + 1,
                'state' => 'draft', 'payload' => $snapshot, 'base_hash' => $this->hash($snapshot),
                'created_by' => $actor->id, 'updated_by' => $actor->id,
            ]);
        });
    }

    public function save(PostRevision $revision, array $input, User $actor): PostRevision
    {
        $this->assertEditor($actor);

        return DB::transaction(function () use ($revision, $input, $actor): PostRevision {
            $draft = PostRevision::query()->whereKey($revision->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft);
            $kind = $draft->post->kind;
            $clean = Validator::make($input, [
                'title' => ['required', 'string', 'max:255'],
                'nav_title' => ['nullable', 'string', 'max:255'],
                'summary' => ['nullable', 'string', 'max:2000'],
                'body' => ['nullable', 'string', 'max:1000000'],
                'extra_body' => ['nullable', 'string', 'max:1000000'],
                'client_name' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'started_on' => ['nullable', 'date'],
                'finished_on' => ['nullable', 'date', 'after_or_equal:started_on'],
                'seo' => ['required', 'array'],
                'seo.title' => ['nullable', 'string', 'max:255'],
                'seo.description' => ['nullable', 'string', 'max:500'],
                'seo.keywords' => ['nullable', 'string', 'max:1000'],
                'products' => ['sometimes', 'array'],
                'products.*' => ['integer', Rule::exists('products', 'id')->where('is_visible', true)],
            ])->validate();
            $payload = $draft->payload;
            $fields = ['title', 'summary', 'body'];
            if ($kind === 'solution') {
                array_push($fields, 'nav_title', 'extra_body');
            }
            if ($kind === 'project') {
                array_push($fields, 'client_name', 'address', 'started_on', 'finished_on');
            }
            foreach ($fields as $field) {
                if (array_key_exists($field, $clean)) {
                    $value = $clean[$field];
                    $payload[$field] = $value === null && ($payload[$field] ?? null) === '' ? '' : $value;
                }
            }
            foreach (['title', 'description', 'keywords'] as $field) {
                $payload['seo'][$field] = $clean['seo'][$field] ?? null;
            }
            if ($kind === 'project' && array_key_exists('products', $clean)) {
                $payload['products'] = array_values(array_unique(array_map('intval', $clean['products'])));
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
            $this->assertSupported($post);
            $draft = PostRevision::query()->whereKey($revision->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft);
            $current = $this->snapshot($post);
            if (! hash_equals((string) $draft->base_hash, $this->hash($current))) {
                throw ValidationException::withMessages(['draft' => 'Nội dung công khai đã thay đổi sau khi tạo nháp. Hãy rà soát lại trước khi xuất bản.']);
            }
            $payload = $draft->payload;
            if (trim((string) ($payload['title'] ?? '')) === '' || trim(strip_tags((string) ($payload['body'] ?? ''))) === '') {
                throw ValidationException::withMessages(['draft' => 'Cần có tiêu đề và nội dung trước khi xuất bản.']);
            }
            if (($payload['slug'] ?? null) !== $post->slug) {
                throw ValidationException::withMessages(['draft' => 'Đường dẫn đã khác bản công khai; không tự đổi URL khi xuất bản.']);
            }
            if ($post->is_visible && ! $post->revisions()->where('state', 'published')->exists()) {
                $post->revisions()->create(['version' => 0, 'state' => 'published', 'payload' => $current, 'published_at' => now()]);
            }
            $fields = array_intersect_key($payload, array_flip(self::FIELDS));
            $fields['published_at'] = $fields['published_at'] ?: now()->timestamp;
            $fields['is_visible'] = true;
            $post->update($fields);
            $post->seo()->updateOrCreate([], $payload['seo'] ?? []);
            if ($post->kind === 'project' && array_key_exists('products', $payload)) {
                $post->products()->sync($payload['products']);
            }
            $payload['published_at'] = $fields['published_at'];
            $payload['is_visible'] = true;
            $draft->update(['payload' => $payload, 'state' => 'published', 'published_by' => $actor->id, 'published_at' => now()]);

            return $draft->refresh();
        });
    }

    public function restoreAsDraft(PostRevision $revision, User $actor): PostRevision
    {
        abort_unless($actor->canPerform('content_publish'), 403);

        return DB::transaction(function () use ($revision, $actor): PostRevision {
            $post = Post::query()->whereKey($revision->post_id)->lockForUpdate()->firstOrFail();
            $this->assertSupported($post);
            $historical = $post->revisions()->whereKey($revision->id)->where('state', 'published')->firstOrFail();
            if ($post->revisions()->where('state', 'draft')->exists()) {
                throw ValidationException::withMessages(['draft' => 'Đã có bản nháp. Hãy xử lý bản nháp đó trước khi khôi phục.']);
            }
            $current = $this->snapshot($post);
            $payload = $historical->payload;
            $payload['slug'] = $post->slug;
            $payload['products'] ??= $current['products'];

            return $post->revisions()->create([
                'version' => (int) $post->revisions()->max('version') + 1,
                'state' => 'draft', 'payload' => $payload, 'base_hash' => $this->hash($current),
                'created_by' => $actor->id, 'updated_by' => $actor->id,
            ]);
        });
    }

    private function snapshot(Post $post): array
    {
        $data = [];
        foreach (self::FIELDS as $field) {
            $data[$field] = $post->getRawOriginal($field);
        }
        $data['is_visible'] = (bool) $post->is_visible;
        $seo = $post->seo()->first();
        $data['seo'] = ['title' => $seo?->title, 'description' => $seo?->description,
            'keywords' => $seo?->keywords, 'image' => $seo?->image];
        $data['products'] = $post->products()->orderBy('post_products.sort_order')->orderBy('post_products.id')
            ->pluck('products.id')->map(fn ($id) => (int) $id)->all();

        return $data;
    }

    private function hash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function assertEditor(User $actor): void
    {
        abort_unless($actor->is_active && $actor->hasPermission('content'), 403);
    }

    private function assertSupported(Post $post): void
    {
        abort_unless(in_array($post->kind, self::KINDS, true), 404);
    }

    private function assertDraft(PostRevision $revision): void
    {
        abort_unless($revision->state === 'draft' && in_array($revision->post?->kind, self::KINDS, true), 404);
    }
}
