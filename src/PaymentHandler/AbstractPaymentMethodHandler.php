<?php

declare(strict_types=1);

/**
 * Copyright (c) 2025 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\PaymentHandler;

use Coincharge\Shopware\Client\ClientInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Coincharge\Shopware\Order\OrderService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
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

    protected EntityRepository $orderTransactionRepository;

    protected string $baseSuccessUrl;

    public function __construct(
        ClientInterface $client,
        ConfigurationService $configurationService,
        OrderTransactionStateHandler $transactionStateHandler,
        LoggerInterface $logger,
        EntityRepository $orderRepository,
        EntityRepository $orderTransactionRepository
    ) {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderTransactionRepository = $orderTransactionRepository; // ✅ missing line added

        $appUrl = $_SERVER['APP_URL'] ?? '';
        $this->baseSuccessUrl = rtrim($appUrl, '/').'/checkout/finish?orderId=';
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        // This handler currently does not support REFUND or RECURRING via Shopware API
        return false;
    }

    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
    {
        try {
            $redirectUrl = $this->sendReturnUrlToCheckout($transaction, $context);
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransactionId(),
                'An error occurred during the communication with external payment gateway'.PHP_EOL.$e->getMessage()
            );
        }

        return new RedirectResponse($redirectUrl);
    }

    public function finalize(Request $request, PaymentTransactionStruct $transaction, Context $context): void {}

    abstract protected function sendReturnUrlToCheckout(PaymentTransactionStruct $transaction, Context $context): string;

    // Returns the checkout link, after recording the invoice so only its webhooks can pay this transaction.
    protected function checkoutLinkFrom(array $invoice, OrderTransactionEntity $orderTransaction, Context $context): string
    {
        if (empty($invoice['checkoutLink']) || empty($invoice['id']) || ! is_string($invoice['id'])) {
            throw new \RuntimeException('The payment gateway did not return a checkout link.');
        }

        $known = $orderTransaction->getCustomFields()[OrderService::INVOICE_IDS_FIELD] ?? [];
        $known = is_array($known) ? $known : [];
        $known[] = $invoice['id'];

        $this->orderTransactionRepository->update([[
            'id' => $orderTransaction->getId(),
            'customFields' => [OrderService::INVOICE_IDS_FIELD => array_values(array_unique($known))],
        ]], $context);

        return $invoice['checkoutLink'];
    }

    protected function loadOrderByTransactionId(string $orderTransactionId, Context $context): OrderEntity
    {
        // Load the OrderTransaction entity and include its related Order
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('order.transactions');
        $criteria->addAssociation('order.addresses');

        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if (! $orderTransaction) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                sprintf('Order transaction %s could not be found.', $orderTransactionId)
            );
        }

        $order = $orderTransaction->getOrder();

        if (! $order instanceof OrderEntity) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                sprintf('No order found for transaction %s.', $orderTransactionId)
            );
        }

        return $order;
    }
}
