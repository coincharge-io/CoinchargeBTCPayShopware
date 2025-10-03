<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Order;

use Coincharge\Shopware\Client\ClientInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;

class OrderService
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly ConfigurationService $configurationService,
    ) {
    }

    public function invoiceIsFullyPaid(string $invoiceId): bool
    {
        $storeId = $this->configurationService->getSetting('btcpayServerStoreId');

        if (!\is_string($storeId) || $storeId === '') {
            return false;
        }

        $uri = '/api/v1/stores/' . $storeId . '/invoices/' . $invoiceId;
        $response = $this->client->sendGetRequest($uri);

        return ($response['status'] ?? null) === 'Settled';
    }
}
