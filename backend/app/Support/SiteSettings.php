<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Site-wide settings (table "settings", key => JSON value), read once per request. */
class SiteSettings
{
    /** @var array<string, mixed>|null */
    private ?array $values = null;

    /** @return array<string, mixed> */
    public function options(): array
    {
        if ($this->values === null) {
            $this->values = [];
            foreach (DB::table('settings')->get(['key', 'value']) as $row) {
                $this->values[$row->key] = json_decode((string) $row->value, true);
            }
        }

        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->options()[$key] ?? $default;
    }

    public function siteName(): string
    {
        return (string) $this->get('site_name', '');
    }

    /** Public prices are shown only when the "public_product_prices" switch is exactly "1". */
    public function productPricesVisible(): bool
    {
        return (string) $this->get('public_product_prices', '') === '1';
    }
}
