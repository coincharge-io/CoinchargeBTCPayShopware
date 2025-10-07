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
     * @return array{success: bool, message?: string, lastVerifiedAt?: string, webhookStatus?: string}
     */
    public function verify(Request $request, Context $context): array
    {
        if (! $this->hasValidCredentials()) {
            return ['success' => false, 'message' => 'Check server url and API key.'];
        }

        $webhookResult = $this->registerWebhook($request);
        if ($webhookResult['success'] === false) {
            return $webhookResult;
        }

        $paymentStatus = $this->activatePaymentMethods($context);
        $checkedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);

        $this->configurationService->setSetting('coinsnapIntegrationStatus', true);
        $this->configurationService->setSetting('coinsnapLastVerifiedAt', $checkedAt);

        return [
            'success' => true,
            'lastVerifiedAt' => $checkedAt,
            'webhookStatus' => $webhookResult['webhookStatus'] ?? 'registered',
            'paymentStatus' => $paymentStatus,
        ];
    }

    private function hasValidCredentials(): bool
    {
        try {
            $uri = '/api/v1/stores/'.$this->configurationService->getSetting('coinsnapStoreId');
            $response = $this->client->sendGetRequest($uri);

            if (! \is_array($response)) {
                $this->configurationService->setSetting('coinsnapIntegrationStatus', false);
                $this->configurationService->setSetting('coinsnapPaymentStatus', []);

                return false;
            }

            return true;
        } catch (\Throwable $throwable) {
            $this->configurationService->setSetting('coinsnapIntegrationStatus', false);
            $this->configurationService->setSetting('coinsnapPaymentStatus', []);
            $this->logger->error('Coinsnap credential verification failed', ['exception' => $throwable]);

            return false;
        }
    }

    /**
     * @return array<string, bool>
     */
    private function activatePaymentMethods(Context $context): array
    {
        $status = [
            'Lightning' => true,
            'Bitcoin' => true,
            'BitcoinLightning' => true,
        ];

        $this->paymentMethodManager->setActive(CoinsnapLightningPaymentMethod::class, true, $context);
        $this->paymentMethodManager->setActive(CoinsnapBitcoinPaymentMethod::class, true, $context);
        $this->paymentMethodManager->setActive(CoinsnapBitcoinLightningPaymentMethod::class, true, $context);
        $this->configurationService->setSetting('coinsnapPaymentStatus', $status);

        return $status;
    }

    /**
     * @return array{success: bool, message?: string, webhookStatus: string, registeredAt?: string}
     */
    public function reRegisterWebhook(Request $request): array
    {
        return $this->registerWebhook($request);
    }

    /**
     * @return array{success: bool, message?: string, webhookStatus: string, registeredAt?: string}
     */
    private function registerWebhook(Request $request): array
    {
        $registered = false;

        try {
            $registered = $this->webhookService->register($request, null);
        } catch (\Throwable $throwable) {
            $this->logger->error('Coinsnap webhook registration failed', ['exception' => $throwable]);
        }

        $status = $registered ? 'registered' : 'error';
        $this->configurationService->setSetting('coinsnapWebhookStatus', $status);

        if ($registered) {
            $timestamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);
            $this->configurationService->setSetting('coinsnapLastWebhookRegistration', $timestamp);

            return [
                'success' => true,
                'webhookStatus' => $status,
                'registeredAt' => $timestamp,
            ];
        }

        return [
            'success' => false,
            'message' => "There is a temporary problem with Coinsnap Server. A webhook can't be created at the moment. Please try later.",
            'webhookStatus' => $status,
        ];
    }
}
