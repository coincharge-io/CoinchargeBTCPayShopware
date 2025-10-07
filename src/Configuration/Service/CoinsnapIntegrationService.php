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

use Coincharge\Shopware\Client\ClientInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Coincharge\Shopware\PaymentMethod\CoinsnapBitcoinLightningPaymentMethod;
use Coincharge\Shopware\PaymentMethod\CoinsnapBitcoinPaymentMethod;
use Coincharge\Shopware\PaymentMethod\CoinsnapLightningPaymentMethod;
use Coincharge\Shopware\Webhook\WebhookServiceInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;

class CoinsnapIntegrationService
{
    private ClientInterface $client;
    private ConfigurationService $configurationService;
    private WebhookServiceInterface $webhookService;
    private PaymentMethodManager $paymentMethodManager;
    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        ConfigurationService $configurationService,
        WebhookServiceInterface $webhookService,
        PaymentMethodManager $paymentMethodManager,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->webhookService = $webhookService;
        $this->paymentMethodManager = $paymentMethodManager;
        $this->logger = $logger;
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function verify(Request $request, Context $context): array
    {
        if (! $this->hasValidCredentials()) {
            return ['success' => false, 'message' => 'Check server url and API key.'];
        }

        if (! $this->webhookService->register($request, null)) {
            return ['success' => false, 'message' => "There is a temporary problem with Coinsnap Server. A webhook can't be created at the moment. Please try later."];
        }

        $this->activatePaymentMethods($context);
        $this->configurationService->setSetting('coinsnapIntegrationStatus', true);

        return ['success' => true];
    }

    private function hasValidCredentials(): bool
    {
        try {
            $uri = '/api/v1/stores/'.$this->configurationService->getSetting('coinsnapStoreId');
            $response = $this->client->sendGetRequest($uri);

            if (! \is_array($response)) {
                $this->configurationService->setSetting('coinsnapIntegrationStatus', false);

                return false;
            }

            return true;
        } catch (\Throwable $throwable) {
            $this->configurationService->setSetting('coinsnapIntegrationStatus', false);
            $this->logger->error('Coinsnap credential verification failed', ['exception' => $throwable]);

            return false;
        }
    }

    private function activatePaymentMethods(Context $context): void
    {
        $this->paymentMethodManager->setActive(CoinsnapLightningPaymentMethod::class, true, $context);
        $this->paymentMethodManager->setActive(CoinsnapBitcoinPaymentMethod::class, true, $context);
        $this->paymentMethodManager->setActive(CoinsnapBitcoinLightningPaymentMethod::class, true, $context);
    }
}
