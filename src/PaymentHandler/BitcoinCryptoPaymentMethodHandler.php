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

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Order\OrderEntity;

class BitcoinCryptoPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    protected function sendReturnUrlToCheckout(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $context, OrderEntity $order): string
    {
        try {
            $accountUrl = $this->baseSuccessUrl . $transaction->getOrderTransaction()->getOrderId();
            if ($transaction->getOrderTransaction()->getAmount()->getTotalPrice() == 0) {
                $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context->getContext());
                return $accountUrl;
            }
            $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices';
            $response = $this->client->sendPostRequest(
                $uri,
                [
                    'amount' => $transaction->getOrderTransaction()->getAmount()->getTotalPrice(),
                    'currency' => $context->getCurrency()->getIsoCode(),
                    'metadata' =>
                    [
                        'orderId' => $transaction->getOrderTransaction()->getOrderId(),
                        'orderNumber' => $order->getOrderNumber(),
                        'transactionId' => $transaction->getOrderTransaction()->getId()
                    ],
                    'checkout' => [
                        'redirectURL' => $accountUrl,
                        'redirectAutomatically' => true,
                    ]
                ]
            );

            return $response['checkoutLink'];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}
