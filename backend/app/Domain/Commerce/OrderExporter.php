<?php

namespace App\Domain\Commerce;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Order lists and single orders as Excel files with the company heading. */
final class OrderExporter
{
    public function __construct(private readonly SiteSettings $settings) {}

    /** @param Builder<Order> $orders */
    public function list(Builder $orders): BinaryFileResponse
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('DonHang');
        $row = $this->heading($sheet, 'DANH SÁCH ĐƠN HÀNG', 'N');
        $this->headerRow($sheet, $row, ['STT', 'Mã đơn hàng', 'Ngày đặt', 'Tình trạng', 'Thanh toán', 'Hình thức thanh toán', 'Họ tên', 'Điện thoại', 'Email', 'Địa chỉ', 'Tạm tính', 'Phí vận chuyển', 'Tổng giá trị', 'Ghi chú']);
        $first = ++$row;
        $number = 0;
        foreach ($orders->clone()->with(['province', 'ward', 'paymentMethod'])->reorder('id', 'desc')->cursor() as $order) {
            $this->cells($sheet, $row++, [++$number, (string) $order->code, $order->created_at?->format('H:i d/m/Y'), OrderResource::STATUSES[$order->status] ?? $order->status,
                OrderResource::PAYMENT[$order->payment_status] ?? $order->payment_status, (string) ($order->paymentMethod->name ?? ''), (string) $order->customer_name, (string) $order->phone,
                (string) $order->email, self::address($order), (float) $order->subtotal, (float) $order->shipping_fee, (float) $order->total, (string) $order->notes]);
        }
        $last = max($first, $row - 1);
        $sheet->getStyle('K'.$first.':M'.$last)->getNumberFormat()->setFormatCode('#,##0');
        $this->cells($sheet, $row, ['', '', '', '', '', '', '', '', '', 'Tổng cộng', '=SUM(K'.$first.':K'.$last.')', '=SUM(L'.$first.':L'.$last.')', '=SUM(M'.$first.':M'.$last.')'], true);
        $sheet->getStyle('J'.$row.':M'.$row)->getFont()->setBold(true);
        $sheet->getStyle('K'.$row.':M'.$row)->getNumberFormat()->setFormatCode('#,##0');
        foreach (['A' => 6, 'B' => 16, 'C' => 18, 'D' => 14, 'E' => 16, 'F' => 22, 'G' => 24, 'H' => 14, 'I' => 26, 'J' => 44, 'K' => 14, 'L' => 14, 'M' => 16, 'N' => 30] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A'.$first);

        return $this->send($book, 'don-hang-'.date('Ymd-His'));
    }

    public function single(Order $order): BinaryFileResponse
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('DonHang');
        $row = $this->heading($sheet, 'ĐƠN HÀNG '.$order->code, 'F');
        foreach ([['Ngày đặt', $order->created_at?->format('H:i d/m/Y')], ['Tình trạng', OrderResource::STATUSES[$order->status] ?? $order->status],
            ['Thanh toán', OrderResource::PAYMENT[$order->payment_status] ?? $order->payment_status], ['Khách hàng', $order->customer_name], ['Điện thoại', $order->phone],
            ['Email', $order->email], ['Địa chỉ', self::address($order)], ['Yêu cầu', $order->requirements]] as [$label, $value]) {
            $this->cells($sheet, $row++, [$label, (string) $value]);
            $sheet->getStyle('A'.($row - 1))->getFont()->setBold(true);
        }
        $this->headerRow($sheet, ++$row, ['STT', 'Sản phẩm', 'Phân loại', 'Đơn giá', 'Số lượng', 'Thành tiền']);
        $first = ++$row;
        foreach ($order->items as $index => $item) {
            $price = (float) ($item->getAttribute('sale_price') ?: $item->getAttribute('regular_price'));
            $quantity = (int) $item->getAttribute('quantity');
            $this->cells($sheet, $row++, [$index + 1, (string) $item->getAttribute('name'), trim($item->getAttribute('color').' '.$item->getAttribute('size')), $price, $quantity, $price * $quantity]);
        }
        foreach ([['Tạm tính', (float) $order->subtotal], ['Phí vận chuyển', (float) $order->shipping_fee], ['Tổng giá trị', (float) $order->total]] as [$label, $value]) {
            $this->cells($sheet, $row++, ['', '', '', '', $label, $value]);
            $sheet->getStyle('E'.($row - 1).':F'.($row - 1))->getFont()->setBold(true);
        }
        $sheet->getStyle('D'.$first.':F'.($row - 1))->getNumberFormat()->setFormatCode('#,##0');
        foreach (['A' => 14, 'B' => 48, 'C' => 16, 'D' => 14, 'E' => 16, 'F' => 16] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return $this->send($book, 'don-hang-'.$order->code);
    }

    /** Checkout already writes ward and province into `address`; older orders may hold the street only, so add each part once. */
    public static function address(Order $order): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $order->address)), fn ($part) => $part !== ''));
        $seen = array_map(fn ($part) => mb_strtolower($part), $parts);
        foreach ([(string) ($order->ward->name ?? ''), (string) ($order->province->name ?? '')] as $name) {
            $name = trim($name);
            if ($name !== '' && ! in_array(mb_strtolower($name), $seen, true)) {
                $parts[] = $name;
                $seen[] = mb_strtolower($name);
            }
        }

        return implode(', ', $parts);
    }

    /** @return int the row after the heading block */
    private function heading(Worksheet $sheet, string $title, string $lastColumn): int
    {
        $lines = [$this->settings->siteName(), 'Hotline: '.$this->settings->get('hotline', ''), 'Email: '.$this->settings->get('email', ''), 'Địa chỉ: '.$this->settings->get('address', ''), $title];
        foreach ($lines as $index => $text) {
            $sheet->setCellValueExplicit('A'.($index + 1), (string) $text, DataType::TYPE_STRING);
            $sheet->mergeCells('A'.($index + 1).':'.$lastColumn.($index + 1));
        }
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);

        return 7;
    }

    /** @param list<string> $titles */
    private function headerRow(Worksheet $sheet, int $row, array $titles): void
    {
        $this->cells($sheet, $row, $titles);
        $style = $sheet->getStyle('A'.$row.':'.chr(64 + count($titles)).$row);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2A5B');
        $style->getAlignment()->setWrapText(true);
    }

    /** @param list<mixed> $values */
    private function cells(Worksheet $sheet, int $row, array $values, bool $formulas = false): void
    {
        foreach ($values as $index => $value) {
            $cell = chr(65 + $index).$row;
            if ($formulas && is_string($value) && str_starts_with($value, '=')) {
                $sheet->setCellValue($cell, $value);
            } elseif (is_int($value) || is_float($value)) {
                $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_NUMERIC);
            } else {
                $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
            }
        }
    }

    private function send(Spreadsheet $book, string $name): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'pts-orders-');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return response()->download($path, $name.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend();
    }
}
