<?php

namespace App\Domain\Pages;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Legacy\Content\RichText;
use App\Legacy\Support\LegacyText;
use App\Support\SiteSettings;

/** Dealer page (api/pts_v1_dealer.php): pitch, products, benefits, testimonials, figures, sign-up form. */
class DealerAssembler extends PageSupport
{
    public function __construct(ProductMediaUrls $urls, private readonly SiteSettings $settings)
    {
        parent::__construct($urls);
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $text = fn ($v) => LegacyText::cms($v);
        $rich = fn ($v) => RichText::dto(RichText::sanitize($v));
        $static = [];
        foreach ($this->rows("select case section when 'dealer.intro' then 'dai-ly-1' when 'dealer.overview' then 'dai-ly-2' else 'dai-ly-3' end as type, coalesce(title,'') as namevi, coalesce(summary,'') as descvi, if(is_visible,'hienthi','') as status from page_items where section in ('dealer.intro','dealer.overview','dealer.support') order by id desc") as $row) {
            if (self::visible($row)) {
                $static[$row['type']] = $row;
            }
        }
        $heroRows = $this->rows("select coalesce(title,'') as namevi, coalesce(image,'') as photo, if(is_visible,'hienthi','') as status from banners where placement='dealer-hero' order by sort_order,id desc");
        $hero = null;
        foreach ($heroRows as $row) {
            if (self::visible($row) && ($image = $this->strictImage($row['photo'], 'upload/photo/')) !== '') {
                $hero = ['title' => $text($row['namevi']) ?: 'Năng lực phân phối thiết bị năng lượng mặt trời', 'image' => $image];
                break;
            }
        }
        if (! $hero && ! $heroRows && $this->urls->exists('assets/images/site-visuals/catalog-equipment-system.jpg')) {
            $hero = ['title' => 'Thiết bị năng lượng mặt trời phân phối chính hãng', 'image' => '/assets/images/site-visuals/catalog-equipment-system.jpg'];
        }
        $products = [];
        foreach ($this->rows("select coalesce(title,'') as namevi, coalesce(link,'') as link, coalesce(image,'') as photo, if(is_visible,'hienthi','') as status from banners where placement='hinh-anh-dai-ly' order by sort_order,id desc") as $row) {
            if (! self::visible($row)) {
                continue;
            }
            $title = $text($row['namevi']);
            $image = $this->strictImage($row['photo'], 'upload/photo/');
            $link = self::link($row['link']);
            if ($title !== '' && ($image !== '' || $link !== '')) {
                $products[] = ['title' => $title, 'image' => $image, 'href' => $link];
            }
        }
        $item = function (array $row) use ($text, $rich) {
            $title = $text($row['namevi']);

            return self::visible($row) && $title !== ''
                ? ['title' => $title, 'description' => $text($row['descvi']), 'descriptionContent' => $rich($row['descvi']), 'image' => $this->strictImage($row['photo'], 'upload/news/')]
                : null;
        };
        $support = array_values(array_filter(array_map($item, $this->rows("select coalesce(title,'') as namevi, coalesce(summary,'') as descvi, coalesce(image,'') as photo, if(is_visible,'hienthi','') as status from page_items where section='dealer.benefits' order by sort_order,id desc limit 0,4"))));
        $testimonials = [];
        foreach ($this->rows("select coalesce(title,'') as namevi, coalesce(summary,'') as descvi, coalesce(body,'') as contentvi, coalesce(image,'') as photo, if(is_visible,'hienthi','') as status from page_items where section='dealer.testimonials' order by sort_order,id desc limit 0,3") as $row) {
            if ($entry = $item($row)) {
                $testimonials[] = $entry + ['quote' => $text($row['contentvi'])];
            }
        }
        $stats = [];
        foreach ($this->rows("select coalesce(title,'') as namevi, coalesce(figure,'') as solieu, 'hienthi' as status from page_items where section='dealer.stats' and is_visible=1 order by sort_order,id desc limit 0,3") as $row) {
            $label = $text($row['namevi']);
            $value = $text($row['solieu']);
            if (self::visible($row) && $label !== '' && preg_match('/^[0-9][0-9.,]*$/', $value)) {
                $stats[] = ['label' => $label, 'value' => $value];
            }
        }
        $hotline = is_scalar($this->settings->options()['hotline'] ?? null) ? trim((string) $this->settings->options()['hotline']) : '';
        $section = fn (string $key) => ['title' => $text($static[$key]['namevi'] ?? ''), 'description' => $text($static[$key]['descvi'] ?? ''), 'descriptionContent' => $rich($static[$key]['descvi'] ?? '')];

        return [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/dai-ly/',
            'title' => $text($static['dai-ly-1']['namevi'] ?? '') ?: 'Đại lý Phúc Thịnh Solar',
            'seo' => $this->pageSeo('dai-ly', $text),
            'hero' => $hero,
            'intro' => $rich($static['dai-ly-1']['descvi'] ?? ''),
            'overview' => $section('dai-ly-2') + ['products' => $products],
            'support' => $section('dai-ly-3') + ['items' => $support],
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
