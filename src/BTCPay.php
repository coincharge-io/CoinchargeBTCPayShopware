<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\File\FileSaver;
use Coincharge\Shopware\PaymentMethod\PaymentMethods;

class BTCPay extends Plugin
{
    public function install(InstallContext $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $customFieldSetRepository->upsert(
            [
                [
                    'name' => 'btcpayServer',
                    'config' => [
                        'label' => [
                            'de-DE' => 'BTCPayServer Information',
                            'en-GB' => 'BTCPayServer Information'
                        ]
                    ],
                    'customFields' => [
                        [
                            'name' => 'btcpayOrderStatus',
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'Auftragsstatus',
                                    'en-GB' => 'Order Status'
                                ]
                            ]
                        ],
                        [
                            'name' => 'paidAfterExpiration',
                            'type' => CustomFieldTypes::BOOL,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'Bezahlt nach Ablauf der Rechnung',
                                    'en-GB' => 'Paid After Invoice Expiration'
                                ]
                            ]
                        ],
                        [
                            'name' => 'overpaid',
                            'type' => CustomFieldTypes::BOOL,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'Überbezahlt',
                                    'en-GB' => 'Overpaid '
                                ]
                            ]
                        ],
                    ],
                    'relations' => [[
                        'entityName' => 'order'
                    ]],
                ]
            ],
            $context->getContext()
        );
        foreach (PaymentMethods::PAYMENT_METHODS as $paymentMethod) {
            $this->addPaymentMethod(new $paymentMethod(), $context->getContext());
        }
        //$this->addPaymentMethod($context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        // Only set the payment method to inactive when uninstalling. Removing the payment method would
        // cause data consistency issues, since the payment method might have been used in several orders
        //$this->setPaymentMethodIsActive(false, $context->getContext());
        foreach (PaymentMethods::PAYMENT_METHODS as $paymentMethod) {
            $this->setPaymentMethodIsActive(new $paymentMethod(), false, $context->getContext());
        }
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', ['btcpayServer']));

        $customFieldIds = $customFieldSetRepository->searchIds($criteria, $context->getContext());
        $customFieldSetRepository->delete(array_values($customFieldIds->getData()), $context->getContext());
    }

    public function activate(ActivateContext $context): void
    {
        //$this->setPaymentMethodIsActive(true, $context->getContext());
        /* foreach (PaymentMethods::PAYMENT_METHODS as $paymentMethod) {
            $this->setPaymentMethodIsActive(new $paymentMethod(), true, $context->getContext());
        } */
        parent::activate($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        foreach (PaymentMethods::PAYMENT_METHODS as $paymentMethod) {
            $this->setPaymentMethodIsActive(new $paymentMethod(), false, $context->getContext());
        }
        //$this->setPaymentMethodIsActive(false, $context->getContext());
        parent::deactivate($context);
    }

    private function addPaymentMethod($paymentMethod, Context $context): void
    {
        $paymentMethodExists = $this->getPaymentMethodId($paymentMethod);

        // Payment method exists already, no need to continue here
        if ($paymentMethodExists) {
            return;
        }

        /**
         * @var PluginIdProvider $pluginIdProvider 
         */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        $pluginId = $pluginIdProvider->getPluginIdByBaseClass(get_class($this), $context);

        //TODO integrate 'mediaId' => $this->ensureMedia(),
        $examplePaymentData = [
            'handlerIdentifier' => $paymentMethod->getPaymentHandler(),
            'pluginId' => $pluginId,
            'mediaId' => $this->ensureMedia($context, $paymentMethod->getName()),
            'translations' => $paymentMethod->getTranslations()
        ];

        /**
         * @var EntityRepositoryInterface $paymentRepository 
         */
        $paymentRepository = $this->container->get('payment_method.repository');
        $paymentRepository->create([$examplePaymentData], $context);
    }

    private function setPaymentMethodIsActive($paymentMethod, bool $active, Context $context): void
    {
        /**
         * @var EntityRepositoryInterface $paymentRepository 
         */
        $paymentRepository = $this->container->get('payment_method.repository');

        $paymentMethodId = $this->getPaymentMethodId($paymentMethod);

        // Payment does not even exist, so nothing to (de-)activate here
        if (!$paymentMethodId) {
            return;
        }

        $paymentMethod = [
            'id' => $paymentMethodId,
            'active' => $active,
        ];

        $paymentRepository->update([$paymentMethod], $context);
    }

    private function getPaymentMethodId($paymentMethod): ?string
    {
        /**
         * @var EntityRepositoryInterface $paymentRepository 
         */
        $paymentRepository = $this->container->get('payment_method.repository');

        // Fetch ID for update
        $paymentCriteria = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', $paymentMethod->getPaymentHandler()));
        return $paymentRepository->searchIds($paymentCriteria, Context::createDefaultContext())->firstId();
    }

    private function getMediaEntity(string $fileName, Context $context): ?MediaEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('fileName', $fileName));
        $mediaRepository = $this->container->get('media.repository');

        return $mediaRepository->search($criteria, $context)->first();
    }
    private function ensureMedia(Context $context, string $logoName): string
    {
        $filePath = realpath(__DIR__ . '/Resources/icons/' . strtolower($logoName) . '.svg');
        $fileName = hash_file('md5', $filePath);
        $media = $this->getMediaEntity($fileName, $context);
        $mediaRepository = $this->container->get('media.repository');

        if ($media) {
            return $media->getId();
        }

        $mediaFile = new MediaFile(
            $filePath,
            mime_content_type($filePath),
            pathinfo($filePath, PATHINFO_EXTENSION),
            filesize($filePath)
        );
        $mediaId = Uuid::randomHex();
        $mediaRepository->create(
            [
                [
                    'id' => $mediaId,
                ],
            ],
            $context
        );
        $fileSaver = $this->container->get(FileSaver::class);
        $fileSaver->persistFileToMedia(
            $mediaFile,
            $fileName,
            $mediaId,
            $context
        );

        return $mediaId;
    }
}
