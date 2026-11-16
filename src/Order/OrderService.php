<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Order;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

class OrderService
{
    public const INVOICE_IDS_FIELD = 'coinchargeInvoiceIds';

    private EntityRepository $orderRepository;
    private EntityRepository $orderTransactionRepository;

    public function __construct(EntityRepository $orderRepository, EntityRepository $orderTransactionRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    // The transaction with its order, currency and current state, or null.
    public function getTransaction(string $transactionId, Context $context): ?OrderTransactionEntity
    {
        if (!Uuid::isValid($transactionId)) {
            return null;
        }

        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('stateMachineState');
        $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        return $transaction instanceof OrderTransactionEntity ? $transaction : null;
    }

    // Records every invoice created for a transaction; a customer can start a payment twice.
    public function rememberInvoiceId(OrderTransactionEntity $transaction, string $invoiceId, Context $context): void
    {
        $known = $transaction->getCustomFields()[self::INVOICE_IDS_FIELD] ?? [];
        $known = is_array($known) ? $known : [];
        $known[] = $invoiceId;

        $this->orderTransactionRepository->update([[
            'id' => $transaction->getId(),
            'customFields' => [self::INVOICE_IDS_FIELD => array_values(array_unique($known))],
        ]], $context);
    }

    public function setOrderStatus(string $orderId, string $invoiceId, string $status, Context $context): void
    {
        $this->orderRepository->upsert([[
            'id' => $orderId,
            'customFields' => [
                'coinsnapInvoiceId' => $invoiceId,
                'coinsnapOrderStatus' => $status,
            ],
        ]], $context);
    }
}
