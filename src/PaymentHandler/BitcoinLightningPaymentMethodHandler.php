<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\PaymentHandler;

use Coincharge\Shopware\Client\ClientInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class BitcoinLightningPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    protected ClientInterface $client;

    protected ConfigurationService $configurationService;

    protected OrderTransactionStateHandler $transactionStateHandler;

    protected LoggerInterface $logger;

    public function __construct(ClientInterface $client, ConfigurationService $configurationService, OrderTransactionStateHandler $transactionStateHandler, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->logger = $logger;
        parent::__construct($client, $configurationService, $transactionStateHandler, $logger);
    }

    public function sendReturnUrlToCheckout(PaymentTransactionStruct $transaction, SalesChannelContext $context)
    {
        try {
            $accountUrl = $this->baseSuccessUrl.$transaction->getOrderTransaction()->getOrderId();
            if ($transaction->getOrderTransaction()->getAmount()->getTotalPrice() == 0) {
                $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context->getContext());

                return $accountUrl;
            }
            $uri = '/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/invoices';
            $response = $this->client->sendPostRequest(
                $uri,
                [
                    'amount' => $transaction->getOrderTransaction()->getAmount()->getTotalPrice(),
                    'currency' => $context->getCurrency()->getIsoCode(),
                    'metadata' => [
                        'orderId' => $transaction->getOrderTransaction()->getOrderId(),
                        'orderNumber' => $transaction->getOrder()->getOrderNumber(),
                        'transactionId' => $transaction->getOrderTransaction()->getId(),
                    ],
                    'checkout' => [
                        'redirectURL' => $accountUrl,
                        'redirectAutomatically' => true,
                        'paymentMethods' => ['BTC', 'BTC-LightningNetwork', 'BTC-LNURLPAY'],
                    ],
                ]
            );

            return $response['checkoutLink'];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    public function createInvoice(
        float $amount,
        string $currency,
        array $metadata,
        string $redirectUrl,
        Context $context,
        ?array $paymentMethods = null // optional parameter
    ): string {
        $storeId = $this->configurationService->getSetting('btcpayServerStoreId');
        $uri = '/api/v1/stores/'.$storeId.'/invoices';

        // Base payload
        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => $metadata,
            'checkout' => [
                'redirectURL' => $redirectUrl,
                'redirectAutomatically' => true,
            ],
        ];

        // Conditionally add paymentMethods only if provided
        if (! empty($paymentMethods)) {
            $payload['checkout']['paymentMethods'] = $paymentMethods;
        }

        try {
            $response = $this->sendPostRequest($uri, $payload);

            return $response['checkoutLink'] ?? $redirectUrl;
        } catch (\Throwable $e) {
            $this->logger->error('BTCPay invoice creation failed', [
                'exception' => $e,
                'payload' => $payload,
            ]);

            throw $e;
        }
    }
}
