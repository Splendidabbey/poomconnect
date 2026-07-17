<?php

declare(strict_types=1);

/**
 * Thai PromptPay EMV QR payload (Bill Payment / Credit Transfer).
 * @see https://www.bot.or.th/Thai/PaymentSystems/StandardPS/Documents/QRCode_payment_standard_Thailand.pdf
 */
function promptpay_payload(string $id, ?float $amount = null): string
{
    $id = preg_replace('/\D/', '', $id) ?? '';

    if (strlen($id) === 10) {
        $id = '0066' . substr($id, 1);
    } elseif (strlen($id) === 13 && str_starts_with($id, '0')) {
        $id = '0066' . substr($id, 1);
    }

    $merchant = tlv('00', '01')
        . tlv('01', strlen($id) === 13 ? '02' : '01')
        . tlv(strlen($id) === 13 ? '02' : '01', $id);

    $payload = tlv('00', '01')
        . tlv('01', '11')
        . tlv('29', $merchant)
        . tlv('53', '764');

    if ($amount !== null && $amount > 0) {
        $payload .= tlv('54', number_format($amount, 2, '.', ''));
    }

    $payload .= tlv('58', 'TH');
    $payload .= tlv('63', crc16($payload . '6304'));

    return $payload;
}

function promptpay_qr_url(string $id, ?float $amount = null, int $size = 280): string
{
    $payload = promptpay_payload($id, $amount);

    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($payload);
}

function tlv(string $id, string $value): string
{
    return $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function crc16(string $data): string
{
    $crc = 0xFFFF;
    $len = strlen($data);

    for ($i = 0; $i < $len; $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc <<= 1;
            }
            $crc &= 0xFFFF;
        }
    }

    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}
