<?php

namespace App\Domain\Shipping;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Shipping warehouses: branches marked as warehouses, each with its own sender (manager, phone, ward).
 * A product says which warehouse holds it; an order ships from one warehouse, chosen from its products or by staff.
 */
final class Warehouses
{
    /** @return array<int, string> id => name, in the order shown in Admin */
    public function options(): array
    {
        return DB::table('store_locations')->where('is_warehouse', true)->orderBy('sort_order')->orderBy('id')->pluck('name', 'id')->all();
    }

    /**
     * Which warehouses hold these products.
     *
     * @param  iterable<int|string>  $productIds
     * @return array{ids: list<int>, unassigned: int} distinct warehouse ids, and how many products have none
     */
    public function holding(iterable $productIds): array
    {
        $ids = array_values(array_unique(array_map('intval', is_array($productIds) ? $productIds : iterator_to_array($productIds, false))));
        $ids = array_values(array_filter($ids, fn (int $id) => $id > 0));
        if ($ids === []) {
            return ['ids' => [], 'unassigned' => 0];
        }
        $rows = DB::table('products')->whereIn('id', $ids)->pluck('warehouse_id', 'id');
        $warehouses = $rows->filter()->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        return ['ids' => $warehouses, 'unassigned' => $rows->filter(fn ($id) => ! $id)->count()];
    }

    /** The one warehouse that holds everything in the cart; null when it is split across warehouses or any product has no warehouse. */
    public function singleFor(iterable $productIds): ?int
    {
        $held = $this->holding($productIds);

        return count($held['ids']) === 1 && $held['unassigned'] === 0 ? $held['ids'][0] : null;
    }

    /** @return array{ids: list<int>, unassigned: int} for an order's current items */
    public function holdingForOrder(Order $order): array
    {
        return $this->holding($order->items()->pluck('product_id')->filter()->all());
    }

    /**
     * Sender details of a warehouse, or null when it does not exist.
     *
     * @return array{id: int, name: string, label: string, phone: string, address: string, ward_id: int}|null
     */
    public function sender(?int $id): ?array
    {
        $row = $id ? DB::table('store_locations')->where('id', $id)->where('is_warehouse', true)->first() : null;
        if (! $row) {
            return null;
        }

        return ['id' => (int) $row->id, 'label' => (string) $row->name, 'name' => trim((string) $row->sender_name), 'phone' => trim((string) $row->sender_phone),
            'address' => (string) $row->address, 'ward_id' => (int) $row->ward_id];
    }
}
