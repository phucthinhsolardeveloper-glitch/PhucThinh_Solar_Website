<?php

namespace App\Domain\Projects;

use App\Domain\Content\PostVisibility;
use App\Domain\Pages\PageSupport;
use App\Models\Post;
use App\Support\RichText;
use App\Support\Text;

/** One completed project: story, gallery, equipment used, nearby projects. */
class ProjectDetail extends PageSupport
{
    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(array $get): array
    {
        $bad = [400, ['error' => ['code' => 'BAD_QUERY']]];
        if (array_diff(array_keys($get), ['slug']) || (isset($get['slug']) && ! is_string($get['slug']))) {
            return $bad;
        }
        $slug = trim($get['slug'] ?? '', '/');
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $bad;
        }
        $post = $this->db->table('posts')->where('slug', $slug)->where('kind', 'project')->where(PostVisibility::live())->first();
        if (! $post) {
            return [404, ['error' => ['code' => 'PROJECT_NOT_FOUND']]];
        }
        $id = (int) $post->id;
        $projectSlug = Text::slug($post->slug);
        $title = Text::cms($post->title);
        $seoRow = $this->db->table('seo_metas')->where('owner_type', Post::class)->where('owner_id', $id)->first(['title', 'description', 'keywords']);
        $categoryRow = $post->category_id ? $this->db->table('post_categories')->where('id', $post->category_id)->where('kind', 'project')->where('is_visible', true)->first(['id', 'name', 'slug']) : null;
        $categoryName = Text::cms((string) $categoryRow?->name);
        $categorySlug = Text::slug((string) $categoryRow?->slug);
        $category = $categoryRow && $categoryName !== '' && $categorySlug !== ''
            ? ['id' => (int) $categoryRow->id, 'name' => $categoryName, 'slug' => $categorySlug, 'url' => '/danh-muc-du-an/'.$categorySlug.'/'] : null;

