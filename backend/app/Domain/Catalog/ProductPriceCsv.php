<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductPriceCsv
{
    public const HEADERS = ['ID', 'Mã sản phẩm', 'Tên sản phẩm', 'Giá bán', 'Giá khuyến mãi', 'Chỉ báo giá', 'Hiển thị'];

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::HEADERS, ',', '"', '');

            Product::query()->orderBy('id')->chunkById(200, function ($products) use ($out): void {
                foreach ($products as $product) {
                    fputcsv($out, [
                        $product->id,
                        $this->safeText($product->code),
                        $this->safeText($product->name),
                        $product->regular_price,
                        $product->sale_price,
                        (int) $product->quote_only,
                        (int) $product->is_visible,
                    ], ',', '"', '');
                }
            });
            fclose($out);
        }, 'pts-gia-san-pham-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{total: int, changed: int, skipped: int, skipped_rows: array<int, array>, errors: array<int, string>, changes: array<int, array>, fingerprint: string} */
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
                throw ValidationException::withMessages(['file' => 'Cột CSV không đúng mẫu. Hãy xuất mẫu mới từ Admin rồi chỉnh các cột giá và trạng thái.']);
            }

            $rows = [];
            $errors = [];
            $seen = [];
            $line = 1;
            while (($row = fgetcsv($in, 0, ',', '"', '')) !== false) {
                $line++;
                if ($line > 5001) {
                    $errors[$line] = 'Tối đa 5.000 sản phẩm mỗi lần.';
                    break;
                }
                if ($row === [null] || count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }
                if (count($row) !== count(self::HEADERS) || ! ctype_digit(trim((string) ($row[0] ?? '')))) {
                    $errors[$line] = 'Thiếu ID hoặc số cột không đúng mẫu.';

                    continue;
                }
                if (! mb_check_encoding(implode('', $row), 'UTF-8')) {
                    $errors[$line] = 'CSV phải được lưu dưới dạng UTF-8.';

                    continue;
                }
                $id = (int) trim($row[0]);
                if ($id < 1 || isset($seen[$id])) {
                    $errors[$line] = 'ID sản phẩm bị lặp hoặc không hợp lệ.';

                    continue;
                }
                $seen[$id] = true;
                $rows[$line] = array_map(fn ($value) => trim((string) $value), $row);
            }
            if ($rows === [] && $errors === []) {
                throw ValidationException::withMessages(['file' => 'CSV không có dòng sản phẩm.']);
            }

            $query = Product::query()->whereIn('id', array_keys($seen));
            $products = ($lock ? $query->lockForUpdate() : $query)->get()->keyBy('id');
            $changes = [];
            $skipped = 0;
            $skippedRows = [];
            foreach ($rows as $number => $row) {
                $product = $products->get((int) $row[0]);
                if (! $product) {
                    $errors[$number] = 'Không tìm thấy ID sản phẩm.';

                    continue;
                }
                if ($this->plainText($row[1]) !== (string) ($product->code ?? '') || $this->plainText($row[2]) !== (string) $product->name) {
                    $errors[$number] = 'Mã hoặc tên sản phẩm không khớp với ID; hãy xuất mẫu mới.';

                    continue;
                }
                try {
                    $fields = [
                        'regular_price' => $this->money($row[3], false),
                        'sale_price' => $this->money($row[4], true),
                        'quote_only' => $this->flag($row[5]),
                        'is_visible' => $this->flag($row[6]),
                    ];
                } catch (\InvalidArgumentException $exception) {
                    $errors[$number] = $exception->getMessage();

                    continue;
                }

                $update = [];
                $before = [];
                foreach ($fields as $field => $value) {
                    if ($value === '') {
                        continue;
                    }
                    $old = $product->{$field};
                    $old = in_array($field, ['quote_only', 'is_visible'], true) ? (bool) $old : ($old === null ? null : (string) $old);
                    if ($old !== $value) {
                        $before[$field] = $old;
                        $update[$field] = $value;
                    }
                }
                if ($update === []) {
                    $skipped++;
                    $skippedRows[$number] = ['id' => $product->id, 'name' => $product->name];

                    continue;
                }
                $regular = $update['regular_price'] ?? ($product->regular_price === null ? null : (string) $product->regular_price);
                $sale = array_key_exists('sale_price', $update) ? $update['sale_price'] : ($product->sale_price === null ? null : (string) $product->sale_price);
                if ($regular !== null && $sale !== null && (int) $sale > (int) $regular) {
                    $errors[$number] = 'Giá khuyến mãi lớn hơn giá bán.';

                    continue;
                }
                $changes[$number] = ['id' => $product->id, 'name' => $product->name, 'before' => $before, 'after' => $update];
            }

            ksort($errors);

            return [
                'total' => count($rows), 'changed' => count($changes), 'skipped' => $skipped,
                'skipped_rows' => $skippedRows, 'errors' => $errors, 'changes' => $changes,
                'fingerprint' => hash('sha256', json_encode([$changes, $errors], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
            ];
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
                throw ValidationException::withMessages(['file' => 'Dữ liệu đã thay đổi hoặc CSV có lỗi. Hãy xem trước lại trước khi áp dụng.']);
            }
            foreach ($preview['changes'] as $change) {
                Product::query()->whereKey($change['id'])->update($change['after']);
            }

            return $preview;
        });
    }

    public function report(array $preview): StreamedResponse
    {
        return response()->streamDownload(function () use ($preview): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Dòng CSV', 'ID', 'Sản phẩm', 'Kết quả', 'Chi tiết'], ',', '"', '');
            foreach ($preview['changes'] as $line => $change) {
                $labels = ['regular_price' => 'Giá bán', 'sale_price' => 'Giá khuyến mãi', 'quote_only' => 'Chỉ báo giá', 'is_visible' => 'Hiển thị'];
                $details = [];
                foreach ($change['after'] as $field => $value) {
                    $before = $change['before'][$field];
                    $format = fn ($item) => $item === null ? 'trống' : (is_bool($item) ? ($item ? '1' : '0') : (string) $item);
                    $details[] = $labels[$field].': '.$format($before).' → '.$format($value);
                }
                fputcsv($out, [$line, $change['id'], $this->safeText($change['name']), 'Sẽ cập nhật', implode('; ', $details)], ',', '"', '');
            }
            foreach ($preview['errors'] as $line => $error) {
                fputcsv($out, [$line, '', '', 'Lỗi', $this->safeText($error)], ',', '"', '');
            }
            foreach ($preview['skipped_rows'] as $line => $row) {
                fputcsv($out, [$line, $row['id'], $this->safeText($row['name']), 'Không đổi', ''], ',', '"', '');
            }
            fclose($out);
        }, 'pts-ket-qua-xem-truoc-gia.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function money(string $value, bool $allowClear): ?string
    {
        if ($value === '') {
            return '';
        }
        if ($allowClear && mb_strtoupper($value) === 'XÓA') {
            return null;
        }
        $normalized = trim(preg_replace('/[đ₫]/u', '', mb_strtolower($value)));
        $digits = preg_replace('/[.,\s\x{00A0}]/u', '', $normalized);
        if (! ctype_digit($digits) || strlen($digits) > 15 || ! preg_match('/^(?:\d{1,3}(?:[.,\s]\d{3})+|\d+)$/u', $normalized)) {
            throw new \InvalidArgumentException('Giá không hợp lệ; chỉ nhập số nguyên hoặc nhóm nghìn bằng dấu chấm, phẩy.');
        }

        return ltrim($digits, '0') ?: '0';
    }

    private function flag(string $value): string|bool
    {
        if ($value === '') {
            return '';
        }

        return match (mb_strtolower($value)) {
            '1', 'có', 'true', 'hiện' => true,
            '0', 'không', 'false', 'ẩn' => false,
            default => throw new \InvalidArgumentException('Trạng thái chỉ nhận 1/0, Có/Không hoặc Hiện/Ẩn.'),
        };
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
