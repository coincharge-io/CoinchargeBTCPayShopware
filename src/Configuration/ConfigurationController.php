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

use Coincharge\Shopware\PaymentMethod\PaymentMethodInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
abstract class ConfigurationController extends AbstractController implements ConfigurationControllerInterface
{
    public function __construct(protected readonly EntityRepository $paymentMethodRepository)
    {
    }

    abstract public function verifyApiKey(Request $request, Context $context): JsonResponse;

    protected function updatePaymentMethodStatus(Context $context, string $paymentMethodClass, bool $isActive): void
    {
        if (!\is_subclass_of($paymentMethodClass, PaymentMethodInterface::class)) {
            return;
        }

        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = new $paymentMethodClass();
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('handlerIdentifier', $paymentMethod->getPaymentHandler()));

        $paymentMethodId = $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
        if ($paymentMethodId === null) {
            return;
        }

        $this->paymentMethodRepository->update([
            [
                'id' => $paymentMethodId,
                'active' => $isActive,
            ],
        ], $context);
    }
}
