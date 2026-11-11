<?php

namespace App\Domain\Site;

/**
 * Social and marketplace accounts the footer can link to.
 *
 * The array key is the settings key (`fanpage` and `youtube` keep the names the PHP site used, so
 * the two links already filled in survive). `kind` is what the frontend picks an icon by — never the
 * label or the URL, so renaming a label cannot change an icon. A network with no link is not shown
 * anywhere: the owner adds one by filling the field in Admin → Thông tin website.
 */
final class SocialNetworks
{
    /** key => [label, kind, placeholder] — order is the order shown in Admin and in the footer. */
    public const NETWORKS = [
        'fanpage' => ['Facebook', 'facebook', 'https://www.facebook.com/phucthinhsolar'],
        'youtube' => ['YouTube', 'youtube', 'https://www.youtube.com/@phucthinhsolar'],
        'tiktok' => ['TikTok', 'tiktok', 'https://www.tiktok.com/@phucthinhsolar'],
        'zalo_oa' => ['Zalo OA', 'zalo', 'https://zalo.me/0944555957'],
        'instagram' => ['Instagram', 'instagram', 'https://www.instagram.com/phucthinhsolar'],
        'shopee' => ['Shopee', 'shopee', 'https://shopee.vn/phucthinhsolar'],
        'lazada' => ['Lazada', 'lazada', 'https://www.lazada.vn/shop/phucthinhsolar'],
        'tiki' => ['Tiki', 'tiki', 'https://tiki.vn/cua-hang/phuc-thinh-solar'],
        'threads' => ['Threads', 'threads', 'https://www.threads.net/@phucthinhsolar'],
        'linkedin' => ['LinkedIn', 'linkedin', 'https://www.linkedin.com/company/phucthinhsolar'],
        'x_twitter' => ['X (Twitter)', 'x', 'https://x.com/phucthinhsolar'],
        'telegram' => ['Telegram', 'telegram', 'https://t.me/phucthinhsolar'],
        'whatsapp' => ['WhatsApp', 'whatsapp', 'https://wa.me/84944555957'],
        'pinterest' => ['Pinterest', 'pinterest', 'https://www.pinterest.com/phucthinhsolar'],
    ];

    /** Setting holding the networks switched off: their link stays saved but is not shown anywhere. */
    public const HIDDEN_SETTING = 'social_hidden';

    /** @param array<string, mixed> $options all settings @return list<string> */
    public static function hidden(array $options): array
    {
        return array_values(array_filter((array) ($options[self::HIDDEN_SETTING] ?? []), 'is_string'));
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::NETWORKS);
    }
}
