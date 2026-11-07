<?php

namespace App\Legacy\Settings;

use Illuminate\Support\Facades\DB;

/**
 * Site switches stored as JSON in table_setting.options (first row), read the way the PHP site does.
 */
class LegacySettings
{
    /** @var array<string, mixed>|null */
    private ?array $options = null;

    /** @var array<string, mixed>|null */
    private ?array $row = null;

    /** The table_setting row ($setting in the PHP site): name, address, email... */
    public function row(): array
    {
        return $this->row ??= (array) (DB::connection('legacy')->table('table_setting')->orderBy('id')->first() ?? []);
    }

    /** @return array<string, mixed> */
    public function options(): array
    {
        if ($this->options === null) {
            $decoded = json_decode((string) ($this->row()['options'] ?? ''), true);
            $this->options = is_array($decoded) ? $decoded : [];
        }

        return $this->options;
    }

    /** publicProductPricesVisible(): the "public_product_prices" switch, off unless it is exactly "1". */
    public function productPricesVisible(): bool
    {
        return (string) ($this->options()['public_product_prices'] ?? '') === '1';
    }
}
