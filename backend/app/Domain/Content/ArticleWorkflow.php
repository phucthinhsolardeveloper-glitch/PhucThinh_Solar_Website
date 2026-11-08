<?php

namespace App\Domain\Content;

use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Drafts never modify the published post. A publish or restore is one database transaction. */
class ArticleWorkflow
{
    private const POST_FIELDS = ['title', 'nav_title', 'slug', 'category_id', 'summary', 'body', 'extra_body', 'image', 'cover_image', 'is_featured', 'in_hub', 'published_at'];

    private const EDITABLE_FIELDS = ['title', 'summary', 'body', 'extra_body'];

    public function previewUrl(PostRevision $revision): string
    {
        abort_unless($revision->post?->kind === 'article', 404);
        $signed = URL::temporarySignedRoute('article-draft.preview', now()->addMinutes(15), ['revision' => $revision->id], absolute: false);
        $query = parse_url($signed, PHP_URL_QUERY);

        return url('/xem-truoc/bai-viet/'.$revision->id).'?'.$query;
    }

    public function draft(Post $post, User $actor): PostRevision
    {
        abort_unless($actor->is_active && $actor->hasPermission('content'), 403);

        return DB::transaction(function () use ($post, $actor): PostRevision {
            $locked = Post::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
            $this->assertArticle($locked);
            $existing = $locked->revisions()->where('state', 'draft')->first();
            if ($existing) {
                return $existing;
            }

            $snapshot = $this->snapshot($locked);

            return $locked->revisions()->create([
                'version' => $this->nextVersion($locked), 'state' => 'draft', 'payload' => $snapshot,
                'base_hash' => $this->fingerprint($snapshot), 'created_by' => $actor->id, 'updated_by' => $actor->id,
            ]);
        });
    }

    public function save(PostRevision $revision, array $input, User $actor): PostRevision
    {
        abort_unless($actor->is_active && $actor->hasPermission('content'), 403);

        return DB::transaction(function () use ($revision, $input, $actor): PostRevision {
            $draft = PostRevision::query()->whereKey($revision->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft);
            $clean = Validator::make($input, [
                'title' => ['required', 'string', 'max:255'],
                'summary' => ['nullable', 'string', 'max:2000'],
                'body' => ['nullable', 'string', 'max:1000000'],
                'extra_body' => ['nullable', 'string', 'max:1000000'],
                'seo' => ['required', 'array'],
                'seo.title' => ['nullable', 'string', 'max:255'],
                'seo.description' => ['nullable', 'string', 'max:500'],
                'seo.keywords' => ['nullable', 'string', 'max:1000'],
                'products' => ['sometimes', 'array', 'max:4'],
                'products.*' => ['integer', Rule::exists('products', 'id')->where('is_visible', true)],
            ])->validate();
            $payload = $draft->payload;
            foreach (self::EDITABLE_FIELDS as $field) {
                $value = $clean[$field] ?? null;
                $payload[$field] = in_array($field, ['body', 'extra_body'], true) && $value === null && ($payload[$field] ?? null) === '' ? '' : $value;
            }
            foreach (['title', 'description', 'keywords'] as $field) {
                $payload['seo'][$field] = $clean['seo'][$field] ?? null;
            }
            if (array_key_exists('products', $input)) {
                $payload['products'] = array_values(array_unique(array_map('intval', $clean['products'] ?? [])));
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
            $this->assertArticle($post);
            $draft = PostRevision::query()->whereKey($revision->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft);
            $current = $this->snapshot($post);
            $legacyBase = $current;
            unset($legacyBase['products']);
            if (! hash_equals((string) $draft->base_hash, $this->fingerprint($current))
                && ! hash_equals((string) $draft->base_hash, $this->fingerprint($legacyBase))) {
                throw ValidationException::withMessages(['draft' => 'Bài public đã thay đổi sau khi tạo nháp. Hãy rà soát và tạo nháp mới.']);
            }
            $payload = $draft->payload;
            if (trim((string) ($payload['title'] ?? '')) === '' || trim(strip_tags((string) ($payload['body'] ?? ''))) === '') {
                throw ValidationException::withMessages(['draft' => 'Cần có tiêu đề và nội dung trước khi xuất bản.']);
            }
            if (($payload['slug'] ?? null) !== $post->slug) {
                throw ValidationException::withMessages(['draft' => 'Đường dẫn bài viết đã khác bản public; không tự đổi URL khi xuất bản.']);
            }
            if ($post->is_visible && ! $post->revisions()->where('state', 'published')->exists()) {
                $post->revisions()->create(['version' => 0, 'state' => 'published', 'payload' => $current,
                    'published_at' => now()]);
            }

            $fields = array_intersect_key($payload, array_flip(self::POST_FIELDS));
            $fields['published_at'] = $fields['published_at'] ?: now()->timestamp;
            $fields['is_visible'] = true;
            $post->update($fields);
            $post->seo()->updateOrCreate([], $payload['seo'] ?? []);
            if (array_key_exists('products', $payload)) {
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
            $this->assertArticle($post);
            $historical = PostRevision::query()->whereKey($revision->id)->where('post_id', $post->id)->where('state', 'published')->firstOrFail();
            if ($post->revisions()->where('state', 'draft')->exists()) {
                throw ValidationException::withMessages(['draft' => 'Đã có bản nháp. Hãy xử lý bản nháp đó trước khi khôi phục.']);
            }
            $current = $this->snapshot($post);

            $payload = $historical->payload;
            $payload['slug'] = $post->slug;
            $payload['products'] ??= $current['products'];

            return $post->revisions()->create([
                'version' => $this->nextVersion($post), 'state' => 'draft', 'payload' => $payload,
                'base_hash' => $this->fingerprint($current), 'created_by' => $actor->id, 'updated_by' => $actor->id,
            ]);
        });
    }

    private function snapshot(Post $post): array
    {
        $data = [];
        foreach (self::POST_FIELDS as $field) {
            $data[$field] = $post->getRawOriginal($field);
        }
        $data['is_visible'] = (bool) $post->is_visible;
        $seo = $post->seo()->first();
        $data['seo'] = [
            'title' => $seo?->title, 'description' => $seo?->description,
            'keywords' => $seo?->keywords, 'image' => $seo?->image,
        ];
        $data['products'] = $post->products()->orderBy('post_products.sort_order')->orderBy('post_products.id')
            ->pluck('products.id')->map(fn ($id) => (int) $id)->all();

        return $data;
    }

    private function fingerprint(array $data): string
    {
        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function nextVersion(Post $post): int
    {
        return (int) $post->revisions()->max('version') + 1;
    }

    private function assertArticle(Post $post): void
    {
        abort_unless($post->kind === 'article', 404);
    }

    private function assertDraft(PostRevision $revision): void
    {
        abort_unless($revision->state === 'draft' && $revision->post?->kind === 'article', 404);
    }
}
