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

use Symfony\Component\HttpFoundation\Request;

class BTCPayWebhookService extends AbstractWebhookService
{
    public const REQUIRED_HEADER = 'btcpay-sig';

    private const SETTINGS = [
        'webhookSecret' => 'btcpayWebhookSecret',
        'webhookId' => 'btcpayWebhookId',
        'storeId' => 'btcpayServerStoreId',
    ];

    protected function provider(): string
    {
        return 'btcpay';
    }

    protected function setting(string $name): string
    {
        return self::SETTINGS[$name];
    }

    protected function signatureProblem(Request $request, string $secret): ?string
    {
        return WebhookSignature::btcpayProblem(
            (string) $request->headers->get(self::REQUIRED_HEADER),
            $request->getContent(),
            $secret
        );
    }

    protected function decide(array $invoice, float $price, string $currency): string
    {
        return InvoiceDecision::btcpay($invoice, $price, $currency);
    }

    protected function paidEventTypes(): array
    {
        return ['InvoiceSettled'];
    }
}
