<?php

namespace App\Domain\Content;

use App\Domain\Catalog\Health\CatalogHealth;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * What must be right before a product or post goes on the website ("errors" stop it) and what is worth fixing
 * ("warnings" are only shown). Used when a record is switched on, created switched on, or a draft is published.
 */
class PublishChecks
{
    public function __construct(private readonly CatalogHealth $health, private readonly ProductMediaUrls $media) {}

    /** @return array{errors: list<string>, warnings: list<string>} */
    public function product(Product $product): array
    {
        $out = ['errors' => [], 'warnings' => []];
        foreach ($this->health->forProduct((int) $product->getKey()) as $key) {
            [$severity, $label] = CatalogHealth::CHECKS[$key];
            $out[$severity === 'error' ? 'errors' : 'warnings'][] = $label;
        }
        foreach (ProductSpecChecks::missingRequired($product) as $label) {
            $out['warnings'][] = 'Thiếu thông số chính: '.$label;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $post  post columns (title, slug, body, image, summary, category_id, kind) and 'seo' => [...]
     * @return array{errors: list<string>, warnings: list<string>}
     */
    public function post(array $post, ?int $id = null): array
    {
        $errors = [];
        $warnings = [];
        $kind = (string) ($post['kind'] ?? 'article');
        $text = trim(html_entity_decode(strip_tags((string) ($post['body'] ?? '')), ENT_QUOTES, 'UTF-8'));
        if (trim((string) ($post['title'] ?? '')) === '') {
            $errors[] = 'Chưa có tiêu đề';
        }
        if (trim((string) ($post['slug'] ?? '')) === '') {
            $errors[] = 'Chưa có đường dẫn';
        } elseif ($this->slugTaken($kind, (string) $post['slug'], $id)) {
            $errors[] = 'Đường dẫn trùng với một trang khác trên website';
        }
        if ($text === '' && ! preg_match('/<(img|iframe)\b/i', (string) ($post['body'] ?? ''))) {
            $errors[] = 'Chưa có nội dung';
        } elseif ($kind === 'article' && mb_strlen($text) < 300) {
            $warnings[] = 'Nội dung ngắn (dưới 300 ký tự)';
        }
        if (in_array($kind, ['article', 'project', 'solution'], true)) {
            $image = (string) ($post['image'] ?? '');
            if ($image === '') {
                $errors[] = 'Chưa có ảnh đại diện';
            } elseif ($this->media->visual($image, 'upload/news/') === '') {
                $warnings[] = 'Ảnh đại diện không tìm thấy trên máy chủ';
            }
            if (trim((string) ($post['summary'] ?? '')) === '') {
                $warnings[] = 'Chưa có tóm tắt (dùng cho danh sách bài và chia sẻ)';
            }
        }
        $seo = (array) ($post['seo'] ?? []);
        if (trim((string) ($seo['description'] ?? '')) === '' && trim((string) ($post['summary'] ?? '')) === '') {
            $warnings[] = 'Chưa có mô tả SEO';
        }
        if (! empty($post['category_id']) && DB::table('post_categories')->where('id', $post['category_id'])->where('is_visible', false)->exists()) {
            $warnings[] = 'Chuyên mục đang ẩn';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /** @return array<string, mixed> */
    public static function postData(Post $post): array
    {
        return $post->only(['kind', 'title', 'slug', 'body', 'image', 'summary', 'category_id']) + ['seo' => $post->seo?->only(['title', 'description']) ?? []];
    }

    private function slugTaken(string $kind, string $slug, ?int $id): bool
    {
        $top = in_array($kind, ['article', 'project'], true);
        $posts = DB::table('posts')->where('slug', $slug)->when($id, fn ($q) => $q->where('id', '<>', $id));
        if ((clone $posts)->when($top, fn ($q) => $q->whereIn('kind', ['article', 'project']), fn ($q) => $q->where('kind', $kind))->exists()) {
            return true;
        }
        if (! $top) {
            return false;
        }
        // Articles and projects live at the top level, beside products, brands, albums and top categories.
        foreach (['products', 'brands', 'albums'] as $table) {
            if (DB::table($table)->where('slug', $slug)->exists()) {
                return true;
            }
        }

        return DB::table('categories')->whereNull('parent_id')->where('slug', $slug)->exists();
    }
}
