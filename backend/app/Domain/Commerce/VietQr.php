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
    /** Bank identification numbers of common Vietnamese banks (NAPAS BIN => short name). */
    public const BANKS = [
        '970436' => 'Vietcombank', '970415' => 'VietinBank', '970418' => 'BIDV', '970405' => 'Agribank', '970407' => 'Techcombank',
        '970422' => 'MB Bank', '970416' => 'ACB', '970432' => 'VPBank', '970403' => 'Sacombank', '970423' => 'TPBank',
        '970441' => 'VIB', '970443' => 'SHB', '970437' => 'HDBank', '970448' => 'OCB', '970426' => 'MSB',
        '970440' => 'SeABank', '970431' => 'Eximbank', '970449' => 'LPBank', '970428' => 'Nam A Bank', '970425' => 'ABBANK',
        '970409' => 'Bac A Bank', '970412' => 'PVcomBank', '970452' => 'KienlongBank', '970419' => 'NCB', '970427' => 'VietABank',
        '970438' => 'BaoViet Bank', '970400' => 'Saigonbank', '970430' => 'PG Bank', '970433' => 'Vietbank', '970429' => 'SCB',
    ];

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
