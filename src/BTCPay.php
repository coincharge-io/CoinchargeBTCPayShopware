<?php declare(strict_types=1);

namespace Coincharge\Shopware;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaEntity;

use Coincharge\Shopware\Service\BTCPayServerPayment;

class BTCPay extends Plugin
{
	public function install(InstallContext $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
    
            $customFieldSetRepository->upsert([
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
                            'label' => 'Order Status',
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'Auftragsstatus',
                                    'en-GB' => 'Order Status'
                                ]
                            ]
                        ],
                        [
                            'name' => 'paymentMethod',
                            'label' => 'Payment Method',
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'Zahlungsmethode',
                                    'en-GB' => 'Payment Method'
                                ]
                            ]
                        ],
                        [
                            'name' => 'paidAfterExpiration',
                            'label' => 'Paid After Expiration',
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
                            'label' => 'Received more than expected',
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
            ], $context->getContext()); 
        $this->addPaymentMethod($context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        // Only set the payment method to inactive when uninstalling. Removing the payment method would
        // cause data consistency issues, since the payment method might have been used in several orders
        $this->setPaymentMethodIsActive(false, $context->getContext());
    }

    public function activate(ActivateContext $context): void
    {
        $this->setPaymentMethodIsActive(true, $context->getContext());
        parent::activate($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        $this->setPaymentMethodIsActive(false, $context->getContext());
        parent::deactivate($context);
    }

    private function addPaymentMethod(Context $context): void
    {
        $paymentMethodExists = $this->getPaymentMethodId();

        // Payment method exists already, no need to continue here
        if ($paymentMethodExists) {
            return;
        }

        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        $pluginId = $pluginIdProvider->getPluginIdByBaseClass(get_class($this), $context);
        
        //TODO integrate 'mediaId' => $this->ensureMedia(),
        $examplePaymentData = [
            'handlerIdentifier' => BTCPayServerPayment::class,
            'pluginId' => $pluginId,
            'translations' => [
                'de-DE' => [
                    'name' => 'BTCPayShopware',
                    'description' => 'Zahlen mit Bitcoin'
                ],
                'en-GB' => [
                    'name' => 'BTCPayShopware',
                    'description' => 'Pay with Bitcoin'
                ],
            ],
        ];

        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');
        $paymentRepository->create([$examplePaymentData], $context);
    }

    private function setPaymentMethodIsActive(bool $active, Context $context): void
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        $paymentMethodId = $this->getPaymentMethodId();

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

    private function getPaymentMethodId(): ?string
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        // Fetch ID for update
        $paymentCriteria = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', BTCPayServerPayment::class));
        return $paymentRepository->searchIds($paymentCriteria, Context::createDefaultContext())->firstId();
    }
}
