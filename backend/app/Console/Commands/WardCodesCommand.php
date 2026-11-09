<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fills the official codes of provinces and wards from the 2025 list (resources/locations/wards-2025.csv,
 * made from Danh-muc-Phuong-xa_moi.xlsx). Matching is by name without "Thành phố/Tỉnh/Phường/Xã/Đặc khu".
 * Safe to run again; prints what it could not match.
 */
class WardCodesCommand extends Command
{
    protected $signature = 'pts:ward-codes {file? : CSV with province_code, province_name, province_tax_code, ward_tax_code, ward_name}';

    protected $description = 'Fill official province and ward codes from the 2025 administrative list.';

    public function handle(): int
    {
        $path = (string) ($this->argument('file') ?: resource_path('locations/wards-2025.csv'));
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            $this->error('Không đọc được '.$path);

            return self::FAILURE;
        }
        fgetcsv($handle, 0, ',', '"', '');
        $provinces = DB::table('provinces')->get(['id', 'name'])->mapWithKeys(fn ($p) => [self::key($p->name) => (int) $p->id]);
        $wards = [];
        foreach (DB::table('wards')->get(['id', 'province_id', 'name']) as $ward) {
            foreach (['e:'.self::exact($ward->name), 'a:'.self::key($ward->name), 'c:'.self::compact($ward->name)] as $key) {
                $wards[$ward->province_id][$key][] = (int) $ward->id;
            }
        }
        $done = ['provinces' => [], 'wards' => 0];
        $missing = [];
        DB::transaction(function () use ($handle, $provinces, $wards, &$done, &$missing) {
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                [$provinceCode, $provinceName, $provinceTax, $wardTax, $wardName] = array_map('trim', $row + ['', '', '', '', '']);
                $provinceId = $provinces[self::key($provinceName)] ?? null;
                if ($provinceId === null) {
                    $missing[] = $provinceName.' (tỉnh)';

                    continue;
                }
                if (! isset($done['provinces'][$provinceId])) {
                    DB::table('provinces')->where('id', $provinceId)->update(['code' => $provinceCode, 'tax_code' => $provinceTax]);
                    $done['provinces'][$provinceId] = true;
                }
                $ids = [];
                // Exact name with accents first ("Văn Lăng" is not "Văn Lang"), then looser forms.
                foreach (['e:'.self::exact($wardName), 'a:'.self::key($wardName), 'c:'.self::compact($wardName), 'c:'.self::compact((string) preg_replace('/\s+-\s+.+$/u', '', $wardName))] as $key) {
                    $ids = array_values(array_unique($wards[$provinceId][$key] ?? []));
                    if ($ids !== []) {
                        break;
                    }
                }
                if (count($ids) !== 1) {
                    $missing[] = $wardName.', '.$provinceName.(count($ids) > 1 ? ' (trùng tên)' : '');

                    continue;
                }
                DB::table('wards')->where('id', $ids[0])->update(['tax_code' => $wardTax]);
                $done['wards']++;
            }
        });
        fclose($handle);
        $this->info('Tỉnh/thành có mã: '.count($done['provinces']).'. Phường/xã có mã: '.$done['wards'].'.');
        foreach (array_slice($missing, 0, 40) as $line) {
            $this->warn('Chưa ghép được: '.$line);
        }
        if (count($missing) > 40) {
            $this->warn('… và '.(count($missing) - 40).' dòng khác.');
        }

        return self::SUCCESS;
    }

    /** Letters only, "y" read as "i": "B' Lao" = "B'Lao", "Lục Sỹ" = "Lục Sĩ", "Langbiang - Đà Lạt" = "Lang Biang - Đà Lạt". */
    private static function compact(string $name): string
    {
        return str_replace('y', 'i', (string) preg_replace('/[^a-z0-9]/', '', self::key($name)));
    }

    private static function exact(string $name): string
    {
        return mb_strtolower(trim((string) preg_replace(['/^(thành phố|tp\.?|tỉnh|phường|xã|đặc khu|thị trấn)\s+/iu', '/\s+/u'], ['', ' '], trim($name))), 'UTF-8');
    }

    private static function key(string $name): string
    {
        $name = (string) preg_replace('/^(thành phố|tp\.?|tỉnh|phường|xã|đặc khu|thị trấn)\s+/iu', '', trim($name));

        return Str::lower(Str::ascii((string) preg_replace('/\s+/u', ' ', $name)));
    }
}
