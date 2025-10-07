<?php

declare(strict_types=1);

/**
 * Copyright (c) 2025 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Configuration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class PaymentMethodManager
{
    private EntityRepository $paymentMethodRepository;

    public function __construct(EntityRepository $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function setActive(string $paymentMethodClass, bool $active, Context $context): void
    {
        $paymentMethodId = $this->getPaymentMethodId($paymentMethodClass, $context);

        if ($paymentMethodId === null) {
            return;
        }

        $this->paymentMethodRepository->update([
            [
                'id' => $paymentMethodId,
                'active' => $active,
            ],
        ], $context);
    }

    private function getPaymentMethodId(string $paymentMethodClass, Context $context): ?string
    {
        $paymentMethod = new $paymentMethodClass();
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('handlerIdentifier', $paymentMethod->getPaymentHandler()))
            ->setLimit(1);

        return $this->paymentMethodRepository
            ->searchIds($criteria, $context)
            ->firstId();
    }
}
