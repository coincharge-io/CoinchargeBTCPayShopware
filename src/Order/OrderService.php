<?php

declare(strict_types=1);

namespace Coincharge\Shopware\Order;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;
use Coincharge\Shopware\Client\BTCPayServerClientInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Coincharge\Shopware\Order\OrderServiceInterface;

class OrderService implements OrderServiceInterface
{
    private BTCPayServerClientInterface $client;
    private EntityRepository $orderRepository;

    public function __construct(BTCPayServerClientInterface $client, EntityRepository $orderRepository)
    {
        $this->client = $client;
        $this->orderRepository = $orderRepository;
    }

    protected function invoiceIsFullyPaid($invoiceId): bool
    {
        /* $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]); */
        /* $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices/' . $invoiceId);

        $responseBody = json_decode($response->getBody()->getContents()); */
        $uri =  '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices/' . $invoiceId;
        $response = $this->client->sendGetRequest($uri);
        if ($response['status'] !== 'Settled') {
            return false;
        }
        return true;
    }

    public function update(int $orderNumber, array $fields = ['btcpayOrderStatus' => 'New'], Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
        //check custom field order status
        $orderId = $this->orderRepository->searchIds($criteria, $context)->firstId();
        $this->orderRepository->upsert([
            [
                'id' => $orderId,
                'customFields' => $fields,
            ],
        ], $context);
    }
}
