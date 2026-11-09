<?php

namespace App\Domain\Pages;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Support\RichText;
use App\Support\SiteSettings;
use App\Support\Text;

/** Dealer page: pitch, products, benefits, testimonials, figures, sign-up form. */
class DealerAssembler extends PageSupport
{
    public function __construct(ProductMediaUrls $urls, private readonly SiteSettings $settings)
    {
        parent::__construct($urls);
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $text = fn ($v) => Text::cms($v);
        $rich = fn ($v) => RichText::dto(RichText::sanitize($v));
        // Each fixed section shows its first visible item.
        $static = [];
        foreach (['dealer.intro', 'dealer.overview', 'dealer.support'] as $key) {
            $static[$key] = $this->db->table('page_items')->where('section', $key)->where('is_visible', true)->orderBy('id')->first(['title', 'summary']);
        }
        $heroRows = $this->banners('dealer-hero');
        $hero = null;
        foreach ($heroRows as $row) {
            if ($row['is_visible'] && ($image = $this->strictImage($row['image'], 'upload/photo/')) !== '') {
                $hero = ['title' => $text($row['title']) ?: 'Năng lực phân phối thiết bị năng lượng mặt trời', 'image' => $image];
                break;
            }
        }
        if (! $hero && ! $heroRows && $this->urls->exists('assets/images/site-visuals/catalog-equipment-system.jpg')) {
            $hero = ['title' => 'Thiết bị năng lượng mặt trời phân phối chính hãng', 'image' => '/assets/images/site-visuals/catalog-equipment-system.jpg'];
        }
        $products = [];
        foreach ($this->banners('hinh-anh-dai-ly', true) as $row) {
            $title = $text($row['title']);
            $image = $this->strictImage($row['image'], 'upload/photo/');
            $link = self::link($row['link']);
            if ($title !== '' && ($image !== '' || $link !== '')) {
                $products[] = ['title' => $title, 'image' => $image, 'href' => $link];
            }
        }
        $card = function (array $row) use ($text, $rich) {
            $title = $text($row['title']);

            return $row['is_visible'] && $title !== ''
                ? ['title' => $title, 'description' => $text($row['summary']), 'descriptionContent' => $rich($row['summary']), 'image' => $this->strictImage($row['image'], 'upload/news/')]
                : null;
        };
        $support = array_values(array_filter(array_map($card, $this->section('dealer.benefits', 4))));
        $testimonials = [];
        foreach ($this->section('dealer.testimonials', 3) as $row) {
            if ($entry = $card($row)) {
                $testimonials[] = $entry + ['quote' => $text($row['body'])];
            }
        }
        $stats = [];
        foreach ($this->section('dealer.stats', 3, true) as $row) {
            $label = $text($row['title']);
            $value = $text($row['figure']);
            if ($label !== '' && preg_match('/^[0-9][0-9.,]*$/', $value)) {
                $stats[] = ['label' => $label, 'value' => $value];
            }
        }
        $hotline = is_scalar($this->settings->options()['hotline'] ?? null) ? trim((string) $this->settings->options()['hotline']) : '';
        $intro = $static['dealer.intro'];
        $part = fn (string $key) => ['title' => $text((string) $static[$key]?->title), 'description' => $text((string) $static[$key]?->summary), 'descriptionContent' => $rich((string) $static[$key]?->summary)];

        return [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/dai-ly/',
            'title' => $text((string) $intro?->title) ?: 'Đại lý Phúc Thịnh Solar',
            'seo' => $this->pageSeo('dai-ly', $text),
            'hero' => $hero,
            'intro' => $rich((string) $intro?->summary),
            'overview' => $part('dealer.overview') + ['products' => $products],
            'support' => $part('dealer.support') + ['items' => $support],
            'testimonials' => $testimonials,
            'stats' => $stats,
            'form' => ['action' => '/dai-ly/#dang-ky-dai-ly', 'method' => 'POST', 'title' => 'Đăng ký làm đối tác phân phối', 'note' => 'Để lại thông tin, Phúc Thịnh Solar sẽ liên hệ tư vấn chính sách đại lý phù hợp.'],
            'hotline' => $hotline === '' ? null : ['text' => $hotline, 'tel' => preg_replace('/[^0-9+]/', '', $hotline)],
            'labels' => ['overview' => 'Sản phẩm phân phối', 'support' => 'Quyền lợi đại lý', 'testimonials' => 'Phản hồi từ khách hàng và đối tác', 'stats' => 'Tự hào Phúc Thịnh Solar', 'empty' => 'Nội dung đang được cập nhật.'],
        ];
    }

    private static function link(mixed $value): string
    {
        $link = is_scalar($value) ? trim((string) $value) : '';
        if ($link === '' || preg_match('/[\x00-\x20<>"\'`]/', $link) || preg_match('/^(?:javascript|data|vbscript):/i', $link)) {
            return '';
        }

        return $link[0] === '/' || preg_match('/^https?:\/\//i', $link) ? $link : '';
    }
}
