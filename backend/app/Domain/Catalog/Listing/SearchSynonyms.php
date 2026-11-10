<?php

namespace App\Domain\Catalog\Listing;

use App\Support\SiteSettings;
use App\Support\Text;

/**
 * Words customers use for the same thing ("inverter" / "biến tần"). A keyword that contains one word of a
 * group is also searched with each other word of that group. Groups are edited in Admin → Thông tin website.
 */
final class SearchSynonyms
{
    public const SETTING = 'search_synonyms';

    public const MAX_VARIANTS = 6;

    /** Starting groups for a solar shop; one line per group in Admin. */
    public const DEFAULTS = [
        'inverter, biến tần, inverter hòa lưới',
        'tấm pin, pin mặt trời, pin năng lượng mặt trời, tấm quang năng, solar panel',
        'pin lưu trữ, ắc quy, battery, pin lithium',
        'đèn năng lượng mặt trời, đèn solar, đèn nlmt',
        'đèn đường, street light',
        'đèn pha, đèn chiếu sáng, flood light',
        'bộ điều khiển sạc, điều khiển sạc, charge controller, mppt',
        'máy bơm, bơm nước, pump',
        'hòa lưới, on-grid, ongrid',
        'độc lập, off-grid, offgrid',
    ];

    public function __construct(private readonly SiteSettings $settings) {}

    /** @return list<list<string>> groups as written in Admin, empty lines and one-word groups dropped */
    public function groups(): array
    {
        $saved = $this->settings->get(self::SETTING);

        return self::parse(is_array($saved) ? $saved : self::DEFAULTS);
    }

    /** @param  array<mixed>  $lines @return list<list<string>> */
    public static function parse(array $lines): array
    {
        $groups = [];
        foreach ($lines as $line) {
            $terms = array_values(array_unique(array_filter(array_map(fn ($term) => mb_strtolower(trim($term)), explode(',', is_scalar($line) ? (string) $line : '')),
                fn ($term) => $term !== '' && mb_strlen($term) <= 60)));
            if (count($terms) > 1) {
                $groups[] = $terms;
            }
        }

        return $groups;
    }

    /**
     * Other ways to write the keyword, as [raw text, slug] pairs. Matching is on whole words without accents,
     * so "bien tan 5kw" and "Biến tần 5kW" both become "inverter 5kw".
     *
     * @return list<array{0: string, 1: string}>
     */
    public function variants(string $keyword): array
    {
        $slug = '-'.Text::changeTitle(htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8')).'-';
        $lower = (string) preg_replace('/\s+/u', ' ', mb_strtolower(trim($keyword)));
        $variants = [];
        foreach ($this->groups() as $terms) {
            $slugs = array_map(fn ($term) => Text::changeTitle(htmlspecialchars($term, ENT_QUOTES, 'UTF-8')), $terms);
            foreach ($slugs as $i => $found) {
                if ($found === '' || ! str_contains($slug, '-'.$found.'-')) {
                    continue;
                }
                foreach ($terms as $j => $other) {
                    if ($j === $i || $slugs[$j] === '') {
                        continue;
                    }
                    $otherSlug = trim(str_replace('-'.$found.'-', '-'.$slugs[$j].'-', $slug), '-');
                    // Keep the accents of what was typed where the word appears as written; fall back to the plain form.
                    $raw = $otherSlug === $slugs[$j] ? $other : (str_contains($lower, $terms[$i]) ? str_replace($terms[$i], $other, $lower) : str_replace('-', ' ', $otherSlug));
                    $variants[$otherSlug] = [$raw, $otherSlug];
                }
                break;
            }
        }
        unset($variants[trim($slug, '-')]);

        return array_slice(array_values($variants), 0, self::MAX_VARIANTS);
    }
}
