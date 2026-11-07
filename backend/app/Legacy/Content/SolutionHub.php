<?php

namespace App\Legacy\Content;

use App\Legacy\Support\LegacyText;

/** Selection rules shared by the solution and project pages (libraries/solution_hub.php). */
final class SolutionHub
{
    public static function has(array $row, string $flag): bool
    {
        return in_array($flag, explode(',', (string) ($row['status'] ?? '')), true);
    }

    /**
     * Published rows; when any row carries $flag, only flagged ones. A hidden flagged row still counts
     * as a selection, so hiding the last chosen item never brings back unrelated content.
     *
     * @return list<array<string, mixed>>
     */
    public static function select(array $rows, string $flag = '', int $limit = 0): array
    {
        $hasSelection = $flag !== '' && array_any($rows, fn ($row) => self::has($row, $flag));
        $selected = [];
        foreach ($rows as $row) {
            if (! self::has($row, 'hienthi') || ($hasSelection && ! self::has($row, $flag))) {
                continue;
            }
            $selected[] = $row;
            if ($limit > 0 && count($selected) >= $limit) {
                break;
            }
        }

        return $selected;
    }

    public static function field(array $row, string $field): string
    {
        return isset($row[$field.'vi']) ? LegacyText::cms($row[$field.'vi']) : '';
    }

    public static function navName(array $row): string
    {
        return self::field($row, 'title_index') ?: self::field($row, 'name');
    }

    public static function publicPath(array $row): string
    {
        $slug = self::field($row, 'slug');

        return $slug !== '' ? 'giai-phap/'.$slug.'/' : 'giai-phap/';
    }
}
