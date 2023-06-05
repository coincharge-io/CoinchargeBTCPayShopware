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

use Coincharge\Shopware\Client\ClientInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Context;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class CoinsnapWebhookService implements WebhookServiceInterface
{
    public const REQUIRED_HEADER = 'x-coinsnap-sig';
    private ClientInterface $client;
    private ConfigurationService $configurationService;
    private OrderTransactionStateHandler $transactionStateHandler;
    private $orderService;
    private EntityRepository $orderRepository;
    private LoggerInterface $logger;

    public function __construct(ClientInterface $client, ConfigurationService $configurationService, OrderTransactionStateHandler $transactionStateHandler, $orderService, EntityRepository $orderRepository, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function register(Request $request, ?string $salesChannelId): bool
    {
        if ($this->isEnabled()) {
            $this->logger->info('Webhook exists');
            return true;
        }

        $webhookUrl =  $request->server->get('APP_URL') . '/api/_action/coincharge/webhook-endpoint';

        $uri = '/api/v1/websites/' . $this->configurationService->getSetting('coisnapWebsiteId') . '/webhooks';
        $body = $this->client->sendPostRequest(
            $uri,
            [
                'url' => $webhookUrl
            ]
        );
        if (empty($body)) {
            $this->logger->error("Webhook couldn't be created");
            return false;
        }

        $this->configurationService->setSetting('coinsnapWebhookSecret', $body['secret']);
        $this->configurationService->setSetting('coinsnapWebhookId', $body['id']);

        return true;
    }
    public function isEnabled(): bool
    {

        if (empty($this->configurationService->getSetting('coinsnapWebhookId'))) {
            return false;
        }
        $uri = '/api/v1/websites/' . $this->configurationService->getSetting('coinsnapWebsiteId') . '/webhooks/' . $this->configurationService->getSetting('coinsnapWebhookId');
        $response = $this->client->sendGetRequest($uri);

        if (empty($response)) {
            $this->logger->error("Webhook with ID:" . $this->configurationService->getSetting('btcpayWebhookId') . " doesn't exist.");
            return false;
        }
        if ($response['enabled'] == false) {
            $this->logger->error("Webhook with ID:" . $this->configurationService->getSetting('btcpayWebhookId') . " isn't enabled.");
            return false;
        }
        return true;
    }

    public function process(Request $request, Context $context): Response
    {
        $signature = $request->headers->get(self::REQUIRED_HEADER);
        $body = $request->request->all();

        $expectedHeader = 'sha256=' . hash_hmac('sha256', $request->getContent(), $this->configurationService->getSetting('btcpayWebhookSecret'));
        if ($signature !== $expectedHeader) {
            $this->logger->error('Invalid signature');
            return new Response();
        }
        $uri = '/api/v1/websites/' . $this->configurationService->getSetting('coinsnapWebsiteId') . '/invoices/' . $body['invoiceId'];
        $responseBody = $this->client->sendGetRequest($uri);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $responseBody['metadata']['orderNumber']));
        $orderId = $this->orderRepository->searchIds($criteria, $context)->firstId();



        switch ($body['event']) {
            case 'Pending': // The invoice is paid in full.
                $this->transactionStateHandler->process($responseBody['metadata']['transactionId'], $context);
                $this->logger->info('Invoice payment received fully, waiting for settlement.');
                break;
            case 'Expired':
                $this->orderRepository->upsert(
                    [
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'invoiceId' => $body['invoiceId'],
                                'btcpayOrderStatus' => 'invoiceExpired',
                                'paidAfterExpiration' => true,
                                'overpaid'  => false
                            ],
                        ],
                    ],
                    $context
                );
                $this->transactionStateHandler->payPartially($responseBody['metadata']['transactionId'], $context);
                $this->logger->info('Invoice expired but was paid partially, please check.');
                break;
            case 'Paid':
                $this->orderRepository->upsert(
                    [
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'invoiceId' => $body['invoiceId'],
                                'btcpayOrderStatus' => 'paid',
                                'paidAfterExpiration' => false,
                                'overpaid' => true
                            ],
                        ],
                    ],
                    $context
                );
                $this->transactionStateHandler->paid($responseBody['metadata']['transactionId'], $context);
                $this->logger->info('Invoice payment settled but was overpaid.');
                break;
        }
        return new Response();
    }
}
