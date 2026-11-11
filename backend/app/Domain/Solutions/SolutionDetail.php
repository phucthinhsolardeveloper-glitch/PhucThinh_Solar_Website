<?php

namespace App\Domain\Solutions;

use App\Domain\Content\PostVisibility;
use App\Domain\Pages\PageSupport;
use App\Models\Post;
use App\Support\RichText;
use App\Support\Text;

/** One solution page: body, gallery, sibling solutions, equipment, CTA. */
class SolutionDetail extends PageSupport
{
    private const FALLBACK_IMAGES = [
        'dien-mat-troi-ho-gia-dinh' => 'assets/images/site-visuals/solution-home-solar-v2.jpg',
        'he-thong-luu-tru-bess' => 'assets/images/site-visuals/solution-bess-v2.jpg',
        'bom-nuoc-nang-luong-mat-troi' => 'assets/images/site-visuals/solution-solar-pump-v2.jpg',
    ];

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
        $post = $this->db->table('posts')->where('slug', $slug)->where('kind', 'solution')->where(PostVisibility::live())->first();
        if (! $post) {
            return [404, ['error' => ['code' => 'SOLUTION_NOT_FOUND']]];
        }
        $id = (int) $post->id;
        $categoryName = $post->category_id ? $this->db->table('post_categories')->where('id', $post->category_id)->where('kind', 'solution')->where('is_visible', true)->value('name') : null;
        $seoRow = $this->db->table('seo_metas')->where('owner_type', Post::class)->where('owner_id', $id)->first(['title', 'description', 'keywords']);
        $gallery = $this->db->table('post_media')->where('post_id', $id)->where('kind', 'image')->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')->pluck('path')
            ->map(fn ($path) => $this->strictImage((string) $path, 'upload/news/'))->filter()->unique()->values()->all();
        $image = $this->strictImage((string) $post->cover_image, 'upload/news/') ?: $this->strictImage((string) $post->image, 'upload/news/');
        if ($image === '' && isset(self::FALLBACK_IMAGES[$slug]) && $this->urls->exists(self::FALLBACK_IMAGES[$slug])) {
            $image = '/'.self::FALLBACK_IMAGES[$slug];
        }

        $navigation = [];
        foreach (SolutionHub::select(SolutionHub::solutions($this->db), true) as $item) {
            $itemSlug = self::text($item['slug']);
            $itemTitle = self::text($item['nav_title']) ?: self::text($item['title']);
            if ($itemSlug === '' || $itemTitle === '') {
                continue;
            }
            $navigation[] = ['title' => $itemTitle, 'slug' => $itemSlug, 'url' => self::canonical($itemSlug),
                'image' => $this->strictImage($item['cover_image'], 'upload/news/') ?: $this->strictImage($item['image'], 'upload/news/'), 'active' => $item['id'] === $id];
        }
        $equipment = [];
        foreach (SolutionHub::select(SolutionHub::productGroups($this->db), true, 4) as $item) {
            $name = self::text($item['name']);
            if ($name === '') {
                continue;
            }
            $listSlug = trim($item['slug'], '/');
            $equipment[] = ['name' => $name, 'description' => self::text($item['summary']), 'url' => '/danh-muc-san-pham/'.($listSlug !== '' ? $listSlug.'/' : ''),
                'image' => $this->strictImage($item['image'], 'upload/product/')];
        }
        $canonical = self::canonical(Text::slug($post->slug));

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => $canonical,
            'seo' => [
                'title' => Text::flat((string) $seoRow?->title),
                'description' => Text::flat((string) $seoRow?->description),
                'keywords' => Text::flat((string) $seoRow?->keywords),
                'image' => $this->urls->visual((string) $post->image, 'upload/news/'),
            ],
            'solution' => [
                'id' => $id,
                'title' => self::text($post->title),
                'slug' => Text::slug($post->slug),
                'url' => $canonical,
                'category' => self::text($categoryName),
                'summary' => self::text($post->summary),
                'image' => $image,
                'content' => RichText::dto(RichText::sanitize((string) $post->body)),
                'extraContent' => RichText::dto(RichText::sanitize((string) $post->extra_body)),
                'gallery' => $gallery,
            ],
            'navigation' => ['allUrl' => '/giai-phap/', 'items' => $navigation],
            'equipment' => $equipment,
            'cta' => ['label' => 'Nhận tư vấn giải pháp', 'href' => '/lien-he/#yeu-cau-tu-van'],
            'campaignCta' => $this->campaignCta($this->section('solutions.cta')),
        ]];
    }

    /** Admin "solutions.cta" block; the hotline comes from the site API on the Nuxt side. */
    private function campaignCta(array $rows): ?array
    {
        $row = SolutionHub::select($rows, false, 1)[0] ?? null;
        if ($rows && ! $row) {
            return null;
        }
        $row ??= ['title' => '', 'subtitle' => '', 'summary' => '', 'image' => ''];
        $button = Text::cms($row['subtitle']) ?: 'Nhận tư vấn';

        return [
            'title' => Text::cms($row['title']) ?: 'Chưa biết giải pháp nào phù hợp?',
            'description' => Text::cms($row['summary']) ?: 'Chia sẻ nhu cầu của bạn. Đội ngũ Phúc Thịnh Solar sẽ hỗ trợ lựa chọn giải pháp phù hợp.',
            'buttonLabel' => $button,
            'image' => $this->strictImage($row['image'], 'upload/news/') ?: '/assets/images/site-visuals/catalog-street-lighting.jpg',
            'links' => ['consult' => ['label' => $button, 'href' => '#ptsContactModal']],
            'hotline' => null,
        ];
    }

    private static function canonical(string $slug): string
    {
        return $slug === '' ? '' : '/giai-phap/'.$slug.'/';
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8')))) : '';
    }
}
