<?php

namespace App\Domain\Commerce;

use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * VietQR transfer codes built here, offline (NAPAS EMVCo payload with a CRC16 check). Making a code
 * sends nothing to any bank and proves no payment.
 */
final class VietQr
{
    /**
     * Banks that can receive a VietQR transfer (NAPAS BIN => [name, badge letters, badge colour]).
     *
     * The badge is drawn by us — letters on a colour close to the bank's own — so Admin and the
     * checkout page can tell banks apart at a glance without loading a logo from anyone's server.
     * It is a signpost, not the bank's trademark, and nothing here is sent to a bank.
     */
    public const BANKS = [
        '970436' => ['Vietcombank', 'VCB', '#0F7A3D'],
        '970415' => ['VietinBank', 'CTG', '#004A94'],
        '970418' => ['BIDV', 'BIDV', '#006A4E'],
        '970405' => ['Agribank', 'AGR', '#9E1B32'],
        '970407' => ['Techcombank', 'TCB', '#E4002B'],
        '970422' => ['MB Bank', 'MB', '#1F4E9C'],
        '970416' => ['ACB', 'ACB', '#004B8D'],
        '970432' => ['VPBank', 'VPB', '#00A562'],
        '970403' => ['Sacombank', 'STB', '#0066B3'],
        '970423' => ['TPBank', 'TPB', '#5B2A86'],
        '970441' => ['VIB', 'VIB', '#0057A8'],
        '970443' => ['SHB', 'SHB', '#1B4F9C'],
        '970437' => ['HDBank', 'HDB', '#ED1C24'],
        '970448' => ['OCB', 'OCB', '#008C44'],
        '970426' => ['MSB', 'MSB', '#D71920'],
        '970440' => ['SeABank', 'SEA', '#B01F24'],
        '970431' => ['Eximbank', 'EIB', '#1B4FA0'],
        '970449' => ['LPBank', 'LPB', '#8E1B3A'],
        '970428' => ['Nam A Bank', 'NAB', '#0072BC'],
        '970425' => ['ABBANK', 'ABB', '#0057A4'],
        '970409' => ['Bac A Bank', 'BAB', '#0B6E4F'],
        '970412' => ['PVcomBank', 'PVC', '#C8102E'],
        '970452' => ['KienlongBank', 'KLB', '#0E6EB8'],
        '970419' => ['NCB', 'NCB', '#1C4E9D'],
        '970427' => ['VietABank', 'VAB', '#0F6FB7'],
        '970438' => ['BaoViet Bank', 'BVB', '#0C5AA6'],
        '970400' => ['Saigonbank', 'SGB', '#1A7A3C'],
        '970430' => ['PG Bank', 'PGB', '#B8860B'],
        '970433' => ['Vietbank', 'VBB', '#0E7AC2'],
        '970429' => ['SCB', 'SCB', '#0A3D91'],
    ];

    /**
     * The badge of one bank: letters and colour, or null when the BIN is unknown.
     *
     * @return array{short: string, color: string}|null
     */
    public static function badge(string $bin): ?array
    {
        return isset(self::BANKS[$bin]) ? ['short' => self::BANKS[$bin][1], 'color' => self::BANKS[$bin][2]] : null;
    }

    /**
     * Picker options, BIN => a row with the badge beside the name.
     *
     * The styling is inline on purpose: Admin's stylesheet is built ahead of time and never sees
     * these strings, so utility classes would not exist and the badge would come out as bare text.
     *
     * @return array<string, string>
     */
    public static function options(string $search = ''): array
    {
        $search = trim(mb_strtolower($search));
        $options = [];
        foreach (self::BANKS as $bin => [$name, $short, $color]) {
            if ($search !== '' && ! str_contains(mb_strtolower($name.' '.$short.' '.$bin), $search)) {
                continue;
            }
            $options[$bin] = '<span style="display:inline-flex;align-items:center;gap:.5rem;line-height:1.5">'
                .'<span style="display:inline-flex;flex:none;align-items:center;justify-content:center;width:2.75rem;height:1.5rem;'
                .'border-radius:.375rem;font-size:10px;font-weight:700;letter-spacing:.04em;color:#fff;background:'.$color.'">'
                .e($short).'</span><span>'.e($name).'</span></span>';
        }

        return $options;
    }

    public static function payload(string $bin, string $account, int $amount, string $note): string
    {
        $field = fn (string $id, string $value) => $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
        $beneficiary = $field('00', $bin).$field('01', $account);
        $merchant = $field('00', 'A000000727').$field('01', $beneficiary).$field('02', 'QRIBFTTA');
        $body = $field('00', '01').$field('01', '12').$field('38', $merchant).$field('53', '704')
            .($amount > 0 ? $field('54', (string) $amount) : '').$field('58', 'VN').$field('62', $field('08', $note)).'6304';

        return $body.self::crc($body);
    }

    /** SVG picture of a code, as a data URI the page can show directly. */
    public static function svg(string $payload): string
    {
        $options = new QROptions(['outputType' => QROutputInterface::MARKUP_SVG, 'eccLevel' => QRCode::ECC_M, 'addQuietzone' => true,
            'quietzoneSize' => 2, 'outputBase64' => true, 'svgUseFillAttributes' => true, 'drawLightModules' => false]);

        return (new QRCode($options))->render($payload);
    }

    /** CRC-16/CCITT-FALSE, upper-case hex, as EMVCo requires. */
    public static function crc(string $data): string
    {
        $crc = 0xFFFF;
        foreach (str_split($data) as $char) {
            $crc ^= ord($char) << 8;
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
