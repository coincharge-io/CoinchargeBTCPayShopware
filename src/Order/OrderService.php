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
    private ClientInterface $client;
    private ConfigurationService $configurationService;

    public function __construct(BTCPayServerClientInterface $client, ConfigurationService $configurationService)
    {
        $this->client = $client;
        $this->configurationService = $configurationService;
    }

    public function invoiceIsFullyPaid(string $invoiceId): bool
    {

        $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices/' . $invoiceId;
        $response = $this->client->sendGetRequest($uri);
        if ($response['status'] !== 'Settled') {
            return false;
        }
        return true;
    }
}
