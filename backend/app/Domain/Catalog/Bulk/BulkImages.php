<?php

namespace App\Domain\Catalog\Bulk;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * Pictures added in bulk from a ZIP named after product codes: "ABC-01.jpg" is the main picture,
 * "ABC-02.jpg" onwards go to the gallery. Existing main pictures are never replaced.
 */
final class BulkImages
{
    private const MAX_ZIP = 20 * 1024 * 1024;

    private const MAX_FILES = 200;

    private const MAX_FILE = 5 * 1024 * 1024;

    private const MAX_TOTAL = 100 * 1024 * 1024;

    private const MAX_SIDE = 1920;

    /** @return array{dir: string, rows: list<array<string, mixed>>, counts: array<string, int>} */
    public function preview(string $zipPath, string $originalName): array
    {
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'zip' || (int) filesize($zipPath) < 1 || (int) filesize($zipPath) > self::MAX_ZIP) {
            self::fail('Tệp phải là ZIP, tối đa 20 MB.');
        }
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            self::fail('Không mở được tệp ZIP.');
        }
        if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_FILES) {
            $zip->close();
            self::fail('ZIP phải chứa từ 1 đến '.self::MAX_FILES.' ảnh.');
        }
        $products = [];
        foreach (Product::query()->whereNotNull('code')->where('code', '!=', '')->get(['id', 'code', 'image']) as $product) {
            $products[mb_strtolower(trim((string) $product->code), 'UTF-8')][] = $product;
        }
        $gallery = DB::table('product_images')->get(['product_id', 'path'])->map(fn ($row) => $row->product_id.'|'.mb_strtolower((string) $row->path, 'UTF-8'))->flip();
        $dir = 'bulk-images/'.bin2hex(random_bytes(16));
        $disk = Storage::disk('local');
        $disk->makeDirectory($dir);
        $rows = [];
        $counts = ['ready' => 0, 'ready_main' => 0, 'ready_gallery' => 0, 'existing' => 0, 'missing_product' => 0, 'error' => 0];
        $total = 0;
        $seen = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $entry = (string) ($stat['name'] ?? '');
            if (str_ends_with($entry, '/') || str_starts_with($entry, '__MACOSX/') || basename($entry) === '.DS_Store') {
                continue;
            }
            $row = self::identify($entry) + ['entry' => $entry, 'status' => 'error', 'message' => '', 'product_id' => null, 'file' => null];
            $total += (int) ($stat['size'] ?? 0);
            $key = mb_strtolower($row['filename'], 'UTF-8');
            $problem = match (true) {
                $total > self::MAX_TOTAL => 'Tổng dung lượng giải nén vượt 100 MB.',
                $entry !== basename($entry) || str_contains($entry, '..') => 'ZIP không được chứa thư mục.',
                ! in_array($row['extension'], ['jpg', 'jpeg', 'png', 'webp'], true) => 'Chỉ nhận JPG, PNG, WEBP.',
                $row['code'] === '' => 'Tên tệp phải theo mẫu MA-01.jpg.',
                (int) ($stat['size'] ?? 0) > self::MAX_FILE || (int) ($stat['size'] ?? 0) < 1 => 'Mỗi ảnh tối đa 5 MB.',
                isset($seen[$key]) => 'Tên ảnh bị lặp trong ZIP.',
                default => null,
            };
            $seen[$key] = true;
            if ($problem === null) {
                $problem = $this->place($zip, $entry, $row, $dir, $products, $gallery, $counts);
            }
            if ($problem !== null) {
                $row['message'] = $problem;
                $counts['error']++;
            }
            $rows[] = $row;
        }
        $zip->close();

        return ['dir' => $dir, 'rows' => $rows, 'counts' => $counts];
    }

    /** @param array{dir: string, rows: list<array<string, mixed>>} $preview */
    public function apply(array $preview): int
    {
        $disk = Storage::disk('local');
        $target = Storage::disk('upload_product');
        $written = [];
        $added = 0;
        try {
            DB::transaction(function () use ($preview, $disk, $target, &$written, &$added): void {
                foreach ($preview['rows'] as $row) {
                    if ($row['status'] !== 'ready') {
                        continue;
                    }
                    $product = Product::query()->lockForUpdate()->findOrFail($row['product_id']);
                    $name = $row['filename'];
                    if ($target->exists($name)) {
                        $name = pathinfo($name, PATHINFO_FILENAME).'-'.bin2hex(random_bytes(3)).'.'.$row['extension'];
                    }
                    $target->put($name, (string) $disk->get($row['file']));
                    $written[] = $name;
                    if ($row['role'] === 'main') {
                        if (trim((string) $product->image) !== '') {
                            throw ValidationException::withMessages(['file' => 'Sản phẩm mã '.$row['code'].' đã có ảnh chính sau bước xem trước; chưa ghi gì.']);
                        }
                        $product->update(['image' => $name]);
                    } else {
                        $product->images()->create(['path' => $name, 'is_visible' => true, 'sort_order' => (int) $row['sequence']]);
                    }
                    $added++;
                }
            });
        } catch (\Throwable $error) {
            $target->delete($written);
            throw $error;
        }
        $disk->deleteDirectory($preview['dir']);

        return $added;
    }

    private function place(ZipArchive $zip, string $entry, array &$row, string $dir, array $products, $gallery, array &$counts): ?string
    {
        $owners = $products[mb_strtolower($row['code'], 'UTF-8')] ?? [];
        if ($owners === []) {
            $row['status'] = 'missing_product';
            $row['message'] = 'Không có sản phẩm mã này.';
            $counts['missing_product']++;

            return null;
        }
        if (count($owners) > 1) {
            return 'Mã này thuộc nhiều sản phẩm.';
        }
        $product = $owners[0];
        $row['product_id'] = (int) $product->id;
        if ($row['role'] === 'main' && trim((string) $product->image) !== '') {
            $row['status'] = 'existing';
            $row['message'] = 'Đã có ảnh chính, giữ nguyên.';
            $counts['existing']++;

            return null;
        }
        if ($row['role'] === 'gallery' && $gallery->has($product->id.'|'.mb_strtolower($row['filename'], 'UTF-8'))) {
            $row['status'] = 'existing';
            $row['message'] = 'Ảnh cùng tên đã có trong thư viện.';
            $counts['existing']++;

            return null;
        }
        $relative = $dir.'/'.bin2hex(random_bytes(8)).'.'.$row['extension'];
        $bytes = $zip->getFromName($entry);
        if ($bytes === false) {
            return 'Không đọc được ảnh trong ZIP.';
        }
        Storage::disk('local')->put($relative, $bytes);
        $path = Storage::disk('local')->path($relative);
        $problem = self::finalise($path, $row['extension']);
        if ($problem !== null) {
            Storage::disk('local')->delete($relative);

            return $problem;
        }
        $row['status'] = 'ready';
        $row['file'] = $relative;
        $row['message'] = $row['role'] === 'main' ? 'Sẽ thành ảnh chính.' : 'Sẽ thêm vào thư viện ảnh.';
        $counts['ready']++;
        $counts[$row['role'] === 'main' ? 'ready_main' : 'ready_gallery']++;

        return null;
    }

    /** Real picture of the claimed type; anything larger than 1920 px is scaled down. */
    private static function finalise(string $path, string $extension): ?string
    {
        $info = @getimagesize($path);
        $expected = ['jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG, 'webp' => IMAGETYPE_WEBP][$extension];
        if (! is_array($info) || $info[2] !== $expected) {
            return 'Nội dung tệp không phải ảnh '.strtoupper($extension).' hợp lệ.';
        }
        if ($info[0] <= self::MAX_SIDE && $info[1] <= self::MAX_SIDE) {
            return null;
        }
        $source = match ($expected) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            default => @imagecreatefromwebp($path),
        };
        if ($source === false) {
            return 'Không xử lý được ảnh.';
        }
        $scale = self::MAX_SIDE / max($info[0], $info[1]);
        $resized = imagescale($source, (int) round($info[0] * $scale), (int) round($info[1] * $scale));
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        match ($expected) {
            IMAGETYPE_JPEG => imagejpeg($resized, $path, 88),
            IMAGETYPE_PNG => imagepng($resized, $path, 7),
            default => imagewebp($resized, $path, 85),
        };

        return null;
    }

    /** @return array{code: string, sequence: int, role: string, extension: string, filename: string} */
    private static function identify(string $entry): array
    {
        $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        $code = trim(pathinfo($entry, PATHINFO_FILENAME));
        $sequence = 1;
        if (preg_match('/^(.*)-(\d{2,3})$/u', $code, $match)) {
            $code = trim($match[1]);
            $sequence = (int) $match[2];
        }
        $safe = (string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $code);

        return ['code' => $code, 'sequence' => $sequence, 'role' => $sequence === 1 ? 'main' : 'gallery', 'extension' => $extension,
            'filename' => $safe.'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT).'.'.$extension];
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
