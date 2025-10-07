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
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class LitecoinPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    public function __construct(
        ClientInterface $client,
        ConfigurationService $configurationService,
        OrderTransactionStateHandler $transactionStateHandler,
        LoggerInterface $logger,
        EntityRepository $orderRepository,
        EntityRepository $orderTransactionRepository
    ) {
        parent::__construct(
            $client,
            $configurationService,
            $transactionStateHandler,
            $logger,
            $orderRepository,
            $orderTransactionRepository
        );
    }

    protected function sendReturnUrlToCheckout(PaymentTransactionStruct $transaction, Context $context): string
    {
        try {
            $order = $transaction->getOrder();

            if (! $order instanceof OrderEntity) {
                $order = $this->loadOrderByTransactionId($transaction->getOrderTransactionId(), $context);
            }

            $accountUrl = $this->baseSuccessUrl.$transaction->getOrderTransaction()->getOrderId();

            if ($transaction->getOrderTransaction()->getAmount()->getTotalPrice() == 0.0) {
                $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);

                return $accountUrl;
            }

            $currency = $order->getCurrency();

            if ($currency === null) {
                throw new \RuntimeException(sprintf('Currency information missing for order %s', (string) $order->getId()));
            }

            $uri = '/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/invoices';
            $response = $this->client->sendPostRequest(
                $uri,
                [
                    'amount' => $transaction->getOrderTransaction()->getAmount()->getTotalPrice(),
                    'currency' => $currency->getIsoCode(),
                    'metadata' => [
                        'orderId' => $transaction->getOrderTransaction()->getOrderId(),
                        'orderNumber' => $order->getOrderNumber(),
                        'transactionId' => $transaction->getOrderTransaction()->getId(),
                    ],
                    'checkout' => [
                        'redirectURL' => $accountUrl,
                        'redirectAutomatically' => true,
                        'paymentMethods' => ['LTC-CHAIN'],
                    ],
                ]
            );

            return $response['checkoutLink'];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}
