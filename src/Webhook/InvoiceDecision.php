<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Webhook;

// Maps the invoice as the API reports it to what should happen to the order transaction.
class InvoiceDecision
{
    public const PAID = 'paid';
    public const PROCESS = 'process';
    public const PARTIAL = 'partial';
    public const FAIL = 'fail';
    public const NONE = 'none';
    // Settled, but not for this transaction's amount and currency (or settled by hand).
    public const MISMATCH = 'mismatch';

    public static function coinsnap(array $invoice, float $price, string $currency): string
    {
        $status = (string) ($invoice['status'] ?? '');
        $additionalStatus = (string) ($invoice['additionalStatus'] ?? '');

        return match ($status) {
            'Settled' => self::pays($invoice['fiatAmount'] ?? null, $invoice['currency'] ?? null, $price, $currency)
                ? self::PAID
                : self::MISMATCH,
            'Processing' => self::PROCESS,
            'Expired' => $additionalStatus === 'Underpaid' ? self::PARTIAL : self::NONE,
            'Invalid' => self::FAIL,
            default => self::NONE,
        };
    }

    public static function btcpay(array $invoice, float $price, string $currency): string
    {
        $status = (string) ($invoice['status'] ?? '');
        $additionalStatus = (string) ($invoice['additionalStatus'] ?? '');

        if ($status === 'Settled') {
            // Marked: the merchant settled it by hand in BTCPay, no payment proves it.
            $paid = $additionalStatus !== 'Marked'
                && self::pays($invoice['amount'] ?? null, $invoice['currency'] ?? null, $price, $currency);

            return $paid ? self::PAID : self::MISMATCH;
        }

        return match ($status) {
            'Processing' => self::PROCESS,
            'Expired' => $additionalStatus === 'PaidPartial' ? self::PARTIAL : self::FAIL,
            'Invalid' => self::FAIL,
            default => self::NONE,
        };
    }

    private static function pays(mixed $invoiceAmount, mixed $invoiceCurrency, float $price, string $currency): bool
    {
        $priceCents = self::toCents($price);
        $paidCents = self::toCents($invoiceAmount);

        if ($priceCents === null || $priceCents <= 0 || $paidCents === null) {
            return false;
        }
        if (strtoupper((string) $invoiceCurrency) !== strtoupper($currency)) {
            return false;
        }

        return abs($paidCents - $priceCents) <= 1;
    }

    // Whole cents, parsed from the decimal string so amounts are never compared as floats.
    public static function toCents(mixed $value): ?int
    {
        if (is_int($value) || is_float($value)) {
            $value = number_format((float) $value, 2, '.', '');
        }
        if (!is_string($value) || !preg_match('/^(\d+)(?:\.(\d+))?$/', trim($value), $match)) {
            return null;
        }

        $fraction = str_pad($match[2] ?? '', 3, '0');
        $roundUp = (int) $fraction[2] >= 5 ? 1 : 0;

        return (int) $match[1] * 100 + (int) substr($fraction, 0, 2) + $roundUp;
    }
}
