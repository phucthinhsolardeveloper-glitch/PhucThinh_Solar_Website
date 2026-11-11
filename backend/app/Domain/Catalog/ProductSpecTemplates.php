<?php

namespace App\Domain\Catalog;

use App\Domain\Catalog\ProductDetail\ProductRecord;
use App\Domain\Catalog\ProductDetail\ProductSpecs;
use App\Models\Product;

/**
 * The spec fields of each product type for the Admin form (config/pdp.php + config/product_specs.php), how their
 * values are stored, and the conversions between what staff type and what the product page reads.
 */
final class ProductSpecTemplates
{
    /** Spec keys stored in their own products column (key => column). */
    public static function columns(): array
    {
        return ['cong_suat' => 'power_watts'] + array_combine(ProductRecord::SPECS, ProductRecord::SPECS);
    }

    /** @return array<string, string> type key => label, in config order */
    public static function types(): array
    {
        return collect(config('pdp.profiles'))->map(fn ($p, $key) => (string) ($p['label'] ?? $key))->all();
    }

    /**
     * @return list<array{key: string, label: string, column: ?string, unit: string, placeholder: string, suggest: list<string>, required: bool}>
     */
    public static function fields(string $type): array
    {
        $profile = config('pdp.profiles.'.$type) ?? config('pdp.profiles.general');
        $labels = array_diff_key((array) ($profile['fields'] ?? []), ['package_contents' => true]);
        foreach ((array) config('product_specs.extra_fields.'.$type, []) as $key => $extra) {
            $position = array_search($extra['after'] ?? '', array_keys($labels), true);
            $labels = $position === false ? $labels + [$key => $extra['label']]
                : array_slice($labels, 0, $position + 1, true) + [$key => $extra['label']] + array_slice($labels, $position + 1, null, true);
        }
        $required = (array) config('product_specs.required.'.$type, []);
        $columns = self::columns();
        $out = [];
        foreach ($labels as $key => $label) {
            $meta = (array) config('product_specs.fields.'.$key, []);
            $out[] = [
                'key' => (string) $key, 'label' => (string) $label, 'column' => $columns[$key] ?? null,
                'unit' => (string) ($meta['unit'] ?? ''), 'placeholder' => (string) ($meta['placeholder'] ?? ''),
                'suggest' => array_values((array) ($meta['suggest'] ?? [])), 'required' => in_array($key, $required, true),
            ];
        }

        return $out;
    }

    /** @return array<string, string> extra (non-column) keys of a type => label */
    public static function extraKeys(string $type): array
    {
        return collect(self::fields($type))->whereNull('column')->pluck('label', 'key')->all();
    }

    /** "5 kW" -> "5" for a box that already shows kW; anything else is shown as typed. */
    public static function forBox(string $unit, mixed $value): ?string
    {
        $value = $value === null ? null : trim((string) $value);
        if ($value === null || $value === '' || $unit === '') {
            return $value === '' ? null : $value;
        }
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*'.preg_quote($unit, '/').'$/iu', $value, $match)) {
            return $match[1];
        }

        return $value;
    }

    /** A bare number typed in a box with a unit is stored as "<number> <unit>". */
    public static function fromBox(string $unit, mixed $value): ?string
    {
        $value = $value === null ? '' : trim((string) $value);
        if ($value === '') {
            return null;
        }

        return $unit !== '' && preg_match('/^\d+(?:[.,]\d+)?$/', $value) ? $value.' '.$unit : $value;
    }

    /** @return list<string> lower-case labels that mean this key */
    public static function aliases(string $key): array
    {
        $labels = [];
        foreach (config('pdp.profiles') as $profile) {
            if (isset($profile['fields'][$key])) {
                $labels[] = mb_strtolower((string) $profile['fields'][$key]);
            }
        }
        foreach ((array) config('product_specs.extra_fields') as $extras) {
            if (isset($extras[$key])) {
                $labels[] = mb_strtolower((string) $extras[$key]['label']);
            }
        }

        return array_values(array_unique([...$labels, ...(array) (config('pdp.spec_aliases.'.$key) ?? []), ...(array) config('product_specs.aliases.'.$key, [])]));
    }

    public static function typeOf(Product $product): string
    {
        return app(ProductSpecs::class)->profileForCategory($product->pdp_profile, (int) $product->category_id);
    }

    /** @return list<string> labels of the type's required specs that the product does not show */
    public static function missingRequired(Product $product): array
    {
        $type = self::typeOf($product);
        $row = $product->getAttributes();
        $row['additional_specs'] = json_encode($product->additional_specs ?? [], JSON_UNESCAPED_UNICODE);
        $specs = app(ProductSpecs::class);
        $missing = [];
        /** @var array<int, array<string, mixed>> $rows */
        $rows = (array) $product->getAttribute('additional_specs');
        $typedLabels = collect($rows)->map(fn ($spec) => (array) $spec)
            ->filter(fn (array $spec) => trim((string) ($spec['value'] ?? '')) !== '')
            ->map(fn (array $spec) => mb_strtolower(trim((string) ($spec['label'] ?? ''))))->all();
        foreach (self::fields($type) as $field) {
            if ($field['required'] && $specs->value($row, $field['key']) === ''
                && array_intersect($typedLabels, self::aliases($field['key'])) === []) {
                $missing[] = $field['label'];
            }
        }

        return $missing;
    }
}
