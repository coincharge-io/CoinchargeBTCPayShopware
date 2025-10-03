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
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractPaymentMethodHandler extends AbstractPaymentHandler
{
    protected ClientInterface $client;

    protected ConfigurationService $configurationService;

    protected OrderTransactionStateHandler $transactionStateHandler;

    protected LoggerInterface $logger;

    protected EntityRepository $orderRepository;

    public string $baseSuccessUrl;

    public function __construct(ClientInterface $client, ConfigurationService $configurationService, OrderTransactionStateHandler $transactionStateHandler, LoggerInterface $logger, EntityRepository $orderRepository)
    {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $appUrl = $_SERVER['APP_URL'];
        $url = "$appUrl/checkout/finish?orderId=";
        $this->baseSuccessUrl = $url;
    }

    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
    {
        $orderTransaction = $transaction->getOrderTransaction();

        try {
            $order = $this->loadOrder($orderTransaction->getOrderId(), $context, $orderTransaction->getId());
            $redirectUrl = $this->sendReturnUrlToCheckout($transaction, $context, $order);
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransaction->getId(),
                'An error occurred during the communication with external payment gateway'.PHP_EOL.$e->getMessage()
            );
        }

        return new RedirectResponse($redirectUrl);
    }

    public function finalize(Request $request, PaymentTransactionStruct $transaction, Context $context): void {}

    abstract protected function sendReturnUrlToCheckout(PaymentTransactionStruct $transaction, Context $context, OrderEntity $order): string;

    protected function getCurrencyIso(OrderEntity $order): string
    {
        $currency = $order->getCurrency();

        if ($currency !== null && method_exists($currency, 'getIsoCode')) {
            return (string) $currency->getIsoCode();
        }

        return 'USD';
    }

    private function loadOrder(?string $orderId, Context $context, string $orderTransactionId): OrderEntity
    {
        if ($orderId === null) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'The order transaction does not contain an order identifier.'
            );
        }

        $criteria = (new Criteria([$orderId]))
            ->addAssociation('currency');
        $order = $this->orderRepository->search($criteria, $context)->first();

        if (! $order instanceof OrderEntity) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                sprintf('Unable to load order %s for payment processing.', $orderId)
            );
        }

        return $order;
    }
}
