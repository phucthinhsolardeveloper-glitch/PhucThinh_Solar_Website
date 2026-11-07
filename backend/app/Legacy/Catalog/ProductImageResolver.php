<?php

namespace App\Legacy\Catalog;

use App\Legacy\Support\StatusFlags;
use Illuminate\Support\Facades\DB;

/**
 * Port of productImageResolve() / productImageResolveFromDatabase(): the main photo if its file exists,
 * otherwise the first visible gallery photo whose file exists. Names that point outside the folder are refused.
 */
class ProductImageResolver
{
    public function __construct(private readonly string $productDirectory) {}

    /**
     * @param  array<string, mixed>  $product
     * @return array{has_image: bool, photo: string, source: 'photo'|'gallery'|'none', broken: list<string>}
     */
    public function resolve(array $product): array
    {
        $main = $this->pick([['raw' => trim((string) ($product['photo'] ?? '')), 'source' => 'photo']]);
        if ($main['has_image']) {
            return $main;
        }

        $candidates = [['raw' => trim((string) ($product['photo'] ?? '')), 'source' => 'photo']];
        foreach ($this->galleryRows($product) as $row) {
            if (! (new StatusFlags($row->status))->visible()) {
                continue;
            }
            $candidates[] = ['raw' => trim((string) $row->photo), 'source' => 'gallery'];
        }

        return $this->pick($candidates);
    }

    /**
     * @param  list<array{raw: string, source: string}>  $candidates
     * @return array{has_image: bool, photo: string, source: 'photo'|'gallery'|'none', broken: list<string>}
     */
    private function pick(array $candidates): array
    {
        $broken = [];
        foreach ($candidates as $candidate) {
            if ($candidate['raw'] === '') {
                continue;
            }
            $file = $this->safeName($candidate['raw']);
            if ($file !== '' && is_file($this->productDirectory.DIRECTORY_SEPARATOR.$file)) {
                /** @var 'photo'|'gallery' $source */
                $source = $candidate['source'];

                return ['has_image' => true, 'photo' => $file, 'source' => $source, 'broken' => array_values(array_unique($broken))];
            }
            $broken[] = $candidate['raw'];
        }

        return ['has_image' => false, 'photo' => '', 'source' => 'none', 'broken' => array_values(array_unique($broken))];
    }

    private function safeName(string $value): string
    {
        return $value === '' || str_contains($value, "\0") || basename($value) !== $value ? '' : $value;
    }

    /** @return iterable<object{photo: string|null, status: string|null}> */
    private function galleryRows(array $product): iterable
    {
        $id = (int) ($product['id'] ?? 0);
        $type = trim((string) ($product['type'] ?? ''));
        if ($id <= 0 || $type === '') {
            return [];
        }

        return DB::connection('legacy')->table('table_gallery')
            ->where('id_parent', $id)->where('com', 'product')->where('kind', 'man')->where('val', $type)
            ->whereRaw("find_in_set('hienthi', status)")
            ->orderBy('numb')->orderBy('id')
            ->get(['photo', 'status']);
    }
}
