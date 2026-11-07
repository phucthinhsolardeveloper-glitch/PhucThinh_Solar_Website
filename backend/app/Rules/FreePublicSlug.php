<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Products, brands, articles, projects, albums, top product categories and editorial categories
 * share the "/<slug>" address space, so a slug must be free in all of them, not only in its own table.
 */
class FreePublicSlug implements ValidationRule
{
    /** table => extra conditions for rows that answer on "/<slug>" */
    private const TABLES = [
        'products' => [], 'brands' => [], 'albums' => [],
        'categories' => ['parent_id' => null],
        'posts' => ['kind' => ['article', 'project']], 'post_categories' => ['depth' => 1],
    ];

    public function __construct(private readonly string $table, private readonly ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slug = (string) $value;
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $fail('Đường dẫn chỉ gồm chữ thường không dấu, số và dấu gạch ngang.');

            return;
        }
        foreach (self::TABLES as $table => $where) {
            $query = DB::table($table)->where('slug', $slug);
            foreach ($where as $column => $expected) {
                match (true) {
                    $expected === null => $query->whereNull($column),
                    is_array($expected) => $query->whereIn($column, $expected),
                    default => $query->where($column, $expected),
                };
            }
            if ($table === $this->table && $this->ignoreId) {
                $query->where('id', '!=', $this->ignoreId);
            }
            if ($query->exists()) {
                $fail('Đường dẫn này đã được dùng cho một trang khác.');

                return;
            }
        }
    }
}
