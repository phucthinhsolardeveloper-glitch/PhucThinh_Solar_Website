<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "Hoàn tác" for a change made to many products: remembers what the changed columns held before, and puts them
 * back later. A column someone has changed again in the meantime is left alone.
 */
final class ProductUndo
{
    private const MINUTES = 30;

    /**
     * Runs $change and remembers how to take it back.
     *
     * @param  Collection<int, Product>  $products
     * @param  list<string>  $columns  the columns $change may write
     * @return array{token: ?string, changed: int} token is null when nothing changed
     */
    public function record(Collection $products, array $columns, \Closure $change, int $userId): array
    {
        $ids = $products->map(fn (Product $product) => (int) $product->getKey())->all();
        $before = $this->read($ids, $columns);
        $change();
        $after = $this->read($ids, $columns);

        $diff = [];
        foreach ($after as $id => $row) {
            foreach ($row as $column => $value) {
                if (array_key_exists($id, $before) && $before[$id][$column] !== $value) {
                    $diff[$id][$column] = [$before[$id][$column], $value];
                }
            }
        }
        if ($diff === []) {
            return ['token' => null, 'changed' => 0];
        }
        $token = Str::random(24);
        Cache::put($this->key($userId, $token), $diff, now()->addMinutes(self::MINUTES));

        return ['token' => $token, 'changed' => count($diff)];
    }

    /**
     * Puts the old values back, once.
     *
     * @return array{restored: int, skipped: int}|null null when the step is unknown, used or expired
     */
    public function restore(string $token, int $userId): ?array
    {
        $diff = Cache::pull($this->key($userId, $token));
        if (! is_array($diff)) {
            return null;
        }

        return DB::transaction(function () use ($diff): array {
            $restored = $skipped = 0;
            foreach (Product::query()->whereKey(array_keys($diff))->get() as $product) {
                $columns = $diff[$product->getKey()];
                $values = [];
                foreach ($columns as $column => [$old, $new]) {
                    if (self::plain($product->getRawOriginal($column)) === $new) {
                        $values[$column] = $old;
                    }
                }
                if (count($values) < count($columns)) {
                    $skipped++;
                }
                if ($values !== []) {
                    $product->forceFill($values)->save();
                    $restored++;
                }
            }

            return ['restored' => $restored, 'skipped' => $skipped];
        });
    }

    /**
     * @param  list<int>  $ids
     * @param  list<string>  $columns
     * @return array<int, array<string, string|null>>
     */
    private function read(array $ids, array $columns): array
    {
        return Product::query()->whereKey($ids)->get(['id', ...$columns])->mapWithKeys(fn (Product $product) => [
            (int) $product->getKey() => array_combine($columns, array_map(fn (string $column) => self::plain($product->getRawOriginal($column)), $columns)),
        ])->all();
    }

    private static function plain(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function key(int $userId, string $token): string
    {
        return 'pts-product-undo:'.$userId.':'.$token;
    }
}
