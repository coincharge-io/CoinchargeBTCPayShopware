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

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Coincharge\Shopware\Client\ClientInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

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
        $url =  "$appUrl/checkout/finish?orderId=";
        $this->baseSuccessUrl = $url;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        try {
            $orderTransaction = $transaction->getOrderTransaction();
            $orderId = $orderTransaction->getOrderId();
            $order = $this->loadOrder($orderId, $salesChannelContext->getContext(), $orderTransaction->getId());

            $redirectUrl = $this->sendReturnUrlToCheckout($transaction, $salesChannelContext, $order);
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
        return new RedirectResponse($redirectUrl);
    }

    //Webhook handles this part
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
    }
    abstract protected function sendReturnUrlToCheckout(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $context, OrderEntity $order): string;

    private function loadOrder(?string $orderId, Context $context, string $orderTransactionId): OrderEntity
    {
        if ($orderId === null) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'The order transaction does not contain an order identifier.'
            );
        }

        $criteria = new Criteria([$orderId]);
        $order = $this->orderRepository->search($criteria, $context)->first();

        if (!$order instanceof OrderEntity) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                sprintf('Unable to load order %s for payment processing.', $orderId)
            );
        }

        return $order;
    }
}
