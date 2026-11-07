<?php

namespace App\Domain\Pages;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Support\SiteSettings;
use App\Support\Text;

/** Contact page (api/pts_v1_contact.php): intro, picture, company contacts, branches with Google Maps links. */
class ContactAssembler extends PageSupport
{
    public function __construct(ProductMediaUrls $urls, private readonly SiteSettings $settings)
    {
        parent::__construct($urls);
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $text = fn ($v) => Text::flat($v);
        $visualRows = $this->rows("select coalesce(title,'') as namevi, coalesce(summary,'') as descvi, coalesce(link,'') as link, coalesce(image,'') as photo, if(is_visible,'hienthi','') as status from banners where placement='contact-visual' order by sort_order,id desc");
        $visual = null;
        foreach ($visualRows as $row) {
            if (empty($row['photo']) || ! str_contains(','.$row['status'].',', ',hienthi,') || ($image = $this->looseImage($row['photo'], 'upload/photo/')) === '') {
                continue;
            }
            $visual = ['name' => $text($row['namevi']) ?: 'Tư vấn đúng thiết bị, đúng nhu cầu sử dụng', 'description' => $text($row['descvi']), 'href' => self::href($row['link'], '#yeu-cau-tu-van'), 'image' => $image];
            break;
        }
        if (! $visualRows && ($fallback = $this->looseImage('contact-consultation.jpg', 'assets/images/site-visuals/')) !== '') {
            $visual = ['name' => 'Tư vấn đúng thiết bị, đúng nhu cầu sử dụng', 'description' => 'Trao đổi cùng đội ngũ Phúc Thịnh Solar để chọn cấu hình phù hợp cho gia đình, cửa hàng hoặc công trình.', 'href' => '#yeu-cau-tu-van', 'image' => $fallback];
        }
        $locations = [];
        foreach ($this->rows("select name as namevi, address, id, coalesce(map_embed,'') as map_iframe from store_locations where is_visible=1"
            ." order by case when name like 'Trụ sở%' then 0 when name like 'Kho Gia Lai%' then 1 else 2 end,sort_order,id desc") as $row) {
            $name = $text($row['namevi']);
            $address = $text($row['address']);
            if ((int) $row['id'] > 0 && $name !== '' && $address !== '') {
                $locations[] = ['id' => (int) $row['id'], 'name' => $name, 'address' => $address, 'mapUrl' => self::mapUrl($row['map_iframe'])];
            }
        }
        $options = $this->settings->options();
        $intro = $this->row("select body as contentvi from page_items where section='contact.intro' limit 0,1");
        $seo = $this->pageSeo('lien-he', $text);

        return [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/lien-he/',
            'seo' => $seo,
            'title' => 'Liên hệ Phúc Thịnh Solar',
            'intro' => $text($intro['contentvi'] ?? ''),
            'visual' => $visual,
            'contact' => [
                'company' => $text($this->settings->siteName()),
                'hotline' => $text($options['hotline'] ?? ''),
                'email' => $text($options['email'] ?? ''),
                'address' => $text($options['address'] ?? ''),
                'workingHours' => $text($options['thoigianlamviec'] ?? ''),
            ],
            'locations' => $locations,
            'labels' => [
                'locations' => 'Hệ thống chi nhánh',
                'formCta' => 'Đăng ký nhận tư vấn',
                'formNote' => 'Thông tin được gửi qua biểu mẫu bảo mật và lưu vào hệ thống để đội ngũ Phúc Thịnh Solar liên hệ lại.',
            ],
        ];
    }

    private static function href(mixed $value, string $fallback): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || preg_match('~[\x00-\x20<>"\']|\.\.~', $value)) {
            return $fallback;
        }

        return str_starts_with($value, '#') || str_starts_with($value, '/') ? $value : $fallback;
    }

    /** Only the src of a Google Maps embed, https and Google hosts only; never markup (ptsContactMapUrl). */
    public static function mapUrl(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '') {
            return '';
        }
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('~<iframe\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1[^>]*>~i', $value, $match)) {
            $value = html_entity_decode(trim($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (preg_match('/[\x00-\x20\x7f<>]/', $value)) {
            return '';
        }
        $parts = parse_url($value);
        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return '';
        }
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        $path = $parts['path'] ?? '/';
        if (! in_array($host, ['www.google.com', 'google.com', 'maps.google.com', 'www.google.com.vn', 'google.com.vn'], true) || ! preg_match('~^/maps(?:/|$)~i', $path)) {
            return '';
        }

        return 'https://'.$host.$path.(isset($parts['query']) ? '?'.$parts['query'] : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
