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

class CoinsnapBitcoinPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    protected function sendReturnUrlToCheckout(PaymentTransactionStruct $transaction, Context $context, OrderEntity $order): string
    {
        try {
            $accountUrl = $this->baseSuccessUrl . $transaction->getOrderTransaction()->getOrderId();
            if ($transaction->getOrderTransaction()->getAmount()->getTotalPrice() == 0) {
                $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
                return $accountUrl;
            }
            $uri = '/api/v1/stores/' . $this->configurationService->getSetting('coinsnapStoreId') . '/invoices';
            $response = $this->client->sendPostRequest(
                $uri,
                [
                    'amount' => $transaction->getOrderTransaction()->getAmount()->getTotalPrice(),
                    'currency' => $this->getCurrencyIso($order),
                    'referralCode' => 'DEV17612c35cd8c54d3fad381615',
                    'metadata' =>
                    [
                        'orderNumber' => $order->getOrderNumber(),
                        'transactionId' => $transaction->getOrderTransaction()->getId()
                    ],
                    'orderId' => $transaction->getOrderTransaction()->getOrderId(),
                    'redirectUrl' => $accountUrl,
                    'enabledPaymentMethods' => ['BTC']
                ]
            );

            return $response['checkoutLink'];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}
