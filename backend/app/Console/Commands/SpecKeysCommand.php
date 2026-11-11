<?php

namespace App\Console\Commands;

use App\Domain\Audit\Audit;
use App\Domain\Catalog\ProductDetail\ProductSpecs;
use App\Domain\Catalog\ProductSpecTemplates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gives the hand-typed "Thông số thêm" rows the key of the spec they mean, so the typed product form shows them in
 * their box: exact label matches only (config/pdp.php spec_aliases + config/product_specs.php aliases), one row per
 * key, label set to the form's label. Inverters whose specs are one "Thông tin" sentence get power, phases, MPPT
 * and warranty read out of it; the sentence itself stays. Without --apply nothing is written.
 */
class SpecKeysCommand extends Command
{
    protected $signature = 'pts:spec-keys {--apply : Write the changes (otherwise a dry run)} {--report= : Also write the report as Markdown to this path}';

    protected $description = 'Key the hand-typed product spec rows for the typed product form (dry run unless --apply).';

    public function handle(ProductSpecs $specs): int
    {
        $plan = [];
        $byType = [];
        $unmatched = [];
        foreach (DB::table('products')->orderBy('id')->get(['id', 'name', 'pdp_profile', 'category_id', 'additional_specs', ...array_values(array_diff(ProductSpecTemplates::columns(), ['power_watts']))]) as $product) {
            $type = $specs->profileForCategory((string) $product->pdp_profile, (int) $product->category_id);
            $keys = ProductSpecTemplates::extraKeys($type);
            $columnKeys = collect(ProductSpecTemplates::fields($type))->whereNotNull('column')->where('column', '!=', 'power_watts')->pluck('column', 'key')->all();
            $columnValues = [];
            $rows = json_decode((string) $product->additional_specs, true);
            if (! is_array($rows)) {
                continue;
            }
            $taken = array_flip(array_filter(array_map(fn ($row) => (string) ($row['key'] ?? ''), $rows)));
            $changes = [];
            foreach ($rows as $i => $row) {
                if (! empty($row['key'])) {
                    continue;
                }
                $label = mb_strtolower(trim((string) ($row['label'] ?? '')));
                $match = null;
                foreach ($keys as $key => $formLabel) {
                    if (! isset($taken[$key]) && in_array($label, ProductSpecTemplates::aliases($key), true)) {
                        $match = $key;
                        break;
                    }
                }
                if ($match === null) {
                    foreach ($columnKeys as $key => $column) {
                        if (! isset($columnValues[$column]) && trim((string) $product->{$column}) === '' && in_array($label, ProductSpecTemplates::aliases($key), true)) {
                            $columnValues[$column] = (string) ($row['value'] ?? '');
                            $changes[] = '"'.$row['label'].'" → cột '.$column;
                            unset($rows[$i]);

                            continue 2;
                        }
                    }
                    $unmatched[$type][$label] = ($unmatched[$type][$label] ?? 0) + 1;

                    continue;
                }
                $taken[$match] = true;
                $changes[] = '"'.$row['label'].'" → '.$keys[$match];
                $rows[$i] = ['key' => $match, 'label' => $keys[$match], 'value' => (string) ($row['value'] ?? '')];
            }
            if ($type === 'inverter') {
                [$rows, $read, $warranty] = $this->readInverterSentence(array_values($rows), $taken, $keys, (string) $product->warranty);
                array_push($changes, ...$read);
                if ($warranty !== null && ! isset($columnValues['warranty'])) {
                    $columnValues['warranty'] = $warranty;
                }
            }
            if ($changes !== []) {
                $plan[] = [$product, array_values($rows), $changes, $columnValues];
                $byType[$type] = ($byType[$type] ?? 0) + 1;
            }
        }

        $lines = ['# Gắn mã thông số sản phẩm ('.now()->format('d/m/Y H:i').', '.($this->option('apply') ? 'đã ghi' : 'chạy thử').')', '',
            count($plan).' sản phẩm có thay đổi.', '', '| Loại | Sản phẩm |', '|---|---|'];
        foreach ($byType as $type => $n) {
            $lines[] = '| '.(ProductSpecTemplates::types()[$type] ?? $type).' | '.$n.' |';
        }
        $lines[] = '';
        $lines[] = '## Từng sản phẩm';
        foreach ($plan as [$product, , $changes]) {
            $lines[] = '- #'.$product->id.' '.$product->name.': '.implode('; ', $changes);
        }
        $lines[] = '';
        $lines[] = '## Nhãn chưa khớp ô nào (giữ nguyên ở "Thông số thêm")';
        foreach ($unmatched as $type => $labels) {
            arsort($labels);
            $lines[] = '- '.(ProductSpecTemplates::types()[$type] ?? $type).': '.collect($labels)->map(fn ($n, $l) => $l.' ('.$n.')')->implode(', ');
        }
        $report = implode("\n", $lines)."\n";
        if ($path = $this->option('report')) {
            file_put_contents($path, $report);
        }
        $this->line($report);

        if (! $this->option('apply')) {
            $this->warn('Chạy thử: chưa ghi gì. Thêm --apply để ghi.');

            return self::SUCCESS;
        }
        DB::transaction(function () use ($plan) {
            foreach ($plan as [$product, $rows, $changes, $columnValues]) {
                $update = ['additional_specs' => json_encode($rows, JSON_UNESCAPED_UNICODE), 'updated_at' => now()] + $columnValues;
                DB::table('products')->where('id', $product->id)->update($update);
                Audit::record('product.spec_keys', 'Product', (int) $product->id, ['additional_specs' => $product->additional_specs], ['changes' => $changes], 'pts:spec-keys');
            }
        });
        $this->info('Đã ghi '.count($plan).' sản phẩm.');

        return self::SUCCESS;
    }

