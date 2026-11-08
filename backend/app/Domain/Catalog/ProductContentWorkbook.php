<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/** Bulk edits existing catalog content and SEO while leaving prices, URLs and media untouched. */
class ProductContentWorkbook
{
    public const COLUMNS = [
        'Mô tả ngắn' => ['summary', 2000],
        'ID danh mục' => ['category_id', 0],
        'ID thương hiệu' => ['brand_id', 0],
        'Công suất (W)' => ['power_watts', 0],
        'Bộ sản phẩm gồm' => ['package_contents', 5000],
        'Dung lượng pin' => ['battery_capacity', 255],
        'Công suất tấm pin' => ['panel_power', 255],
        'Nguồn sáng / quang thông' => ['light_source', 255],
        'Kích thước đèn' => ['lamp_dimensions', 255],
        'Nhiệt độ màu' => ['color_temperature', 255],
        'Màu ánh sáng' => ['light_color', 255],
        'Chất liệu' => ['material', 255],
        'Thời gian sạc' => ['charging_time', 255],
        'Thời gian chiếu sáng' => ['lighting_time', 255],
        'Chống nước' => ['waterproof_rating', 255],
        'Chip LED' => ['led_chip', 255],
        'Diện tích chiếu sáng' => ['lighting_area', 255],
        'Độ cao lắp đặt' => ['installation_height', 255],
        'Kích thước tấm pin' => ['panel_dimensions', 255],
        'Số lượng / thùng' => ['carton_quantity', 255],
        'Kích thước thùng' => ['carton_dimensions', 255],
        'Trọng lượng' => ['weight', 255],
        'Bảo hành' => ['warranty', 255],
        'Tiêu đề SEO' => ['seo.title', 255],
        'Mô tả SEO' => ['seo.description', 500],
        'Từ khóa SEO' => ['seo.keywords', 1000],
    ];

    /** @return list<string> */
    public static function headers(): array
    {
        return ['ID', 'Mã sản phẩm', 'Tên sản phẩm', ...array_keys(self::COLUMNS)];
    }

    public function export(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'pts-content-');
        $writer = new Writer;
        try {
            $writer->openToFile($path);
            $writer->addRow(Row::fromValues(self::headers()));
            Product::query()->with('seo')->orderBy('id')->chunkById(200, function ($products) use ($writer): void {
                foreach ($products as $product) {
                    $values = [$product->id, (string) ($product->code ?? ''), $product->name];
                    foreach (self::COLUMNS as [$field]) {
                        $value = str_starts_with($field, 'seo.')
                            ? $product->seo?->getAttribute(substr($field, 4)) : $product->getAttribute($field);
                        $values[] = $value === null ? '' : (string) $value;
                    }
                    $cells = [Cell::fromValue($product->id)];
                    foreach (array_slice($values, 1) as $value) {
                        $cells[] = new StringCell((string) $value, null);
                    }
                    $writer->addRow(new Row($cells));
                }
            });
            $writer->close();
        } catch (\Throwable $exception) {
            $writer->close();
            @unlink($path);
            throw $exception;
        }

