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

use Coincharge\Shopware\Client\ClientInterface;
use Coincharge\Shopware\Webhook\WebhookServiceInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Coincharge\Shopware\PaymentMethod\{CoinsnapLightningPaymentMethod, CoinsnapBitcoinPaymentMethod, CoinsnapBitcoinLightningPaymentMethod};

#[Route(defaults: ['_routeScope' => ['api']])]
class CoinsnapConfigurationController extends ConfigurationController
{
    private const INTEGRATION_STATUS_KEY = 'coinsnapIntegrationStatus';

    public function __construct(
        private readonly ClientInterface $client,
        private readonly ConfigurationService $configurationService,
        private readonly WebhookServiceInterface $webhookService,
        EntityRepository $paymentRepository,
    ) {
        parent::__construct($paymentRepository);
    }

    #[Route(path: '/api/_action/coincharge/coinsnap_verify', name: 'api.action.coincharge.coinsnap_verify', methods: ['GET'])]
    public function verifyApiKey(Request $request, Context $context): JsonResponse
    {
        try {
            $uri = '/api/v1/stores/' . $this->configurationService->getSetting('coinsnapStoreId');
            $response = $this->client->sendGetRequest($uri);

            if (!\is_array($response)) {
                $this->configurationService->setSetting(self::INTEGRATION_STATUS_KEY, false);

                return new JsonResponse(['success' => false, 'message' => 'Check server url and API key.']);
            }

            if (!$this->webhookService->register($request, null)) {
                $this->configurationService->setSetting(self::INTEGRATION_STATUS_KEY, false);

                return new JsonResponse([
                    'success' => false,
                    'message' => 'There is a temporary problem with Coinsnap server. A webhook cannot be created at the moment. Please try later.',
                ]);
            }

            $this->updatePaymentMethodStatus($context, CoinsnapLightningPaymentMethod::class, true);
            $this->updatePaymentMethodStatus($context, CoinsnapBitcoinPaymentMethod::class, true);
            $this->updatePaymentMethodStatus($context, CoinsnapBitcoinLightningPaymentMethod::class, true);

            $this->configurationService->setSetting(self::INTEGRATION_STATUS_KEY, true);

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $exception) {
            $this->configurationService->setSetting(self::INTEGRATION_STATUS_KEY, false);

            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $exception->getMessage(),
            ]);
        }
    }
}
