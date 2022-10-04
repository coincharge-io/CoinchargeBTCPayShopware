<?php

declare(strict_types=1);

namespace Coincharge\Shopware\Order;

use Coincharge\Shopware\Client\BTCPayServerClientInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Shopware\Core\Framework\Context;

class OrderService
{
    private BTCPayServerClientInterface $client;
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
