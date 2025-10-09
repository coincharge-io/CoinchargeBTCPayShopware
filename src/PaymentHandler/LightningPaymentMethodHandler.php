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
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class LightningPaymentMethodHandler extends AbstractPaymentMethodHandler
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
            $order = $this->loadOrderByTransactionId($transaction->getOrderTransactionId(), $context);
            $orderTransaction = $order->getTransactions()?->get($transaction->getOrderTransactionId());

            if ($orderTransaction === null) {
                throw PaymentException::asyncProcessInterrupted(
                    $transaction->getOrderTransactionId(),
                    sprintf('Transaction %s missing on order %s.', $transaction->getOrderTransactionId(), $order->getOrderNumber() ?? $order->getId())
                );
            }

            $accountUrl = $this->baseSuccessUrl.$orderTransaction->getOrderId();

            if ($orderTransaction->getAmount()->getTotalPrice() == 0.0) {
                $this->transactionStateHandler->paid($orderTransaction->getId(), $context);

                return $accountUrl;
            }

            $currency = $order->getCurrency();

            if ($currency === null) {
                throw new \RuntimeException(sprintf('Currency information missing for order %s', (string) $order->getId()));
            }

            $orderNumber = $order->getOrderNumber() ?? (string) $order->getId();

            $redirectUrl = $this->client->createInvoice(
                $orderTransaction->getAmount()->getTotalPrice(),
                $currency->getIsoCode(),
                [
                    'orderId' => $orderTransaction->getOrderId(),
                    'orderNumber' => $orderNumber,
                    'transactionId' => $orderTransaction->getId(),
                ],
                $accountUrl,
                $context,
                ['BTC-LN', 'BTC-LNURL']
            );

            return $redirectUrl;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}
