<?php

namespace App\Console\Commands;

use App\Domain\Audit\Audit;
use App\Domain\Catalog\SpecFormat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rewrites time, height, area and size specs of every product in one form (App\Domain\Catalog\SpecFormat).
 * Without --apply it only shows what would change. Safe to run again: a second run finds nothing.
 */
class NormalizeSpecsCommand extends Command
{
    protected $signature = 'pts:normalize-specs {--apply : Write the changes (otherwise a dry run)} {--samples=12 : How many examples to print}';

    protected $description = 'Write product specs one way ("4–6 giờ", "3–5 m", "200 m²", "585 × 260 mm", "3.2V/30Ah", "IP66"), fold same-meaning spec names, brand casing in names.';

    public function handle(): int
    {
        $columns = array_merge(['id', 'name', 'additional_specs'], array_keys(SpecFormat::COLUMNS));
        $plan = [];
        $counts = [];
        $brands = DB::table('brands')->pluck('name', 'id');
        foreach (DB::table('products')->orderBy('id')->get([...$columns, 'brand_id']) as $product) {
            $changes = SpecFormat::changes((array) $product + ['brand_name' => (string) ($brands[$product->brand_id] ?? '')]);
            if ($changes !== []) {
                $plan[(int) $product->id] = [$product, $changes];
                foreach (array_keys($changes) as $column) {
                    $counts[$column] = ($counts[$column] ?? 0) + 1;
                }
            }
        }
        $this->info(count($plan).' sản phẩm cần chuẩn hóa.');
        $this->table(['Cột', 'Số sản phẩm'], collect($counts)->map(fn ($n, $c) => [$c, $n])->values()->all());
        $shown = 0;
        foreach ($plan as [$product, $changes]) {
            foreach ($changes as $column => $value) {
                if ($shown++ >= (int) $this->option('samples')) {
                    break 2;
                }
                $before = $column === 'additional_specs' ? '(thông số thêm)' : (string) $product->{$column};
                $after = $column === 'additional_specs' ? collect($value)->pluck('value')->implode(' | ') : $value;
                $this->line(sprintf('#%d %s: %s  →  %s', $product->id, $column, $before, mb_strimwidth((string) $after, 0, 120, '…')));
            }
        }
        if (! $this->option('apply')) {
            $this->comment('Chạy thử, chưa ghi. Thêm --apply để ghi.');

            return self::SUCCESS;
        }
        DB::transaction(function () use ($plan, $counts) {
            foreach ($plan as $id => [, $changes]) {
                if (isset($changes['additional_specs'])) {
                    $changes['additional_specs'] = json_encode($changes['additional_specs'], JSON_UNESCAPED_UNICODE);
                }
                DB::table('products')->where('id', $id)->update($changes);
            }
            Audit::record('catalog.normalize_specs', 'Product', null, null, ['products' => count($plan), 'columns' => $counts], 'system:normalize-specs');
        });
        $this->info('Đã chuẩn hóa '.count($plan).' sản phẩm.');

        return self::SUCCESS;
    }
}
