<?php

namespace App\Mail;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Site\SocialNetworks;
use App\Support\SiteSettings;
use App\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * What every transactional email shows around its content: the logo, how to reach the company and
 * who sent it. The PHP site carried the same block, and losing it made the new mails look anonymous.
 *
 * Addresses are absolute against the canonical origin because an email is read outside the site, and
 * pictures are referenced by URL — nothing is embedded, so a mail stays small.
 */
final class MailBrand
{
    public function __construct(
        private readonly SiteSettings $settings,
        private readonly ProductMediaUrls $urls,
    ) {}

    /**
     * @return array{name: string, tagline: string, logo: string, home: string, hotline: string,
     *     technicalHotline: string, workingHours: string, address: string, email: string,
     *     social: list<array{label: string, href: string}>}
     */
    public function data(): array
    {
        $options = $this->settings->options();
        $origin = rtrim((string) config('site.canonical_origin'), '/');

        $social = [];
        $hidden = SocialNetworks::hidden($options);
        foreach (SocialNetworks::NETWORKS as $key => [$label]) {
            $href = ! in_array($key, $hidden, true) && is_scalar($options[$key] ?? null) ? trim((string) $options[$key]) : '';
            if (preg_match('~^https?://~i', $href)) {
                $social[] = ['label' => $label, 'href' => $href];
            }
        }

        return [
            'name' => Text::flat($this->settings->siteName()) ?: 'Phúc Thịnh Solar',
            'tagline' => 'Giải pháp năng lượng mặt trời cho gia đình, doanh nghiệp và công trình',
            'logo' => $this->logo($origin),
            'home' => $origin.'/',
            'hotline' => Text::flat($options['hotline'] ?? ''),
            'technicalHotline' => Text::flat($options['hotline_technical'] ?? ''),
            'workingHours' => Text::flat($options['working_hours'] ?? '') ?: 'Thứ Hai – Thứ Bảy, 08:00 – 17:00',
            'address' => Text::flat($options['address'] ?? ''),
            'email' => Text::flat($options['email'] ?? ''),
            'social' => $social,
        ];
    }

    /** The header logo as an absolute URL, or '' when the file is missing so the name is shown instead. */
    private function logo(string $origin): string
    {
        $file = DB::table('banners')->where('placement', 'logo')->orderBy('sort_order')->orderByDesc('id')->value('image');
        $file = is_scalar($file) ? trim((string) $file) : '';
        if ($file === '' || basename($file) !== $file || ! preg_match('/\.(?:png|jpe?g|gif|webp)$/i', $file)) {
            return '';
        }

        return $this->urls->exists('upload/photo/'.$file) ? $origin.'/upload/photo/'.$file : '';
    }
}
