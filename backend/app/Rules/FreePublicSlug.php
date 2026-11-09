<?php

namespace App\Rules;

use App\Support\Slugs;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** A slug free in the shared "/<slug>" address space (see Slugs::PUBLIC_SPACE). */
class FreePublicSlug implements ValidationRule
{
    public function __construct(private readonly string $table, private readonly ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slug = (string) $value;
        if (! preg_match(Slugs::PATTERN, $slug)) {
            $fail('Đường dẫn chỉ gồm chữ thường không dấu, số và dấu gạch ngang.');
        } elseif (Slugs::takenPublic($slug, $this->table, $this->ignoreId)) {
            $fail('Đường dẫn này đã được dùng cho một trang khác.');
        }
    }
}
