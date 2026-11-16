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

class CoinsnapWebhookService extends AbstractWebhookService
{
    // Timestamped header; the older X-Coinsnap-Sig has no time in it and can be replayed.
    public const REQUIRED_HEADER = 'x-coinsnap-signature';

    private const SETTINGS = [
        'webhookSecret' => 'coinsnapWebhookSecret',
        'webhookId' => 'coinsnapWebhookId',
        'storeId' => 'coinsnapStoreId',
    ];

    protected function provider(): string
    {
        return 'coinsnap';
    }

    protected function setting(string $name): string
    {
        return self::SETTINGS[$name];
    }

    protected function signatureProblem(Request $request, string $secret): ?string
    {
        return WebhookSignature::coinsnapProblem(
            (string) $request->headers->get(self::REQUIRED_HEADER),
            $request->getContent(),
            $secret,
            time()
        );
    }

    protected function decide(array $invoice, float $price, string $currency): string
    {
        return InvoiceDecision::coinsnap($invoice, $price, $currency);
    }

    protected function paidEventTypes(): array
    {
        return ['Settled'];
    }
}
