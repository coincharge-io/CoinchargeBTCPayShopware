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
use Coincharge\Shopware\Configuration\ConfigurationService;
use JsonException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        try {
            if ($this->isEnabled()) {
                $this->logger->info('Webhook exists');
                return true;
            }

            $webhookUrl =  $request->server->get('APP_URL') . '/api/_action/coincharge/webhook-endpoint';

            $uri = '/api/v1/stores/' . $this->configurationService->getSetting('coinsnapStoreId') . '/webhooks';
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
        } catch (\Exception $e) {
            return false;
        }
    }
    public function isEnabled(): bool
    {
        try {

            if (empty($this->configurationService->getSetting('coinsnapWebhookId'))) {
                return false;
            }
            $uri = '/api/v1/stores/' . $this->configurationService->getSetting('coinsnapStoreId') . '/webhooks/' . $this->configurationService->getSetting('coinsnapWebhookId');
            $response = $this->client->sendGetRequest($uri);
            if (empty($response)) {
                $this->logger->error("Webhook with ID:" . $this->configurationService->getSetting('coinsnapWebhookId') . " doesn't exist.");
                return false;
            }
            if ($response['enabled'] == false) {
                $this->logger->error("Webhook with ID:" . $this->configurationService->getSetting('coinsnapWebhookId') . " isn't enabled.");
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function process(Request $request, Context $context): Response
    {
        $signature = $request->headers->get(self::REQUIRED_HEADER);
        try {
            $body = $this->decodePayload($request);
        } catch (\RuntimeException $decodeException) {
            $this->logger->error('Failed to decode Coinsnap webhook payload', [
                'error' => $decodeException->getMessage(),
            ]);

            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $expectedHeader = 'sha256=' . hash_hmac('sha256', $request->getContent(), $this->configurationService->getSetting('coinsnapWebhookSecret'));

        if (!\is_string($signature) || !hash_equals($expectedHeader, $signature)) {
            $this->logger->error('Invalid signature');
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        if (!isset($body['invoiceId'], $body['type'])) {
            $this->logger->error('Coinsnap webhook payload missing required fields', [
                'payload' => $body,
            ]);

            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $uri = '/api/v1/stores/' . $this->configurationService->getSetting('coinsnapStoreId') . '/invoices/' . $body['invoiceId'];
        $responseBody = $this->client->sendGetRequest($uri);

        if (!isset($responseBody['metadata']['orderNumber'], $responseBody['metadata']['transactionId'])) {
            $this->logger->error('Coinsnap invoice metadata missing expected identifiers', [
                'invoiceId' => $body['invoiceId'],
                'response' => $responseBody,
            ]);

            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $responseBody['metadata']['orderNumber']));
        $orderId = $this->orderRepository->searchIds($criteria, $context)->firstId();

        if ($orderId === null) {
            $this->logger->warning('Coinsnap webhook received for unknown order number', [
                'orderNumber' => $responseBody['metadata']['orderNumber'],
                'invoiceId' => $body['invoiceId'],
            ]);

            return new Response('', Response::HTTP_NO_CONTENT);
        }

        switch ($body['type']) {
            case 'Processing': // The invoice is paid in full.
                $this->transactionStateHandler->process($responseBody['metadata']['transactionId'], $context);
                $this->orderRepository->upsert(
                    [
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'coinsnapInvoiceId' => $body['invoiceId'],
                                'coinsnapOrderStatus' => 'processing',
                            ],
                        ],
                    ],
                    $context
                );
                $this->logger->info('Invoice settled, waiting for payment to settle.');
                break;
            case 'Expired':
                //TODO: Check if invoice was partially paid
                $underpaid = $body['underpaid'] ?? false;
                $status = $underpaid ? 'partially_paid' : 'expired';
                $this->orderRepository->upsert(
                    [
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'coinsnapInvoiceId' => $body['invoiceId'],
                                'coinsnapOrderStatus' => $status,
                            ],
                        ],
                    ],
                    $context
                );
                //TODO: Check if paid partially
                if ($underpaid) {
                    $this->transactionStateHandler->payPartially($responseBody['metadata']['transactionId'], $context);
                }
                $this->logger->info('Invoice expired.');
                break;
            case 'Settled':
                $this->orderRepository->upsert(
                    [
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'coinsnapInvoiceId' => $body['invoiceId'],
                                'coinsnapOrderStatus' => 'settled',
                            ],
                        ],
                    ],
                    $context
                );
                $this->transactionStateHandler->paid($responseBody['metadata']['transactionId'], $context);
                $this->logger->info('Invoice payment settled.');
                break;
        }
        return new Response();
    }

    private function decodePayload(Request $request): array
    {
        $content = $request->getContent();

        if ($content === '') {
            throw new \RuntimeException('Empty request body.');
        }

        try {
            return \json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new \RuntimeException('Invalid JSON payload.', 0, $e);
        }
    }
}
