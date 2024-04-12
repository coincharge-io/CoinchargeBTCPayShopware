<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Configuration;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Coincharge\Shopware\PaymentMethod\{LightningPaymentMethod, BitcoinPaymentMethod};

#[Route(defaults: ['_routeScope' => ['api']])]
class ConfigurationController extends AbstractController
{
    public function verifyApiKey(Request $request, Context $context)
    {
    }
    protected function updatePaymentMethodStatus(Context $context, string $paymentMethod, bool $status, $paymentRepository)
    {
        $paymentMethodClass = new $paymentMethod();
        $paymentCriteria = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', $paymentMethodClass->getPaymentHandler()));
        $paymentMethodId = $paymentRepository->searchIds($paymentCriteria, Context::createDefaultContext())->firstId();
        if (!$paymentMethodId) {
            return;
        }

        $paymentMethod = [
            'id' => $paymentMethodId,
            'active' => $status,
        ];
        $paymentRepository->update([$paymentMethod], $context);
    }
}
