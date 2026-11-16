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

// Each check returns null when the signature is valid, otherwise the reason for the log.
class WebhookSignature
{
    public const REASON_BAD_SIGNATURE = 'bad signature';
    public const REASON_STALE_TIMESTAMP = 'stale timestamp';
    public const MAX_AGE_SECONDS = 300;

    // X-Coinsnap-Signature: t=<unix time>,v1=<hex HMAC-SHA256 of "t.body">
    public static function coinsnapProblem(string $header, string $rawBody, string $secret, int $nowSeconds): ?string
    {
        parse_str(str_replace(',', '&', $header), $parts);
        $timestamp = (int) ($parts['t'] ?? 0);
        $signature = $parts['v1'] ?? '';

        if ($secret === '' || $timestamp <= 0 || !is_string($signature)) {
            return self::REASON_BAD_SIGNATURE;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        if (!hash_equals($expected, $signature)) {
            return self::REASON_BAD_SIGNATURE;
        }

        if (abs($nowSeconds - $timestamp) > self::MAX_AGE_SECONDS) {
            return self::REASON_STALE_TIMESTAMP;
        }

        return null;
    }

    // BTCPay-Sig: sha256=<hex HMAC-SHA256 of body>
    public static function btcpayProblem(string $header, string $rawBody, string $secret): ?string
    {
        if ($secret === '') {
            return self::REASON_BAD_SIGNATURE;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $header) ? null : self::REASON_BAD_SIGNATURE;
    }
}
