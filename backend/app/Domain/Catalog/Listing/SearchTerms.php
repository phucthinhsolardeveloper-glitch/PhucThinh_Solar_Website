<?php

namespace App\Domain\Catalog\Listing;

/**
 * Word-by-word matching, used when the typed phrase finds nothing: "solax 10kw" matches a product that has
 * both words anywhere in its name. A power or capacity ("550w", "7,5 kW") must match as that exact figure
 * and unit, so 50W never finds 550W and kW never finds kWh.
 */
final class SearchTerms
{
    private const WORD_FIELDS = ['p.name', 'p.code', 'p.slug'];

    private const MAX_TERMS = 8;

    /** @return array{words: list<string>, figures: list<array{0: string, 1: string}>} figures are [number, unit] */
    public static function parse(string $keyword): array
    {
        $text = mb_strtolower(trim($keyword));
        $text = (string) preg_replace('/(?<![\p{L}\p{N}.,])(\d+(?:[.,]\d+)?)\s+(kwh|kwp|kw|wp|w|ah)(?![\p{L}\p{N}])/u', '$1$2', $text);
        $words = [];
        $figures = [];
        foreach (array_slice(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, self::MAX_TERMS) as $term) {
            if (preg_match('/^(\d{1,6}(?:[.,]\d{1,3})?)(kwh|kwp|kw|wp|w|ah)$/', $term, $match)) {
                $figures[] = [str_replace(',', '.', $match[1]), $match[2]];
            } else {
                $words[] = $term;
            }
        }

        return ['words' => $words, 'figures' => $figures];
    }

    /**
     * Every term must match. Returns null when there is nothing word matching could add to the phrase search.
     *
     * @return array{0: string, 1: list<string>}|null
     */
    public static function where(string $keyword): ?array
    {
        ['words' => $words, 'figures' => $figures] = self::parse($keyword);
        if (count($words) + count($figures) < 2) {
            return null;
        }
        $clauses = [];
        $params = [];
        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '\\%_').'%';
            $any = array_map(fn ($field) => $field.' LIKE ?', self::WORD_FIELDS);
            $any[] = 'exists (select 1 from brands b where b.id = p.brand_id and b.name LIKE ?)';
            // "bơm" is the name of the shelf a pump drive sits on, not always part of its own name.
            $any[] = 'exists (select 1 from categories c where c.id = p.category_id and c.name LIKE ?)';
            $clauses[] = '('.implode(' or ', $any).')';
            array_push($params, ...array_fill(0, count($any), $like));
        }
        foreach ($figures as [$number, $unit]) {
            // The product's own rating: in its name or code, or written as "công suất …" / "dung lượng …" in its summary.
            // Other mentions (a lamp "replacing 550W", an inverter's largest panel input) do not count.
            $value = str_replace('.', '[.,]', $number).' ?'.self::unitPattern($unit).'([^a-z]|$)';
            $start = '(^|[^0-9.,])';
            $clauses[] = '(lower(concat_ws(\' \', p.name, p.code)) regexp ? or lower(p.summary) regexp ?)';
            array_push($params, $start.$value, '(công suất|dung lượng)( danh định| định mức| ac| đỉnh)?:? '.$value);
        }

        return ['('.implode(' and ', $clauses).')', $params];
    }

    /** Panels are rated "Wp"; customers type "W". */
    private static function unitPattern(string $unit): string
    {
        return match ($unit) {
            'w', 'wp' => 'wp?',
            'kw', 'kwp' => 'kwp?',
            default => $unit,
        };
    }
}
