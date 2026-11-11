<?php

namespace App\Domain\Commerce;

use App\Domain\Shipping\NhatTin\NtlStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
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
        $sheet = $book->getActiveSheet()->setTitle('Đơn hàng');
        $titles = ['STT', 'Mã đơn', 'Ngày đặt', 'Tình trạng', 'Thanh toán', 'Hình thức', 'Khách hàng', 'Điện thoại', 'Địa chỉ giao', 'Sản phẩm',
            'Tạm tính', 'Phí giao', 'Tổng tiền', 'Vận đơn', 'Ghi chú nội bộ'];
        $last = chr(64 + count($titles));
        $row = $this->heading($sheet, 'DANH SÁCH ĐƠN HÀNG · xuất lúc '.now()->format('H:i d/m/Y'), $last);
        $this->headerRow($sheet, $row, $titles);
        $headerRow = $row;
        $first = ++$row;
        $number = 0;
        foreach ($orders->clone()->with(['province', 'ward', 'paymentMethod', 'items', 'latestShipment'])->reorder('id', 'desc')->cursor() as $order) {
            $items = $order->items->map(fn ($i) => $i->getAttribute('quantity').' × '.$i->getAttribute('name'))->implode("\n");
            $shipment = $order->latestShipment;
            $this->cells($sheet, $row++, [++$number, (string) $order->code, $order->created_at?->format('d/m/Y H:i'), OrderResource::STATUSES[$order->status] ?? $order->status,
                OrderResource::PAYMENT[$order->payment_status] ?? $order->payment_status, (string) ($order->paymentMethod->name ?? ''), (string) $order->customer_name,
                (string) $order->phone, self::address($order), $items, (float) $order->subtotal, (float) $order->shipping_fee, (float) $order->total,
                $shipment ? trim(($shipment->bill_code ?? $shipment->ref_code).' · '.(NtlStatus::LABELS[$shipment->status] ?? $shipment->status)) : '', (string) $order->notes]);
        }
        $end = max($first, $row - 1);
        $body = $sheet->getStyle('A'.$first.':'.$last.$end);
        $body->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $body->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D8DFEA');
        for ($r = $first + 1; $r <= $end; $r += 2) {
            $sheet->getStyle('A'.$r.':'.$last.$r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F6F8FB');
        }
        $sheet->getStyle('A'.$first.':A'.$end)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K'.$first.':M'.$end)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('B'.$first.':B'.$end)->getFont()->setBold(true);
        $this->cells($sheet, $row, ['', '', '', '', '', '', '', '', '', 'Tổng cộng '.$number.' đơn', '=SUM(K'.$first.':K'.$end.')', '=SUM(L'.$first.':L'.$end.')', '=SUM(M'.$first.':M'.$end.')'], true);
        $total = $sheet->getStyle('J'.$row.':M'.$row);
        $total->getFont()->setBold(true);
        $total->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('09285E');
        $sheet->getStyle('K'.$row.':M'.$row)->getNumberFormat()->setFormatCode('#,##0');
        foreach (['A' => 5, 'B' => 13, 'C' => 16, 'D' => 13, 'E' => 15, 'F' => 18, 'G' => 22, 'H' => 13, 'I' => 38, 'J' => 42, 'K' => 13, 'L' => 11, 'M' => 14, 'N' => 24, 'O' => 28] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getRowDimension($headerRow)->setRowHeight(22);
        $sheet->setAutoFilter('A'.$headerRow.':'.$last.$end);
        $sheet->freezePane('C'.$first);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);

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
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('09285E');
        $sheet->getStyle('A2:A4')->getFont()->getColor()->setRGB('5D6B84');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('B5821A');

        return 7;
    }

    /** @param list<string> $titles */
    private function headerRow(Worksheet $sheet, int $row, array $titles): void
    {
        $this->cells($sheet, $row, $titles);
        $style = $sheet->getStyle('A'.$row.':'.chr(64 + count($titles)).$row);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2A5B');
        $style->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
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
