<?php

namespace App\Domain\Pages;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Legacy\Settings\LegacySettings;
use App\Legacy\Support\LegacyText;

/** Contact page (api/pts_v1_contact.php): intro, picture, company contacts, branches with Google Maps links. */
class ContactAssembler extends PageSupport
{
    public function __construct(ProductMediaUrls $urls, private readonly LegacySettings $settings)
    {
        parent::__construct($urls);
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $text = fn ($v) => LegacyText::flat($v);
        $visualRows = $this->rows("select namevi,descvi,link,photo,status from table_photo where type='contact-visual' order by numb,id desc");
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
        foreach ($this->rows("select namevi,address,id,map_iframe from table_news where type='he-thong-cua-hang' and find_in_set('hienthi',status)"
            ." order by case when namevi like 'Trụ sở%' then 0 when namevi like 'Kho Gia Lai%' then 1 else 2 end,numb,id desc") as $row) {
            $name = $text($row['namevi']);
            $address = $text($row['address']);
            if ((int) $row['id'] > 0 && $name !== '' && $address !== '') {
                $locations[] = ['id' => (int) $row['id'], 'name' => $name, 'address' => $address, 'mapUrl' => self::mapUrl($row['map_iframe'])];
            }
        }
        $options = $this->settings->options();
        $intro = $this->row("select contentvi from table_static where type='lienhe' limit 0,1");
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
                'company' => $text($this->settings->row()['namevi'] ?? ''),
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
