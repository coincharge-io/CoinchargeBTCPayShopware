<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Webhook\Factory;

use Coincharge\Shopware\Webhook\BTCPayWebhookService;
use Coincharge\Shopware\Webhook\CoinsnapWebhookService;
use Coincharge\Shopware\Webhook\WebhookServiceInterface;

class WebhookFactory
{
    private CoinsnapWebhookService $coinsnapWebhookService;
    private BTCPayWebhookService $btcpayWebhookService;

    public function __construct(CoinsnapWebhookService $coinsnapWebhookService, BTCPayWebhookService $btcpayWebhookService)
    {
        $this->coinsnapWebhookService = $coinsnapWebhookService;
        $this->btcpayWebhookService = $btcpayWebhookService;
    }

    public function create(string $provider): WebhookServiceInterface
    {
        return match ($provider) {
            'coinsnap' => $this->coinsnapWebhookService,
            'btcpay' => $this->btcpayWebhookService,
            default => throw new \RuntimeException('Unsupported webhook provider.'),
        };
    }
}
