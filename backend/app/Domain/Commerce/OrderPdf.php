<?php

namespace App\Domain\Commerce;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Mail\MailBrand;
use App\Models\Order;
use App\Support\SiteSettings;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

/**
 * The order sheet as a PDF file (one click, ready to send to the customer by Zalo or email). Same content as the
 * printable sheet, laid out with tables because the PDF engine has no flexbox; DejaVu Sans carries Vietnamese.
 */
final class OrderPdf
{
    public function __construct(private readonly SiteSettings $settings, private readonly ProductMediaUrls $urls) {}

    public function render(Order $order): string
    {
        $order->loadMissing(['items', 'province', 'ward', 'paymentMethod']);
        $html = view('order-pdf', ['order' => $order, 'settings' => $this->settings, 'brand' => app(MailBrand::class)->data(), 'logo' => $this->logo()])->render();
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('chroot', [base_path()]);
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return (string) $pdf->output();
    }

    public function download(Order $order): Response
    {
        return response($this->render($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="phieu-don-hang-'.$order->code.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Logo embedded as data (the PDF engine does not fetch URLs); '' when there is no PNG/JPEG logo on disk. */
    private function logo(): string
    {
        $url = app(MailBrand::class)->data()['logo'];
        $path = (string) parse_url($url, PHP_URL_PATH);
        $file = $path !== '' ? $this->urls->absolute(ltrim($path, '/')) : '';
        if ($file === '' || ! is_file($file) || ! preg_match('/\.(png|jpe?g)$/i', $file, $m)) {
            return '';
        }

        return 'data:image/'.(strtolower($m[1]) === 'png' ? 'png' : 'jpeg').';base64,'.base64_encode((string) file_get_contents($file));
    }
}