        $gallery = [];
        foreach ($this->db->table('post_media')->where('post_id', $id)->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')->get() as $item) {
            $caption = Text::cms((string) $item->caption);
            $image = $this->strictImage((string) $item->path, 'upload/news/');
            if ($item->kind !== 'video' && $image !== '') {
                $gallery[] = ['type' => 'image', 'src' => $image, 'caption' => $caption];
            } elseif ($item->kind === 'video' && ($href = self::externalUrl($item->video_url)) !== '') {
                $gallery[] = ['type' => 'video', 'src' => $image, 'href' => $href, 'caption' => $caption];
            }
        }
        $equipment = [];
        $products = $this->db->table('post_products as pp')->join('products as p', 'p.id', '=', 'pp.product_id')
            ->where('pp.post_id', $id)->where('pp.is_visible', true)->where('p.is_visible', true)
            ->orderBy('pp.sort_order')->orderBy('pp.id')->get(['p.id', 'p.name', 'p.slug', 'p.image', 'p.code']);
        foreach ($products as $product) {
            $name = Text::cms($product->name);
            $productSlug = Text::slug($product->slug);
            if ($name !== '' && $productSlug !== '') {
                $equipment[] = ['id' => (int) $product->id, 'name' => $name, 'slug' => $productSlug, 'url' => '/san-pham/'.$productSlug, 'code' => Text::cms((string) $product->code), 'image' => $this->strictImage((string) $product->image, 'upload/product/')];
            }
        }
        $nearby = [];
        $others = $this->db->table('posts')->where('id', '<>', $id)->where('kind', 'project')->where(PostVisibility::live())
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id), fn ($q) => $q->whereNull('category_id'))
            ->orderBy('sort_order')->orderByDesc('id')->limit(3)->get(['id', 'title', 'slug', 'image', 'address', 'province_id', 'ward_id']);
        foreach ($others as $other) {
            $otherSlug = Text::slug($other->slug);
            $otherTitle = Text::cms($other->title);
            if ($otherSlug !== '' && $otherTitle !== '') {
                $nearby[] = ['id' => (int) $other->id, 'title' => $otherTitle, 'slug' => $otherSlug, 'url' => '/'.$otherSlug.'/', 'address' => Text::cms(ProjectLocation::label($other->address, $other->province_id, $other->ward_id)), 'image' => $this->strictImage((string) $other->image, 'upload/news/')];
            }
        }
        $seoTitle = filled($seoRow?->title) ? Text::cms($seoRow->title) : $title;
        if ($seoTitle !== '' && stripos($seoTitle, 'phúc thịnh s') === false) {
            $seoTitle .= ' | Phúc Thịnh Solar';
        }

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'project' => [
                'id' => $id,
                'title' => $title,
                'slug' => $projectSlug,
                'url' => '/'.$projectSlug.'/',
                'summary' => Text::cms((string) $post->summary),
                'coverImage' => $this->strictImage((string) $post->image, 'upload/news/'),
                'category' => $category,
                'client' => Text::cms((string) $post->client_name),
                'address' => Text::cms(ProjectLocation::label($post->address, $post->province_id, $post->ward_id)),
                'startDate' => self::date($post->started_on),
                'endDate' => self::date($post->finished_on),
                'system' => ProjectSystem::payload($post),
                'gallery' => $gallery,
                'relatedEquipment' => $equipment,
                'relatedProjects' => $nearby,
                'cta' => $this->cta(),
            ],
            'content' => RichText::dto(RichText::sanitize((string) $post->body)),
            'seo' => [
                'title' => $seoTitle,
                // Search results and shared links never get an empty snippet: SEO text, else summary, else the story.
                'description' => Text::cms((string) $seoRow?->description) ?: Text::excerpt((string) $post->summary, 160) ?: Text::excerpt((string) $post->body, 160)
                    ?: Text::excerpt($title.(Text::cms(ProjectLocation::label($post->address, $post->province_id, $post->ward_id)) !== '' ? ' tại '.Text::cms(ProjectLocation::label($post->address, $post->province_id, $post->ward_id)) : '').'. Công trình do Phúc Thịnh Solar thực hiện.', 160),
                'keywords' => Text::cms((string) $seoRow?->keywords),
            ],
            'canonical' => ['url' => '/'.$projectSlug],
        ]];
    }

    /** Admin "projects.cta" block; the hotline comes from the site API on the Nuxt side. */
    private function cta(): ?array
    {
        $rows = $this->section('projects.cta');
        $row = collect($rows)->firstWhere('is_visible', true);
        if ($rows && ! $row) {
            return null;
        }
        $row ??= ['title' => '', 'subtitle' => '', 'summary' => '', 'extra' => '', 'image' => ''];
        $image = $this->strictImage($row['image'], 'upload/news/');
        if ($image === '' && $this->urls->exists('upload/news/anh-quan-gl-4-9449.jpg')) {
            $image = '/upload/news/anh-quan-gl-4-9449.jpg';
        }
        $trust = [];
        foreach ($this->section('projects.trust', 3, true) as $item) {
            $label = Text::cms($item['title']);
            if ($label !== '') {
                $trust[] = ['label' => $label, 'icon' => $this->strictImage($item['image'], 'upload/news/'), 'faIcon' => 'fa-check'];
            }
        }

        return [
            'eyebrow' => Text::cms($row['subtitle']) ?: 'Đồng hành cùng năng lượng sạch',
            'title' => Text::cms($row['title']) ?: 'Bạn có công trình cần tư vấn?',
            'description' => Text::cms($row['summary']) ?: 'Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ, tư vấn giải pháp điện mặt trời phù hợp nhất cho nhu cầu của bạn.',
            'buttonLabel' => Text::cms($row['extra']) ?: 'Nhận tư vấn miễn phí',
            'image' => $image,
            'hotline' => '',
            'trust' => $trust ?: [
                ['label' => 'Tư vấn tận tâm', 'icon' => '', 'faIcon' => 'fa-headset'],
                ['label' => 'Giải pháp tối ưu', 'icon' => '', 'faIcon' => 'fa-cogs'],
                ['label' => 'Đồng hành lâu dài', 'icon' => '', 'faIcon' => 'fa-shield-alt'],
            ],
        ];
    }

    private static function externalUrl(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }

        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true) ? $value : '';
    }

    private static function date(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}(?:[  T]\d{2}:\d{2}:\d{2})?$/', $value)) {
            return '';
        }
        $time = strtotime($value);

        return $time === false ? '' : date('Y-m-d', $time);
    }
}