        return response()->download($path, 'pts-noi-dung-seo-san-pham-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** @return array{total:int,changed:int,skipped:int,errors:array<int,string>,operations:array<int,array>,fingerprint:string} */
    public function preview(string $path, bool $lock = false): array
    {
        $rows = $this->readRows($path);
        $header = array_shift($rows);
        if ($header !== self::headers()) {
            throw ValidationException::withMessages(['file' => 'Cột bảng tính không đúng mẫu. Hãy xuất mẫu mới từ Admin.']);
        }
        $total = $changed = $skipped = 0;
        $errors = $operations = $seen = [];
        foreach ($rows as $lineIndex => $row) {
            $line = $lineIndex + 2;
            if (count(array_filter($row, fn (string $value) => $value !== '')) === 0) {
                continue;
            }
            $total++;
            if (count($row) !== count(self::headers())) {
                $errors[$line] = 'Số cột không đúng mẫu.';

                continue;
            }
            [$id, $code, $name] = $row;
            $id = trim($id);
            if (! ctype_digit($id) || (int) $id < 1 || isset($seen[(int) $id])) {
                $errors[$line] = 'ID bị lặp hoặc không hợp lệ.';

                continue;
            }
            $seen[(int) $id] = true;
            $query = Product::query()->with('seo')->whereKey((int) $id);
            $product = $lock ? $query->lockForUpdate()->first() : $query->first();
            if (! $product || $code !== (string) ($product->code ?? '') || $name !== $product->name) {
                $errors[$line] = 'ID, mã hoặc tên không khớp. Hãy xuất mẫu mới.';

                continue;
            }
            if ($lock) {
                $product->setRelation('seo', $product->seo()->lockForUpdate()->first());
            }
            $updates = $seoUpdates = $before = [];
            try {
                foreach (array_values(self::COLUMNS) as $index => [$field, $max]) {
                    $raw = $row[$index + 3];
                    if (trim($raw) === '') {
                        continue;
                    }
                    $old = str_starts_with($field, 'seo.')
                        ? $product->seo?->getAttribute(substr($field, 4)) : $product->getAttribute($field);
                    $old = $old === null ? null : (string) $old;
                    if ($raw === $old || str_replace("\r\n", "\n", $raw) === str_replace("\r\n", "\n", (string) $old)) {
                        continue;
                    }
                    $value = $this->parse($field, $raw, $max);
                    if ($old === $value) {
                        continue;
                    }
                    $before[$field] = $old;
                    if (str_starts_with($field, 'seo.')) {
                        $seoUpdates[substr($field, 4)] = $value;
                    } else {
                        $updates[$field] = $value;
                    }
                }
            } catch (\InvalidArgumentException $exception) {
                $errors[$line] = $exception->getMessage();

                continue;
            }
            if ($updates === [] && $seoUpdates === []) {
                $skipped++;

                continue;
            }
            $operations[$line] = ['id' => $product->id, 'name' => $product->name, 'before' => $before,
                'product' => $updates, 'seo' => $seoUpdates];
            $changed++;
        }
        if ($total === 0) {
            throw ValidationException::withMessages(['file' => 'Bảng tính không có dòng sản phẩm.']);
        }
        ksort($errors);

        return ['total' => $total, 'changed' => $changed, 'skipped' => $skipped, 'errors' => $errors,
            'operations' => $operations, 'fingerprint' => hash('sha256', json_encode([$operations, $errors], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))];
    }

    public function apply(string $path, string $expectedFingerprint, User $actor): array
    {
        abort_unless($actor->canPerform('catalog_bulk'), 403);

        return DB::transaction(function () use ($path, $expectedFingerprint): array {
            $preview = $this->preview($path, true);
            if ($preview['errors'] !== [] || ! hash_equals($expectedFingerprint, $preview['fingerprint'])) {
                throw ValidationException::withMessages(['file' => 'Bảng tính có lỗi hoặc dữ liệu đã đổi sau khi xem trước.']);
            }
            foreach ($preview['operations'] as $operation) {
                $product = Product::query()->findOrFail($operation['id']);
                if ($operation['product'] !== []) {
                    $product->update($operation['product']);
                }
                if ($operation['seo'] !== []) {
                    $product->seo()->updateOrCreate([], $operation['seo']);
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
            fputcsv($out, ['Dòng', 'Sản phẩm', 'Kết quả', 'Cột thay đổi / lỗi'], ',', '"', '');
            foreach ($preview['operations'] as $line => $operation) {
                $columns = array_keys($operation['before']);
                fputcsv($out, [$line, $this->safeCsv($operation['name']), 'Sẽ cập nhật', implode(', ', $columns)], ',', '"', '');
            }
            foreach ($preview['errors'] as $line => $error) {
                fputcsv($out, [$line, '', 'Lỗi', $this->safeCsv($error)], ',', '"', '');
            }
            fclose($out);
        }, 'pts-ket-qua-noi-dung-seo.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsv(string $text): string
    {
        return preg_match('/^[=+@-]/', $text) ? "'".$text : $text;
    }

    private function parse(string $field, string $raw, int $max): ?string
    {
        $raw = trim($raw);
        if (mb_strtoupper($raw) === 'XÓA') {
            if ($field === 'category_id') {
                throw new \InvalidArgumentException('Không được xóa danh mục sản phẩm.');
            }

            return null;
        }
        if (in_array($field, ['category_id', 'brand_id'], true)) {
            if (! ctype_digit($raw) || (int) $raw < 1 || ! DB::table($field === 'category_id' ? 'categories' : 'brands')->where('id', (int) $raw)->exists()) {
                throw new \InvalidArgumentException('ID danh mục hoặc thương hiệu không tồn tại.');
            }

            return $raw;
        }
        if ($field === 'power_watts') {
            if (! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $raw)) {
                throw new \InvalidArgumentException('Công suất cần là số không âm, tối đa 2 chữ số thập phân.');
            }

            return number_format((float) $raw, 2, '.', '');
        }
        if (! mb_check_encoding($raw, 'UTF-8') || mb_strlen($raw) > $max || preg_match('/<[^>]*>/', $raw) || preg_match('/^[=+@]/', $raw)) {
            throw new \InvalidArgumentException('Nội dung không hợp lệ, quá dài hoặc có mã HTML/công thức.');
        }

        return $raw;
    }

    /** @return array<int,list<string>> */
    private function readRows(string $path): array
    {
        if (! is_file($path) || filesize($path) > 5 * 1024 * 1024 || filesize($path) === 0) {
            throw ValidationException::withMessages(['file' => 'Bảng tính không tồn tại hoặc lớn hơn 5 MB.']);
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            throw ValidationException::withMessages(['file' => 'Chỉ nhận XLSX hoặc CSV UTF-8.']);
        }
        $rows = [];
        if ($extension === 'csv') {
            $in = fopen($path, 'r');
            try {
                while (($row = fgetcsv($in, 0, ',', '"', '')) !== false) {
                    $rows[] = $this->strings($row, count($rows) + 1);
                    if (count($rows) > 5001) {
                        break;
                    }
                }
            } finally {
                fclose($in);
            }
        } else {
            $zip = new ZipArchive;
            if ($zip->open($path) !== true) {
                throw ValidationException::withMessages(['file' => 'Tệp XLSX không hợp lệ.']);
            }
            $bytes = 0;
            $entries = $zip->numFiles;
            for ($index = 0; $index < $entries; $index++) {
                $stat = $zip->statIndex($index);
                $bytes += (int) ($stat['size'] ?? 0);
            }
            $zip->close();
            if ($bytes > 50 * 1024 * 1024 || $entries > 300) {
                throw ValidationException::withMessages(['file' => 'XLSX giải nén quá lớn.']);
            }
            $reader = new Reader;
            try {
                $reader->open($path);
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $rows[] = $this->strings($row->toArray(), count($rows) + 1);
                        if (count($rows) > 5001) {
                            break;
                        }
                    }
                    break;
                }
            } catch (ValidationException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages(['file' => 'Không đọc được XLSX.']);
            } finally {
                $reader->close();
            }
        }
        if (count($rows) > 5001) {
            throw ValidationException::withMessages(['file' => 'Tối đa 5.000 dòng sản phẩm.']);
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'Bảng tính trống.']);
        }
        $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]);

        return $rows;
    }

    /** @param array<mixed> $row @return list<string> */
    private function strings(array $row, int $line): array
    {
        $values = [];
        foreach ($row as $value) {
            if (! is_null($value) && ! is_scalar($value)) {
                throw ValidationException::withMessages(['file' => "Dòng {$line} có kiểu ô không hỗ trợ."]);
            }
            $value = (string) $value;
            if (! mb_check_encoding($value, 'UTF-8')) {
                throw ValidationException::withMessages(['file' => "Dòng {$line} không phải UTF-8."]);
            }
            $values[] = $value;
        }

        return array_pad($values, count(self::headers()), '');
    }
}
