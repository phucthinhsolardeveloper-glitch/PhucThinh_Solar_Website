<?php

namespace App\Domain\Catalog\Bulk;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/** Reads an uploaded XLSX, XLS or CSV into rows keyed by column, plus the hidden snapshot taken at export. */
final class BulkReader
{
    public const MAX_ROWS = 5000;

    private const MAX_BYTES = 10 * 1024 * 1024;

    private const MIMES = [
        'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
    ];

    /** @return array{rows: list<array<string, string>>, baseline: array<int, array<string, string>>} */
    public function read(string $path, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! is_file($path) || filesize($path) === 0 || filesize($path) > self::MAX_BYTES) {
            self::fail('Tệp phải có dung lượng từ 1 byte đến 10 MB.');
        }
        if (! isset(self::MIMES[$extension])) {
            self::fail('Chỉ nhận tệp .xlsx, .xls hoặc .csv.');
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (! in_array($mime, self::MIMES[$extension], true)) {
            self::fail('Nội dung tệp không khớp phần mở rộng .'.$extension.'.');
        }
        [$grid, $baseline] = $extension === 'csv' ? [$this->csv($path), []] : $this->spreadsheet($path);
        if ($grid === []) {
            self::fail('Tệp không có dữ liệu.');
        }

        return ['rows' => $this->rows($grid), 'baseline' => $baseline];
    }

    /** @return list<list<mixed>> */
    private function csv(string $path): array
    {
        $content = (string) file_get_contents($path);
        if (! mb_check_encoding($content, 'UTF-8')) {
            self::fail('Tệp CSV phải lưu dạng UTF-8. Trong Excel chọn “CSV UTF-8”, hoặc dùng XLSX.');
        }
        $handle = fopen($path, 'rb');
        $first = (string) fgets($handle);
        rewind($handle);
        $counts = [',' => substr_count($first, ','), ';' => substr_count($first, ';'), "\t" => substr_count($first, "\t")];
        arsort($counts);
        $grid = [];
        while (($cells = fgetcsv($handle, 0, (string) array_key_first($counts), '"', '')) !== false) {
            $grid[] = $cells;
        }
        fclose($handle);

        return $grid;
    }

    /** @return array{0: list<list<mixed>>, 1: array<int, array<string, string>>} */
    private function spreadsheet(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            @ini_set('memory_limit', '768M');
            $book = $reader->load($path);
            $grid = self::grid($book->getSheetByName('SanPham') ?? $book->getSheet(0));
            $baseline = [];
            $system = $book->getSheetByName('_HeThong');
            if ($system) {
                $cells = self::grid($system);
                $keys = array_map(fn ($key) => trim((string) $key), (array) array_shift($cells));
                foreach ($cells as $row) {
                    $values = [];
                    foreach ($keys as $index => $key) {
                        if ($key !== '') {
                            $values[$key] = self::cell($row[$index] ?? null, false);
                        }
                    }
                    if ((int) ($values['id'] ?? 0) > 0) {
                        $baseline[(int) $values['id']] = $values;
                    }
                }
            }
            $book->disconnectWorksheets();

            return [$grid, $baseline];
        } catch (Throwable) {
            self::fail('Không đọc được tệp Excel. Hãy mở và lưu lại, hoặc xuất mẫu mới từ Admin.');
        }
    }

    /** Only the cells holding data; the template formats thousands of empty rows. @return list<list<mixed>> */
    private static function grid(Worksheet $sheet): array
    {
        return $sheet->rangeToArray('A1:'.$sheet->getHighestDataColumn().$sheet->getHighestDataRow(), null, false, false, false);
    }

    /** @param list<list<mixed>> $grid @return list<array<string, string>> */
    private function rows(array $grid): array
    {
        $map = BulkColumns::headerMap();
        $positions = [];
        $errors = [];
        foreach ($grid[0] as $index => $header) {
            $normal = BulkColumns::header($header);
            if ($normal === '') {
                continue;
            }
            $key = $map[$normal] ?? null;
            if ($key === null) {
                $errors[] = 'Cột không được hỗ trợ: '.trim((string) $header);
            } elseif (isset($positions[$key])) {
                $errors[] = 'Tiêu đề cột bị lặp: '.trim((string) $header);
            } else {
                $positions[$key] = $index;
            }
        }
        if (! isset($positions['id']) && ! isset($positions['code'])) {
            $errors[] = 'Tệp phải có cột ID sản phẩm hoặc Mã sản phẩm.';
        }
        if (count($grid) - 1 > self::MAX_ROWS) {
            $errors[] = 'Mỗi lần xử lý tối đa '.number_format(self::MAX_ROWS, 0, ',', '.').' dòng.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }
        $rows = [];
        foreach (array_slice($grid, 1) as $offset => $cells) {
            $row = ['_row' => (string) ($offset + 2)];
            $filled = false;
            foreach ($positions as $key => $index) {
                $row[$key] = self::cell($cells[$index] ?? null);
                $filled = $filled || $row[$key] !== '';
            }
            if ($filled) {
                $rows[] = $row;
            }
        }
        if ($rows === []) {
            self::fail('Tệp không có dòng sản phẩm.');
        }

        return $rows;
    }

    private static function cell(mixed $value, bool $unguard = true): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_float($value) && floor($value) === $value && abs($value) < 1e15) {
            return (string) (int) $value;
        }
        $value = trim((string) $value);

        // The apostrophe the export puts in front of =, +, - and @ is not part of the value.
        return $unguard && strlen($value) > 1 && $value[0] === "'" && in_array($value[1], ['=', '+', '-', '@'], true) ? substr($value, 1) : $value;
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
