<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Changes applied to many selected products. Goes through the model, so each change is audited. */
final class ProductBulkUpdate
{
    /**
     * @param  Collection<int, Product>  $products
     * @param  array<string, mixed>  $values
     * @return int products actually changed
     */
    public function set(Collection $products, array $values): int
    {
        return DB::transaction(function () use ($products, $values): int {
            $changed = 0;
            foreach ($products as $product) {
                $product->fill($values);
                if ($product->isDirty()) {
                    $product->save();
                    $changed++;
                }
            }

            return $changed;
        });
    }

    /**
     * Raises or lowers list and sale prices by a percentage, rounded to the nearest $roundTo đồng.
     *
     * @param  Collection<int, Product>  $products
     * @return array{changed: int, skipped: int} skipped = products without a price
     */
    public function adjustPrices(Collection $products, float $percent, int $roundTo = 1000): array
    {
        return $this->eachPriced($products, function (Product $product, float $regular, float $sale) use ($percent, $roundTo): void {
            $product->regular_price = self::round($regular * (1 + $percent / 100), $roundTo);
            if ($sale > 0) {
                $product->sale_price = self::round($sale * (1 + $percent / 100), $roundTo);
            }
        });
    }

    /**
     * Sale price = list price minus $percent.
     *
     * @param  Collection<int, Product>  $products
     * @return array{changed: int, skipped: int}
     */
    public function setDiscount(Collection $products, float $percent, int $roundTo = 1000): array
    {
        return $this->eachPriced($products, function (Product $product, float $regular) use ($percent, $roundTo): void {
            $product->sale_price = self::round($regular * (1 - $percent / 100), $roundTo);
        });
    }

    /**
     * Deletes products nothing depends on; those with reviews or orders are kept and counted.
     *
     * @param  Collection<int, Product>  $products
     * @return array{deleted: int, kept: int}
     */
    public function delete(Collection $products): array
    {
        return DB::transaction(function () use ($products): array {
            $deleted = 0;
            foreach ($products as $product) {
                if (! $product->reviews()->exists() && ! DB::table('order_items')->where('product_id', $product->id)->exists()) {
                    $product->delete();
                    $deleted++;
                }
            }

            return ['deleted' => $deleted, 'kept' => $products->count() - $deleted];
        });
    }

    /** @return array{changed: int, skipped: int} */
    private function eachPriced(Collection $products, \Closure $change): array
    {
        return DB::transaction(function () use ($products, $change): array {
            $changed = $skipped = 0;
            foreach ($products as $product) {
                $regular = (float) $product->regular_price;
                if ($regular <= 0) {
                    $skipped++;

                    continue;
                }
                $change($product, $regular, (float) $product->sale_price);
                if ($product->isDirty()) {
                    $product->save();
                    $changed++;
                }
            }

            return ['changed' => $changed, 'skipped' => $skipped];
        });
    }

    private static function round(float $value, int $roundTo): int
    {
        return (int) max(0, round($value / max(1, $roundTo)) * max(1, $roundTo));
    }
}
