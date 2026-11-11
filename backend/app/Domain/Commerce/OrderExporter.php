<?php

namespace App\Domain\Commerce;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Shipping\NhatTin\NtlStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Mail\MailBrand;
use App\Models\Order;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
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

    /**
     * One order as a printable sheet laid out like the printed "Phiếu đơn hàng": logo and company block, the order
     * code, customer and delivery boxes, the item table, totals and signatures; A4 portrait, one page wide.
     * Numbers stay real numbers so staff can still edit them in Excel.
     */
    public function single(Order $order): BinaryFileResponse
    {
        $order->loadMissing(['items', 'province', 'ward', 'paymentMethod']);
        $navy = '09285E';
        $gold = 'B5821A';
        $muted = '5D6B84';
        $line = 'D8DFEA';
        $book = new Spreadsheet;
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $sheet = $book->getActiveSheet()->setTitle('Phiếu '.$order->code);
        $sheet->setShowGridlines(false);
        foreach (['A' => 5, 'B' => 42, 'C' => 15, 'D' => 8, 'E' => 22] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $brand = app(MailBrand::class)->data();

        // Company (left) and document (right).
        $logo = $this->logoFile($brand['logo']);
        $row = 1;
        if ($logo !== '') {
            $drawing = new Drawing;
            $drawing->setPath($logo)->setHeight(40)->setCoordinates('A1')->setOffsetX(4)->setOffsetY(4)->setWorksheet($sheet);
            $sheet->getRowDimension(1)->setRowHeight(36);
            $row = 2;
        }
        $company = [[$this->settings->siteName(), true, $navy, 10], [$brand['address'], false, $muted, 9],
            [trim(($brand['hotline'] !== '' ? 'Hotline: '.$brand['hotline'] : '').($brand['email'] !== '' ? ' · '.$brand['email'] : ''), ' ·'), false, $muted, 9]];
        foreach ($company as $i => [$text, $bold, $color, $size]) {
            $r = $row + $i;
            $sheet->mergeCells('A'.$r.':C'.$r);
            $sheet->setCellValueExplicit('A'.$r, (string) $text, DataType::TYPE_STRING);
            $sheet->getStyle('A'.$r)->getFont()->setBold($bold)->setSize($size)->getColor()->setRGB($color);
        }
        $doc = [['PHIẾU ĐƠN HÀNG', 15, $navy], [(string) $order->code, 13, $gold], ['Ngày đặt: '.$order->created_at?->format('H:i d/m/Y'), 9, $muted],
            [(OrderResource::STATUSES[$order->status] ?? $order->status).' · '.(OrderResource::PAYMENT[$order->payment_status] ?? $order->payment_status), 9, $muted]];
        foreach ($doc as $i => [$text, $size, $color]) {
            $r = $i + 1;
            $sheet->mergeCells('D'.$r.':E'.$r);
            $sheet->setCellValueExplicit('D'.$r, $text, DataType::TYPE_STRING);
            $sheet->getStyle('D'.$r)->getFont()->setBold($size > 10)->setSize($size)->getColor()->setRGB($color);
            $sheet->getStyle('D'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        $row = max($row + count($company), count($doc) + 1);
        $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK)->getColor()->setRGB($navy);
        $row++;

        // Customer and delivery boxes.
        $boxes = [
            ['A', 'B', 'Khách hàng', [['Họ tên', (string) $order->customer_name], ['Điện thoại', (string) $order->phone], ['Email', (string) $order->email]]],
            ['C', 'E', 'Giao hàng & thanh toán', [['Địa chỉ', self::address($order)], ['Hình thức', (string) ($order->paymentMethod->name ?? 'Chưa chọn')]]],
        ];
        $top = $row;
        $height = 0;
        foreach ($boxes as [$from, $to, $title, $lines]) {
            $r = $top;
            $sheet->mergeCells($from.$r.':'.$to.$r);
            $sheet->setCellValueExplicit($from.$r, $title, DataType::TYPE_STRING);
            $sheet->getStyle($from.$r)->getFont()->setBold(true)->getColor()->setRGB($navy);
            foreach (array_filter($lines, fn ($l) => trim($l[1]) !== '') as [$label, $value]) {
                $r++;
                $sheet->mergeCells($from.$r.':'.$to.$r);
                $text = new RichText;
                $text->createTextRun($label.': ')->getFont()?->setName('Arial')->setColor(new Color($muted))->setSize(9.5);
                $text->createTextRun($value)->getFont()?->setName('Arial')->setSize(10);
                $sheet->getCell($from.$r)->setValue($text);
                $sheet->getStyle($from.$r)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            }
            $height = max($height, $r - $top);
        }
        foreach ($boxes as [$from, $to]) {
            $box = $sheet->getStyle($from.$top.':'.$to.($top + $height));
            $box->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($line);
        }
        $sheet->getRowDimension($top + 1 + array_search('Địa chỉ', array_column($boxes[1][3], 0), true))->setRowHeight(30);
        $row = $top + $height + 2;

        // Items.
        $head = $row;
        foreach (['A' => 'STT', 'B' => 'Sản phẩm', 'C' => 'Đơn giá', 'D' => 'SL', 'E' => 'Thành tiền'] as $column => $title) {
            $sheet->setCellValueExplicit($column.$row, $title, DataType::TYPE_STRING);
        }
        $style = $sheet->getStyle('A'.$row.':E'.$row);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navy);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $first = ++$row;
        foreach ($order->items as $index => $item) {
            $price = (float) ($item->getAttribute('sale_price') ?: $item->getAttribute('regular_price'));
            $option = trim($item->getAttribute('color').' '.$item->getAttribute('size'));
            $sheet->setCellValueExplicit('A'.$row, $index + 1, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('B'.$row, (string) $item->getAttribute('name').($option !== '' ? "\n".$option : ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C'.$row, $price, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('D'.$row, (int) $item->getAttribute('quantity'), DataType::TYPE_NUMERIC);
            $sheet->setCellValue('E'.$row, '=C'.$row.'*D'.$row);
            if ($index % 2 === 1) {
                $sheet->getStyle('A'.$row.':E'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F6F8FB');
            }
            $row++;
        }
        $end = max($first, $row - 1);
        $items = $sheet->getStyle('A'.$first.':E'.$end);
        $items->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $items->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($line);
        $sheet->getStyle('A'.$first.':A'.$end)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D'.$head.':D'.$end)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C'.$head.':C'.$end)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E'.$head.':E'.$end)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E'.$first.':E'.$end)->getFont()->setBold(true);
        $sheet->getStyle('C'.$first.':E'.$end)->getNumberFormat()->setFormatCode('#,##0"đ"');
        $sheet->getStyle('D'.$first.':D'.$end)->getNumberFormat()->setFormatCode('0');

        // Totals.
        $row++;
        $discount = max(0.0, (float) $order->subtotal + (float) $order->shipping_fee - (float) $order->total);
        $totals = [['Tạm tính', (float) $order->subtotal], ['Phí vận chuyển', (float) $order->shipping_fee]];
        if ($discount > 0) {
            $totals[] = ['Giảm giá', -$discount];
        }
        $totals[] = ['Tổng cộng', (float) $order->total];
        foreach ($totals as $i => [$label, $value]) {
            $sheet->mergeCells('C'.$row.':D'.$row);
            $sheet->setCellValueExplicit('C'.$row, $label, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E'.$row, $value, DataType::TYPE_NUMERIC);
            $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0"đ"');
            $sheet->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            if ($label === 'Tổng cộng') {
                $sum = $sheet->getStyle('C'.$row.':E'.$row);
                $sum->getFont()->setBold(true)->setSize(12)->getColor()->setRGB($navy);
                $sum->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($navy);
                $sheet->getRowDimension($row)->setRowHeight(22);
            } else {
                $sheet->getStyle('C'.$row)->getFont()->getColor()->setRGB($muted);
            }
            if ($label === 'Phí vận chuyển' && $value <= 0) {
                $sheet->setCellValueExplicit('E'.$row, 'Chờ xác nhận', DataType::TYPE_STRING);
            }
            $row++;
        }

        if (trim((string) $order->requirements) !== '') {
            $row++;
            $sheet->mergeCells('A'.$row.':E'.$row);
            $sheet->setCellValueExplicit('A'.$row, 'Yêu cầu của khách: '.$order->requirements, DataType::TYPE_STRING);
            $note = $sheet->getStyle('A'.$row);
            $note->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FDF7E8');
            $note->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THICK)->getColor()->setRGB('D9A326');
            $note->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
            $sheet->getRowDimension($row)->setRowHeight(30);
            $row++;
        }

        // Signatures.
        $row += 2;
        foreach (['A' => ['B', 'Người lập phiếu'], 'C' => ['E', 'Người nhận hàng']] as $from => [$to, $who]) {
            $sheet->mergeCells($from.$row.':'.$to.$row);
            $sheet->setCellValueExplicit($from.$row, $who, DataType::TYPE_STRING);
            $sheet->getStyle($from.$row)->getFont()->setBold(true)->getColor()->setRGB($navy);
            $sheet->mergeCells($from.($row + 1).':'.$to.($row + 1));
            $sheet->setCellValueExplicit($from.($row + 1), '(Ký, ghi rõ họ tên)', DataType::TYPE_STRING);
            $sheet->getStyle($from.($row + 1))->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB($muted);
            $sheet->getStyle($from.$row.':'.$to.($row + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $row += 6;
        $sheet->mergeCells('A'.$row.':E'.$row);
        $sheet->setCellValueExplicit('A'.$row, $this->settings->siteName().' · Phiếu tạo ngày '.now()->format('H:i d/m/Y'), DataType::TYPE_STRING);
        $sheet->getStyle('A'.$row)->getFont()->setSize(8.5)->getColor()->setRGB($muted);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($line);

        $setup = $sheet->getPageSetup();
        $setup->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation(PageSetup::ORIENTATION_PORTRAIT)->setFitToWidth(1)->setFitToHeight(0);
        $setup->setPrintArea('A1:E'.$row);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
        $sheet->getPageSetup()->setHorizontalCentered(true);

        return $this->send($book, 'phieu-don-hang-'.$order->code);
    }

    /** The logo as a local file the sheet can embed, '' when there is none. */
    private function logoFile(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $file = $path !== '' ? app(ProductMediaUrls::class)->absolute(ltrim($path, '/')) : '';

        return $file !== '' && is_file($file) && preg_match('/\.(png|jpe?g)$/i', $file) ? $file : '';
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
