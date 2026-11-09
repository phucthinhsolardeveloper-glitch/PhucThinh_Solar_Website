<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Puts the partner logos shipped in resources/brand-logos into the upload folder and on the brands
 * they belong to (matched by name: accents, case, spaces and a "JD" prefix ignored). Admin edits win:
 * a brand whose logo was changed since is left alone unless --force.
 */
class BrandLogosCommand extends Command
{
    protected $signature = 'pts:brand-logos {--force : Also replace logos chosen in Admin} {--dry-run : Only list what would change}';

    protected $description = 'Install the bundled partner brand logos into uploads and link them to brands.';

    public function handle(): int
    {
        $files = glob(resource_path('brand-logos/*.webp')) ?: [];
        $byKey = [];
        foreach ($files as $file) {
            $byKey[self::key(pathinfo($file, PATHINFO_FILENAME))] = $file;
        }
        $disk = Storage::disk('upload_product');
        $rows = [];
        foreach (Brand::query()->orderBy('sort_order')->get() as $brand) {
            $file = $byKey[self::key((string) $brand->name)] ?? $byKey[self::key((string) $brand->slug)] ?? null;
            if ($file === null) {
                continue;
            }
            $target = 'brand-'.$brand->slug.'.webp';
            $current = (string) $brand->image;
            $state = match (true) {
                $current === $target => 'đã có',
                $current !== '' && ! str_starts_with($current, 'thuong-hieu-') && ! $this->option('force') => 'giữ logo Admin',
                default => 'cập nhật',
            };
            if ($state === 'cập nhật' && ! $this->option('dry-run')) {
                $disk->put($target, (string) file_get_contents($file));
                $brand->update(['image' => $target]);
            }
            $rows[] = [$brand->name, $current ?: '—', $target, $state];
        }
        $this->table(['Thương hiệu', 'Logo hiện tại', 'Logo mới', 'Kết quả'], $rows);

        return self::SUCCESS;
    }

    private static function key(string $value): string
    {
        $key = (string) preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii($value)));

        return $key === 'jdjindian' ? 'jindian' : $key;
    }
}
