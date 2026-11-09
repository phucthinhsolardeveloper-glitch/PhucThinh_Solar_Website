<?php

namespace App\Domain\Analytics;

use Carbon\CarbonImmutable;

/** Time buckets shared by the dashboard charts: days, weeks or months ending today. */
final class Periods
{
    public const NAVY = '#16365f';

    public const GOLD = '#e0a526';

    public const PALETTE = ['#16365f', '#e0a526', '#2f7d6d', '#9b4d8a', '#c2562f', '#5b7fb8', '#8a8f98'];

    /** @var array<string, array{0: string, 1: int}> filter => [unit, count] */
    public const RANGES = ['7d' => ['day', 7], '30d' => ['day', 30], '90d' => ['day', 90], '12w' => ['week', 12], '12m' => ['month', 12]];

    /** @return array{unit: string, from: CarbonImmutable, keys: list<string>, labels: list<string>, sql: string} */
    public static function make(string $filter, string $column = 'created_at'): array
    {
        [$unit, $count] = self::RANGES[$filter] ?? self::RANGES['30d'];
        $today = CarbonImmutable::today();
        $from = match ($unit) {
            'week' => $today->startOfWeek()->subWeeks($count - 1),
            'month' => $today->startOfMonth()->subMonths($count - 1),
            default => $today->subDays($count - 1),
        };
        $keys = $labels = [];
        for ($at = $from; $at <= $today; $at = $at->add(1, $unit)) {
            $keys[] = self::key($at, $unit);
            $labels[] = match ($unit) {
                'week' => 'Tuần '.$at->format('d/m'), 'month' => 'T'.$at->format('n/Y'), default => $at->format('d/m')
            };
        }
        $sql = match ($unit) {
            'week' => "date_format(date_sub(date($column), interval weekday($column) day), '%Y-%m-%d')",
            'month' => "date_format($column, '%Y-%m')",
            default => "date_format($column, '%Y-%m-%d')",
        };

        return ['unit' => $unit, 'from' => $from, 'keys' => $keys, 'labels' => $labels, 'sql' => $sql];
    }

    /** The same length of time just before, for "so với kỳ trước". */
    public static function previousFrom(array $period): CarbonImmutable
    {
        return $period['from']->sub(count($period['keys']), $period['unit']);
    }

    /** @param array<string, int|float> $byKey @return list<int|float> */
    public static function fill(array $period, array $byKey): array
    {
        return array_map(fn ($key) => $byKey[$key] ?? 0, $period['keys']);
    }

    public static function change(float $now, float $before): string
    {
        if ($before <= 0) {
            return $now > 0 ? 'kỳ trước chưa có' : '';
        }
        $pct = (int) round(($now - $before) / $before * 100);

        return ($pct >= 0 ? '+' : '').$pct.'% so với kỳ trước';
    }

    private static function key(CarbonImmutable $at, string $unit): string
    {
        return $unit === 'month' ? $at->format('Y-m') : $at->format('Y-m-d');
    }
}