    /**
     * "5kw Single Phase Inverter 2 MPPT, +CT; With DC Switch, With Wifi, BH 5 năm".
     *
     * @return array{0: array<int, array<string, string>>, 1: list<string>, 2: ?string}
     */
    private function readInverterSentence(array $rows, array $taken, array $keys, string $warranty): array
    {
        $sentence = collect($rows)->first(fn ($row) => mb_strtolower(trim((string) ($row['label'] ?? ''))) === 'thông tin');
        if (! $sentence) {
            return [$rows, [], null];
        }
        $text = (string) $sentence['value'];
        $read = [];
        $add = function (string $key, string $value) use (&$rows, &$read, $taken, $keys) {
            if (! isset($taken[$key]) && isset($keys[$key])) {
                $rows[] = ['key' => $key, 'label' => $keys[$key], 'value' => $value];
                $read[] = $keys[$key].' = '.$value.' (từ "Thông tin")';
            }
        };
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*kw\b/iu', $text, $m)) {
            $add('ac_power', str_replace('.', ',', $m[1]).' kW');
        }
        if (preg_match('/\b(single|1)[\s-]*phase\b|\b1\s*pha\b/iu', $text)) {
            $add('phase_count', '1 pha');
        } elseif (preg_match('/\b(three|3)[\s-]*phase\b|\b3\s*pha\b/iu', $text)) {
            $add('phase_count', '3 pha');
        }
        if (preg_match('/(\d+)\s*mppt\b/iu', $text, $m)) {
            $add('mppt_count', $m[1].' MPPT');
        }
        if (preg_match('/\bwi-?fi\b/iu', $text)) {
            $add('monitoring', 'WiFi');
        }
        $newWarranty = null;
        if (trim($warranty) === '' && preg_match('/\bBH\s*(\d+)\s*năm\b/iu', $text, $m)) {
            $newWarranty = $m[1].' năm';
            $read[] = 'Bảo hành = '.$newWarranty.' (từ "Thông tin")';
        }

        return [$rows, $read, $newWarranty];
    }
}
