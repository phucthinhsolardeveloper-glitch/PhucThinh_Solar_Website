<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use App\Models\User;
use App\Rules\FreePublicSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Imports only catalog identity for new hidden products and media references for existing products. */
class ProductMediaCsv
{
    public const HEADERS = ['ID', 'Mã sản phẩm', 'Tên sản phẩm', 'Đường dẫn', 'ID danh mục', 'Ảnh chính', 'Ảnh phụ', 'Thư viện ảnh'];

    private const IMAGE_MIME = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::HEADERS, ',', '"', '');
            Product::query()->with('images')->orderBy('id')->chunkById(200, function ($products) use ($out): void {
                foreach ($products as $product) {
                    fputcsv($out, [
                        $product->id, $this->safeText($product->code), $this->safeText($product->name), $product->slug,
                        $product->category_id, $product->image, $product->image_secondary,
                        $product->images->pluck('path')->implode('|'),
                    ], ',', '"', '');
                }
            });
            fclose($out);
        }, 'pts-san-pham-media-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{total:int, created:int, updated:int, skipped:int, errors:array<int,string>, operations:array<int,array>, fingerprint:string} */
    public function preview(string $path, bool $lock = false): array
    {
        if (! is_file($path) || filesize($path) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Tệp CSV không tồn tại hoặc lớn hơn 2 MB.']);
        }
        $in = fopen($path, 'r');
        if ($in === false) {
            throw ValidationException::withMessages(['file' => 'Không đọc được tệp CSV.']);
        }

        try {
            $headers = fgetcsv($in, 0, ',', '"', '');
            if ($headers === false) {
                throw ValidationException::withMessages(['file' => 'Tệp CSV trống.']);
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            if ($headers !== self::HEADERS) {
                throw ValidationException::withMessages(['file' => 'Cột CSV không đúng mẫu. Hãy xuất mẫu mới từ Admin.']);
            }
            $total = $created = $updated = $skipped = 0;
            $errors = $operations = $seenIds = $seenSlugs = $seenCodes = [];
            $line = 1;
            while (($row = fgetcsv($in, 0, ',', '"', '')) !== false) {
                $line++;
                if ($line > 5001) {
                    $errors[$line] = 'Tối đa 5.000 dòng mỗi lần.';
                    break;
                }
                if ($row === [null] || count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }
                $total++;
                if (count($row) !== count(self::HEADERS) || ! mb_check_encoding(implode('', $row), 'UTF-8')) {
                    $errors[$line] = 'Số cột không đúng mẫu hoặc CSV không phải UTF-8.';

                    continue;
                }
                $row = array_map(fn ($value) => trim((string) $value), $row);
                [$id, $code, $name, $slug, $category, $image, $secondary, $gallery] = $row;
                $code = $this->plainText($code);
                $name = $this->plainText($name);

                if ($id !== '') {
                    if (! ctype_digit($id) || (int) $id < 1 || isset($seenIds[(int) $id])) {
                        $errors[$line] = 'ID sản phẩm bị lặp hoặc không hợp lệ.';

                        continue;
                    }
                    $seenIds[(int) $id] = true;
                    $query = Product::query()->whereKey((int) $id);
                    $product = $lock ? $query->lockForUpdate()->first() : $query->first();
                    if (! $product || $code !== (string) ($product->code ?? '') || $name !== $product->name || $slug !== $product->slug || $category !== (string) ($product->category_id ?? '')) {
                        $errors[$line] = 'ID, mã, tên, đường dẫn hoặc danh mục không khớp; hãy xuất mẫu mới.';

                        continue;
                    }
                    $images = $lock ? $product->images()->lockForUpdate()->get() : $product->images;
                    $before = ['image' => $product->image, 'image_secondary' => $product->image_secondary,
                        'gallery' => $images->pluck('path')->all()];
                    $after = [];
                    try {
                        foreach (['image' => $image, 'image_secondary' => $secondary] as $field => $value) {
                            if ($value === '') {
                                continue;
                            }
                            $value = mb_strtoupper($value) === 'XÓA' ? null : $this->image($value, $before[$field]);
                            if ($value !== $before[$field]) {
                                $after[$field] = $value;
                            }
                        }
                        if ($gallery !== '') {
                            $paths = mb_strtoupper($gallery) === 'XÓA' ? [] : $this->gallery($gallery, $before['gallery']);
                            if ($paths !== $before['gallery']) {
                                if (count($before['gallery']) !== count(array_unique($before['gallery']))) {
                                    throw new \InvalidArgumentException('Thư viện gốc có ảnh trùng; cần sửa trong form trước khi nhập CSV.');
                                }
                                $after['gallery'] = $paths;
                            }
                        }
                    } catch (\InvalidArgumentException $exception) {
                        $errors[$line] = $exception->getMessage();

                        continue;
                    }
                    if ($after === []) {
                        $skipped++;
                    } else {
                        $operations[$line] = ['type' => 'update', 'id' => $product->id, 'name' => $product->name, 'before' => $before, 'after' => $after];
                        $updated++;
                    }

                    continue;
                }

                $slug = $slug !== '' ? $slug : Str::slug($name);
                if ($name === '' || mb_strlen($name) > 255 || $slug === '' || mb_strlen($slug) > 255 || ! ctype_digit($category) || (int) $category < 1) {
                    $errors[$line] = 'Sản phẩm mới cần tên, đường dẫn hợp lệ và ID danh mục.';

                    continue;
                }
                if (isset($seenSlugs[$slug]) || ! Validator::make(['slug' => $slug], ['slug' => [new FreePublicSlug('products')]])->passes()) {
                    $errors[$line] = 'Đường dẫn đã dùng hoặc không hợp lệ.';

                    continue;
                }
                if (! DB::table('categories')->where('id', (int) $category)->exists()) {
                    $errors[$line] = 'ID danh mục không tồn tại.';

                    continue;
                }
                if (mb_strlen($code) > 255 || ($code !== '' && (isset($seenCodes[$code]) || Product::query()->where('code', $code)->exists()))) {
                    $errors[$line] = 'Mã sản phẩm đã dùng hoặc quá dài.';

                    continue;
                }
                try {
                    $main = $image === '' ? null : $this->image($image);
                    $second = $secondary === '' ? null : $this->image($secondary);
                    $paths = $gallery === '' ? [] : $this->gallery($gallery);
                } catch (\InvalidArgumentException $exception) {
                    $errors[$line] = $exception->getMessage();

                    continue;
                }
                $seenSlugs[$slug] = true;
                if ($code !== '') {
                    $seenCodes[$code] = true;
                }
                $operations[$line] = ['type' => 'create', 'name' => $name, 'data' => [
                    'name' => $name, 'slug' => $slug, 'code' => $code !== '' ? $code : null, 'category_id' => (int) $category,
                    'image' => $main, 'image_secondary' => $second, 'is_visible' => false,
                ], 'gallery' => $paths];
                $created++;
            }
            if ($total === 0) {
                throw ValidationException::withMessages(['file' => 'CSV không có dòng sản phẩm.']);
            }
            ksort($errors);

            return ['total' => $total, 'created' => $created, 'updated' => $updated, 'skipped' => $skipped,
                'errors' => $errors, 'operations' => $operations,
                'fingerprint' => hash('sha256', json_encode([$operations, $errors], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))];
        } finally {
            fclose($in);
        }
    }

    public function apply(string $path, string $expectedFingerprint, User $actor): array
    {
        abort_unless($actor->canPerform('catalog_bulk'), 403);

        return DB::transaction(function () use ($path, $expectedFingerprint): array {
            $preview = $this->preview($path, true);
            if ($preview['errors'] !== [] || ! hash_equals($expectedFingerprint, $preview['fingerprint'])) {
                throw ValidationException::withMessages(['file' => 'CSV có lỗi hoặc dữ liệu đã thay đổi. Hãy xem trước lại.']);
            }
            foreach ($preview['operations'] as $operation) {
                if ($operation['type'] === 'create') {
                    $product = Product::query()->create($operation['data']);
                    $this->syncGallery($product, $operation['gallery']);

                    continue;
                }
                $product = Product::query()->findOrFail($operation['id']);
                $after = $operation['after'];
                if (array_key_exists('gallery', $after)) {
                    $this->syncGallery($product, $after['gallery']);
                    unset($after['gallery']);
                }
                if ($after !== []) {
                    $product->update($after);
                }
            }

            return $preview;
        });
    }

    public function report(array $preview): StreamedResponse
    {
        return response()->streamDownload(function () use ($preview): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Dòng CSV', 'Sản phẩm', 'Kết quả', 'Chi tiết'], ',', '"', '');
            foreach ($preview['operations'] as $line => $operation) {
                fputcsv($out, [$line, $this->safeText($operation['name']), $operation['type'] === 'create' ? 'Sẽ tạo ẩn' : 'Sẽ cập nhật media',
                    $operation['type'] === 'create' ? $operation['data']['slug'] : implode(', ', array_keys($operation['after']))], ',', '"', '');
            }
            foreach ($preview['errors'] as $line => $error) {
                fputcsv($out, [$line, '', 'Lỗi', $this->safeText($error)], ',', '"', '');
            }
            fclose($out);
        }, 'pts-ket-qua-san-pham-media.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function image(string $name, ?string $unchanged = null): string
    {
        if ($name === $unchanged) {
            return $name;
        }
        if (basename($name) !== $name || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp)$/i', $name)) {
            throw new \InvalidArgumentException('Tên ảnh không hợp lệ; chỉ dùng tên file trong upload/product.');
        }
        $disk = Storage::disk('upload_product');
        $expectedMime = self::IMAGE_MIME[strtolower(pathinfo($name, PATHINFO_EXTENSION))] ?? null;
        $file = $disk->path($name);
        $actualMime = $disk->exists($name) ? (new \finfo(FILEINFO_MIME_TYPE))->file($file) : null;
        if (! $disk->exists($name) || $disk->size($name) > 15 * 1024 * 1024 || $actualMime !== $expectedMime || @getimagesize($file) === false) {
            throw new \InvalidArgumentException('Ảnh không tồn tại, quá 15 MB hoặc không phải JPG, PNG, GIF, WebP: '.$name);
        }

        return $name;
    }

    /** @param list<string> $unchanged */
    private function gallery(string $value, array $unchanged = []): array
    {
        $paths = array_map('trim', explode('|', $value));
        if (count($paths) > 50 || in_array('', $paths, true) || count($paths) !== count(array_unique($paths))) {
            throw new \InvalidArgumentException('Thư viện ảnh tối đa 50 tên file, cách nhau bằng | và không được trùng.');
        }
        foreach ($paths as $path) {
            $this->image($path, in_array($path, $unchanged, true) ? $path : null);
        }

        return $paths;
    }

    /** @param list<string> $paths */
    private function syncGallery(Product $product, array $paths): void
    {
        $product->images()->whereNotIn('path', $paths)->delete();
        foreach ($paths as $index => $path) {
            $existing = $product->images()->where('path', $path)->first();
            if ($existing) {
                $existing->update(['sort_order' => $index]);
            } else {
                $product->images()->create(['path' => $path, 'sort_order' => $index, 'is_visible' => true]);
            }
        }
    }

    private function safeText(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/u', $value) ? "'".$value : $value;
    }

    private function plainText(string $value): string
    {
        return str_starts_with($value, "'") && preg_match('/^[=+\-@\t\r]/u', substr($value, 1)) ? substr($value, 1) : $value;
    }
}
