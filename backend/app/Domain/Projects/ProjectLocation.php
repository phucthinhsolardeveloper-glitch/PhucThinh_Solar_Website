<?php

namespace App\Domain\Projects;

use Illuminate\Support\Facades\DB;

/** "Street, Ward, Province" of a project from its detail text and the administrative list. */
final class ProjectLocation
{
    public static function label(mixed $detail, mixed $provinceId, mixed $wardId): string
    {
        $provinces = once(fn () => DB::table('provinces')->pluck('name', 'id')->all());
        $ward = (int) $wardId > 0 ? (string) DB::table('wards')->where('id', (int) $wardId)->value('name') : '';
        $parts = [trim((string) $detail), $ward, (string) ($provinces[(int) $provinceId] ?? '')];

        return implode(', ', array_values(array_filter($parts, fn ($p) => $p !== '')));
    }
}
