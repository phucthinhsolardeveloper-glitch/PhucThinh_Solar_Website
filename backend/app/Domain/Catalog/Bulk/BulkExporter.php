<?php

namespace App\Domain\Catalog\Bulk;

use App\Filament\Support\Options;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Writes the product workbook: data, guide, pick lists, and a hidden copy used to detect later edits. */
final class BulkExporter
{
    /** An Excel cell holds at most this many characters. An empty cell keeps the stored value on import. */
    private const CELL_LIMIT = 32000;

    private const LAST_ROW = BulkReader::MAX_ROWS + 1;

    public function csv(BulkCatalog $catalog, string $name): StreamedResponse
    {
        $columns = BulkColumns::exported();

        return response()->streamDownload(function () use ($catalog, $columns): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_column($columns, 'label'), ',', '"', '');
            foreach ($catalog->values as $values) {
                $row = [];
                foreach (array_keys($columns) as $key) {
                    $value = $values[$key] ?? '';
                    $row[] = $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true) ? "'".$value : $value;
                }
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, $name.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function xlsx(BulkCatalog $catalog, string $name): BinaryFileResponse
    {
        @ini_set('memory_limit', '768M');
        $book = new Spreadsheet;
        $book->getProperties()->setCreator('Phúc Thịnh Solar')->setTitle('Quản lý sản phẩm');
        $sheet = $book->getActiveSheet()->setTitle('SanPham');
        $columns = BulkColumns::exported();
        $letters = [];
        foreach (array_keys($columns) as $index => $key) {
            $letters[$key] = Coordinate::stringFromColumnIndex($index + 1);
        }
        $this->header($sheet, $columns, $letters);
        $this->data($sheet, $catalog, $columns, $letters);
        $ranges = $this->reference($book, $catalog);
        $this->pickLists($sheet, $letters, $ranges);
        $this->protect($sheet, $letters);
        $this->guide($book);
        $this->snapshot($book, $catalog, $columns);
        $book->setActiveSheetIndex(0);

        $path = tempnam(sys_get_temp_dir(), 'pts-products-');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return response()->download($path, $name.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend();
    }

    private function header(Worksheet $sheet, array $columns, array $letters): void
    {
        $last = end($letters);
        foreach ($columns as $key => $column) {
            $sheet->setCellValueExplicit($letters[$key].'1', $column['label'], DataType::TYPE_STRING);
            $sheet->getColumnDimension($letters[$key])->setWidth(! empty($column['wide']) ? 45 : 18);
        }
        $header = $sheet->getStyle('A1:'.$last.'1');
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2A5B');
        $header->getAlignment()->setWrapText(true);
        foreach ($columns as $key => $column) {
            if (! empty($column['required'])) {
                $style = $sheet->getStyle($letters[$key].'1');
                $style->getFont()->getColor()->setRGB('1F2937');
                $style->getFill()->getStartColor()->setRGB('FDE68A');
            }
        }
        $sheet->freezePane('E2');
        $sheet->setAutoFilter('A1:'.$last.'1');
    }

    private function data(Worksheet $sheet, BulkCatalog $catalog, array $columns, array $letters): void
    {
        $row = 2;
        foreach ($catalog->values as $values) {
            foreach ($columns as $key => $column) {
                $value = $values[$key] ?? '';
                if ($value === '' || mb_strlen($value, 'UTF-8') > self::CELL_LIMIT) {
                    continue;
                }
                $numeric = in_array($column['kind'], ['money', 'decimal', 'integer', 'flag'], true) || $key === 'id';
                $sheet->setCellValueExplicit($letters[$key].$row, $numeric ? (float) $value : $value, $numeric ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
            }
            $row++;
        }
    }

    /** @return array<string, string> kind of list => formula of its cells */
    private function reference(Spreadsheet $book, BulkCatalog $catalog): array
    {
        $sheet = $book->createSheet()->setTitle('DanhMuc');
        foreach (['A1' => 'ID danh mục', 'B1' => 'Danh mục', 'D1' => 'ID thương hiệu', 'E1' => 'Thương hiệu', 'G1' => 'Kiểu trang', 'H1' => 'Mô tả'] as $cell => $title) {
            $sheet->setCellValue($cell, $title);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $fill = function (array $items, string $idColumn, string $nameColumn, bool $idIsText = false) use ($sheet): int {
            $row = 2;
            foreach ($items as $id => $name) {
                $sheet->setCellValueExplicit($idColumn.$row, $id, $idIsText ? DataType::TYPE_STRING : DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit($nameColumn.$row, $name, DataType::TYPE_STRING);
                $row++;
            }

            return max(2, $row - 1);
        };
        $lastCategory = $fill($catalog->categoryPaths, 'A', 'B');
        $lastBrand = $fill($catalog->brands, 'D', 'E');
        $lastProfile = $fill(Options::pdpProfiles(), 'G', 'H', true);
        foreach (['B' => 60, 'E' => 30, 'G' => 22, 'H' => 40] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return ['category' => 'DanhMuc!$B$2:$B$'.$lastCategory, 'brand' => 'DanhMuc!$E$2:$E$'.$lastBrand, 'pdp_profile' => 'DanhMuc!$G$2:$G$'.$lastProfile];
    }

    private function pickLists(Worksheet $sheet, array $letters, array $ranges): void
    {
        $lists = $ranges + ['action' => '"create,update,archive,skip"', 'is_visible' => '"1,0"', 'is_bestseller' => '"1,0"', 'quote_only' => '"1,0"'];
        foreach ($lists as $key => $formula) {
            $rule = new DataValidation;
            $rule->setType(DataValidation::TYPE_LIST)->setErrorStyle(DataValidation::STYLE_STOP)->setAllowBlank(true)
                ->setShowDropDown(true)->setShowErrorMessage(true)->setErrorTitle('Giá trị không hợp lệ')
                ->setError('Hãy chọn một giá trị trong danh sách.')->setFormula1($formula);
            $sheet->setDataValidation($letters[$key].'2:'.$letters[$key].self::LAST_ROW, $rule);
        }
    }

    private function protect(Worksheet $sheet, array $letters): void
    {
        $last = end($letters);
        $sheet->getStyle('A2:'.$last.self::LAST_ROW)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
        $id = $sheet->getStyle($letters['id'].'2:'.$letters['id'].self::LAST_ROW);
        $id->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
        $id->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9ECEF');
        $sheet->getProtection()->setSheet(true)->setSort(false)->setAutoFilter(false)->setFormatCells(false)->setFormatColumns(false)->setFormatRows(false);
    }

    private function guide(Spreadsheet $book): void
    {
        $sheet = $book->createSheet()->setTitle('HuongDan');
        $rows = [
            ['HƯỚNG DẪN NHANH', 'Chỉ nhập những ô cần thiết; không cần điền hết các cột.'],
            ['Tạo sản phẩm mới', 'Bắt buộc Mã, Tên và Danh mục (cột vàng). Để trống cột ID. Đường dẫn tự tạo từ tên.'],
            ['Sửa sản phẩm', 'Giữ nguyên ID hoặc Mã. Chỉ sửa ô cần đổi; ô trống giữ nguyên dữ liệu trên website.'],
            ['Xóa một ô', 'Nhập đúng '.BulkColumns::CLEAR.'. Không dùng cho Mã, Tên, Đường dẫn, Danh mục và các cột 1/0.'],
            ['Cột Thao tác', 'Để trống là tự nhận biết. create = tạo mới; update = cập nhật; archive = ẩn khỏi website, không xóa; skip = bỏ qua dòng.'],
            ['Giá', 'Nhập số, có thể viết 1.235.000 hoặc 1235000. Giá khuyến mãi không được lớn hơn giá gốc.'],
            ['Cột 1/0', 'Hiển thị, Bán chạy, Liên hệ báo giá: 1 = bật, 0 = tắt. Sản phẩm mới để trống sẽ hiển thị.'],
            ['Danh mục, thương hiệu', 'Chọn trong danh sách hoặc gõ đúng tên. Thương hiệu mới sẽ được hỏi tạo, ghép hay bỏ trống ở màn xem trước.'],
            ['Điểm nổi bật', 'Mỗi dòng: Tiêu đề | Giải thích. Tối đa 6 dòng.'],
            ['Mô tả dài / FAQ', "## Tiêu đề\n- Danh sách\n[faq] Câu hỏi | Trả lời\n![Chú thích](ten-anh.jpg)\nCác khối cách nhau một dòng trống. [ẩn] để không hiện khối mô tả."],
            ['Thông số khác', 'Mỗi dòng: Mã | Tên | Giá trị, ví dụ ac_power | Công suất AC định mức | 10 kW. Có dấu | trong giá trị thì viết \\|.'],
            ['Ảnh', 'Ghi tên tệp đã tải lên, không ghi đường dẫn. Thư viện ảnh mỗi dòng một tên. Sai tên sẽ được gợi ý tên gần đúng.'],
            ['Ảnh Google Shopping', 'Ảnh nền sạch, không chữ, không logo, từ 500×500 px trở lên.'],
            ['Xung đột', 'Nếu website đã được sửa sau lúc xuất tệp, màn xem trước báo từng ô để chọn có ghi đè hay không.'],
            ['Quy trình', 'Xuất tệp mới nhất → sửa → tải lên → đọc màn xem trước → áp dụng. Còn lỗi thì không dòng nào được ghi.'],
            ['Giới hạn', 'Tối đa '.number_format(BulkReader::MAX_ROWS, 0, ',', '.').' dòng và 10 MB mỗi lần. Nên dùng XLSX.'],
            ['Không nên', 'Không đổi tiêu đề cột, không đổi ID, không sao chép ID sang sản phẩm khác, không nhập công thức.'],
        ];
        $sheet->fromArray($rows);
        $sheet->getColumnDimension('A')->setWidth(26);
        $sheet->getColumnDimension('B')->setWidth(100);
        $sheet->getStyle('A1:B'.count($rows))->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->getStyle('A1:A'.count($rows))->getFont()->setBold(true);
    }

    private function snapshot(Spreadsheet $book, BulkCatalog $catalog, array $columns): void
    {
        $sheet = $book->createSheet()->setTitle('_HeThong');
        $keys = array_values(array_diff(array_keys($columns), ['action']));
        foreach ($keys as $index => $key) {
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($index + 1).'1', $key, DataType::TYPE_STRING);
        }
        $row = 2;
        foreach ($catalog->values as $values) {
            foreach ($keys as $index => $key) {
                if (($values[$key] ?? '') !== '' && mb_strlen($values[$key], 'UTF-8') <= self::CELL_LIMIT) {
                    $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($index + 1).$row, $values[$key], DataType::TYPE_STRING);
                }
            }
            $row++;
        }
        $sheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
        $sheet->getProtection()->setSheet(true);
    }
}
