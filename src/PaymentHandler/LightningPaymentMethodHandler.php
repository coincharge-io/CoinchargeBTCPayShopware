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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

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
                        'paymentMethods' => ['BTC-LightningNetwork'],
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
