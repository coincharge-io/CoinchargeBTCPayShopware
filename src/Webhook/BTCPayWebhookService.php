<?php

declare(strict_types=1);

/**
 * Copyright (c) 2025 Coincharge
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

class BTCPayWebhookService implements WebhookServiceInterface
{
  public const REQUIRED_HEADER = 'btcpay-sig';
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

      $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/webhooks';
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

      $this->configurationService->setSetting('btcpayWebhookSecret', $body['secret']);
      $this->configurationService->setSetting('btcpayWebhookId', $body['id']);

      return true;
    } catch (\Exception $e) {
      return false;
    }
  }
  public function isEnabled(): bool
  {
    try {
      if (empty($this->configurationService->getSetting('btcpayWebhookId'))) {
        return false;
      }
      $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/webhooks/' . $this->configurationService->getSetting('btcpayWebhookId');
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
      $this->logger->error('Failed to decode BTCPay webhook payload', [
        'error' => $decodeException->getMessage(),
      ]);

      return new Response('', Response::HTTP_BAD_REQUEST);
    }

    $expectedHeader = 'sha256=' . hash_hmac('sha256', $request->getContent(), $this->configurationService->getSetting('btcpayWebhookSecret'));
    if (!\is_string($signature) || !hash_equals($expectedHeader, $signature)) {
      $this->logger->error('Invalid signature');
      return new Response('', Response::HTTP_FORBIDDEN);
    }
    if (!isset($body['invoiceId'], $body['type'])) {
      $this->logger->error('BTCPay webhook payload missing required fields', [
        'payload' => $body,
      ]);

      return new Response('', Response::HTTP_BAD_REQUEST);
    }

    $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices/' . $body['invoiceId'];
    $responseBody = $this->client->sendGetRequest($uri);

    if (!isset($responseBody['metadata']['orderNumber'], $responseBody['metadata']['transactionId'])) {
      $this->logger->error('BTCPay invoice metadata missing expected identifiers', [
        'invoiceId' => $body['invoiceId'],
        'response' => $responseBody,
      ]);

      return new Response('', Response::HTTP_NO_CONTENT);
    }

    $criteria = new Criteria();
    $criteria->addFilter(new EqualsFilter('orderNumber', $responseBody['metadata']['orderNumber']));
    $orderId = $this->orderRepository->searchIds($criteria, $context)->firstId();

    if ($orderId === null) {
      $this->logger->warning('BTCPay webhook received for unknown order number', [
        'orderNumber' => $responseBody['metadata']['orderNumber'],
        'invoiceId' => $body['invoiceId'],
      ]);

      return new Response('', Response::HTTP_NO_CONTENT);
    }

    $paymentMethod = $responseBody['paymentMethods'][0] ?? [];
    $cryptoAmount = $paymentMethod['amount'] ?? null;
    $exchangeRate = $paymentMethod['rate'] ?? null;

    switch ($body['type']) {
      case 'InvoiceReceivedPayment':
        if ($body['afterExpiration']) {
          $this->transactionStateHandler->payPartially($responseBody['metadata']['transactionId'], $context);
          $this->logger->info('Invoice (partial) payment incoming (unconfirmed) after invoice was already expired.');
        } else {
          $this->logger->info('Invoice (partial) payment incoming (unconfirmed). Waiting for settlement.');
        }

        $this->orderRepository->upsert(
          [
            [
              'id' => $orderId,
              'customFields' => [
                'invoiceId' => $body['invoiceId'],
                'btcpayOrderStatus' => 'processing',
                'paidAfterExpiration' => $body['afterExpiration'] ?? false,
                'overpaid' => $body['overPaid'] ?? false,
                'cryptoAmount' => $cryptoAmount,
                'exchangeRate' => $exchangeRate,
              ],
            ],
          ],
          $context
        );
        break;
      case 'InvoicePaymentSettled':
        // We can't use $body->afterExpiration here as there is a bug affecting all version prior to
        // BTCPay Server v1.7.0.0, see https://github.com/btcpayserver/btcpayserver/issues/
        // Therefore we check if the invoice is in expired or expired paid partial status, instead.
        if (
          $responseBody['status'] == 'Expired'
          || ($responseBody['status'] == 'Expired' && $responseBody['additionalStatus'] == 'PaidPartial')
        ) {
          // Check if also the invoice is now fully paid.
          if ($this->orderService->invoiceIsFullyPaid($body['invoiceId'])) {
            $this->orderRepository->upsert(
              [
                [
                  'id' => $orderId,
                  'customFields' => [
                    'invoiceId' => $body['invoiceId'],
                    'btcpayOrderStatus' => 'settled',
                    'paidAfterExpiration' => true,
                    'overpaid'      =>  false,
                    'cryptoAmount' => $cryptoAmount,
                    'exchangeRate' => $exchangeRate,
                  ],
                ],
              ],
              $context
            );
            $this->logger->info('Invoice fully settled after invoice was already expired. Needs manual checking.');
          } else {
            $this->orderRepository->upsert(
              [
                [
                  'id' => $orderId,
                  'customFields' => [
                    'invoiceId' => $body['invoiceId'],
                    'btcpayOrderStatus' => 'paidPartially',
                    'paidAfterExpiration' => true,
                    'overpaid'      => false,
                    'cryptoAmount' => $cryptoAmount,
                    'exchangeRate' => $exchangeRate,
                  ],
                ],
              ],
              $context
            );
            $this->transactionStateHandler->payPartially($responseBody['metadata']['transactionId'], $context);
            $this->logger->debug('Invoice with orderId:' . $responseBody['metadata']['orderId'] . ' NOT fully paid.');
            $this->logger->info('(Partial) payment settled but invoice not settled yet (could be more transactions incoming). Needs manual checking.');
          }
        } else {
          // No need to change order status here, only leave a note.
          $this->logger->info('Invoice (partial) payment settled.');
        }

        break;
      case 'InvoiceProcessing': // The invoice is paid in full.
        $this->transactionStateHandler->process($responseBody['metadata']['transactionId'], $context);
        if ($body['overPaid']) {

          $this->logger->info('Invoice payment received fully with overpayment, waiting for settlement.');
        } else {
          $this->logger->info('Invoice payment received fully, waiting for settlement.');
        }
        break;
      case 'InvoiceInvalid':
        $this->transactionStateHandler->fail($responseBody['metadata']['transactionId'], $context);
        if ($body['manuallyMarked']) {
          $this->orderRepository->upsert(
            [
              [
                'id' => $orderId,
                'customFields' => [
                  'invoiceId' => $body['invoiceId'],
                  'btcpayOrderStatus' => 'manuallyMarked',
                  'overpaid'  => false,
                  'paidAfterExpiration' => false
                ],
              ],
            ],
            $context
          );
          $this->logger->info('Invoice manually marked invalid.');
        } else {
          $this->logger->info('Invoice became invalid.');
        }
        break;
      case 'InvoiceExpired':
        if ($body['partiallyPaid']) {

          $this->orderRepository->upsert(
            [
              [
                'id' => $orderId,
                'customFields' => [
                  'invoiceId' => $body['invoiceId'],
                  'btcpayOrderStatus' => 'invoiceExpired',
                  'paidAfterExpiration' => true,
                  'overpaid'  => false,
                  'cryptoAmount' => $cryptoAmount,
                  'exchangeRate' => $exchangeRate,
                ],
              ],
            ],
            $context
          );
          $this->transactionStateHandler->payPartially($responseBody['metadata']['transactionId'], $context);
          $this->logger->info('Invoice expired but was paid partially, please check.');
        } else {
          $this->orderRepository->upsert(
            [
              [
                'id' => $orderId,
                'customFields' => [
                  'invoiceId' => $body['invoiceId'],
                  'btcpayOrderStatus' => 'invoiceExpired',
                  'paidAfterExpiration' => false,
                  'overpaid'  => false,
                  'cryptoAmount' => $cryptoAmount,
                  'exchangeRate' => $exchangeRate,
                ],
              ],
            ],
            $context
          );
          $this->transactionStateHandler->fail($responseBody['metadata']['transactionId'], $context);
          $this->logger->info('Invoice expired.');
        }
        break;
      case 'InvoiceSettled':
        //Webhook doesn't send overPaid
        //Bug
        if (isset($body['overPaid']) && $body['overPaid']) {

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
        } else {
          $this->orderRepository->upsert(
            [
              [
                'id' => $orderId,
                'customFields' => [
                  'invoiceId' => $body['invoiceId'],
                  'btcpayOrderStatus' => 'paid',
                  'paidAfterExpiration' => false,
                  'overpaid' => false
                ],
              ],
            ],
            $context
          );
          $this->logger->info('Invoice payment settled.');
          $this->transactionStateHandler->paid($responseBody['metadata']['transactionId'], $context);
        }
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
