<?php

declare (strict_types = 1);

namespace Coincharge\Shopware\Order;

use Coincharge\Shopware\Client\BTCPayServerClientInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Coincharge\Shopware\Configuration\ConfigurationService;

class OrderService
{
    private BTCPayServerClientInterface $client;
    private EntityRepository $orderRepository;
    private ConfigurationService $configurationService;

    public function __construct(BTCPayServerClientInterface $client, EntityRepository $orderRepository, ConfigurationService $configurationService)
    {
        $this->client = $client;
        $this->orderRepository = $orderRepository;
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
