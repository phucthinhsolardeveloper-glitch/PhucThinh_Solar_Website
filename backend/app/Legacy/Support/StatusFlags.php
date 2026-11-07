<?php

namespace App\Legacy\Support;

/**
 * The PHP-era status column: a comma list such as "hienthi,noibat", tested with find_in_set().
 */
final class StatusFlags
{
    /** @var list<string> */
    private array $flags;

    public function __construct(?string $value)
    {
        $this->flags = array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn ($flag) => $flag !== ''));
    }

    public function has(string $flag): bool
    {
        return in_array($flag, $this->flags, true);
    }

    public function visible(): bool
    {
        return $this->has('hienthi');
    }

    /** @return list<string> */
    public function all(): array
    {
        return $this->flags;
    }
}
