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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Coincharge\Shopware\PaymentMethod\{
    BitcoinCryptoPaymentMethod,
    LightningPaymentMethod,
    BitcoinPaymentMethod,
    LitecoinPaymentMethod,
    MoneroPaymentMethod,
    BitcoinLightningPaymentMethod
};

#[Route(defaults: ['_routeScope' => ['api']])]
class BTCPayConfigurationController extends ConfigurationController
{
    private const INTEGRATION_STATUS_KEY = 'integrationStatus';

    public function __construct(
        private readonly ClientInterface $client,
        private readonly ConfigurationService $configurationService,
        private readonly WebhookServiceInterface $webhookService,
        EntityRepository $paymentRepository,
    ) {
        parent::__construct($paymentRepository);
    }

    #[Route(path: '/api/_action/coincharge/verify', name: 'api.action.coincharge.verify.webhook', methods: ['GET'])]
    public function verifyApiKey(Request $request, Context $context): JsonResponse
    {
        try {
            $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices';
            $response = $this->client->sendGetRequest($uri);

            if (!\is_array($response)) {
                $this->configurationService->setSetting(self::INTEGRATION_STATUS_KEY, false);

                return new JsonResponse(['success' => false, 'message' => 'Check server url and API key.']);
            }

            if (!$this->webhookService->register($request, null)) {
                $this->configurationService->setSetting(self::INTEGRATION_STATUS_KEY, false);

                return new JsonResponse([
                    'success' => false,
                    'message' => 'There is a temporary problem with BTCPay Server. A webhook cannot be created at the moment. Please try later.',
                ]);
            }

            $this->configurationService->setSetting(self::INTEGRATION_STATUS_KEY, true);
            $this->synchroniseBtcpayPaymentMethods($context);

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $exception) {
            $this->configurationService->setSetting(self::INTEGRATION_STATUS_KEY, false);

            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $exception->getMessage(),
            ]);
        }
    }
    #[Route(path: '/api/_action/coincharge/credentials', name: 'api.action.coincharge.update.credentials', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'auth_required' => false, 'csrf_protected' => false])]
    public function updateCredentials(Request $request): RedirectResponse
    {
        $parameters = $request->request->all();

        $apiKey = $parameters['apiKey'] ?? null;
        $permissions = $parameters['permissions'][0] ?? null;
        $storeId = null;

        if (\is_string($permissions) && str_contains($permissions, ':')) {
            [, $storeId] = explode(':', $permissions, 2);
        }

        if (!\is_string($apiKey) || $apiKey === '' || !\is_string($storeId) || $storeId === '') {
            return new RedirectResponse($this->resolveConfigRedirectUrl($request, false));
        }

        $this->configurationService->setSetting('btcpayApiKey', $apiKey);
        $this->configurationService->setSetting('btcpayServerStoreId', $storeId);

        return new RedirectResponse($this->resolveConfigRedirectUrl($request, true));
    }

    private function synchroniseBtcpayPaymentMethods(Context $context): void
    {
        $paymentMethodMapping = [
            'BTC-CHAIN' => ['configKeySuffix' => 'BTC', 'handler' => BitcoinPaymentMethod::class],
            'BTC-LN' => ['configKeySuffix' => 'Lightning', 'handler' => LightningPaymentMethod::class],
            'LTC-CHAIN' => ['configKeySuffix' => 'Litecoin', 'handler' => LitecoinPaymentMethod::class],
            'XMR-CHAIN' => ['configKeySuffix' => 'Monero', 'handler' => MoneroPaymentMethod::class],
        ];

        $this->resetConfiguredPaymentMethods();

        $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/payment-methods';
        $response = $this->client->sendGetRequest($uri);

        if (!\is_array($response)) {
            return;
        }

        foreach ($paymentMethodMapping as $remoteCode => $localConfig) {
            $enabled = (bool)($response[$remoteCode]['enabled'] ?? false);
            $this->configurationService->setSetting('btcpayStorePaymentMethod' . $localConfig['configKeySuffix'], $enabled);
            $this->updatePaymentMethodStatus($context, $localConfig['handler'], $enabled);
        }

        $this->updatePaymentMethodStatus($context, BitcoinCryptoPaymentMethod::class, true);
    }

    private function resetConfiguredPaymentMethods(): void
    {
        foreach (['BTC', 'Lightning', 'Litecoin', 'Monero'] as $suffix) {
            $this->configurationService->setSetting('btcpayStorePaymentMethod' . $suffix, false);
        }
    }

    private function resolveConfigRedirectUrl(Request $request, bool $success): string
    {
        $baseUrl = (string)$request->server->get('APP_URL', '');
        $query = $success ? '' : '?error=invalid_credentials';

        return rtrim($baseUrl, '/') . '/admin#/sw/extension/config/CoinchargeBTCPayShopware' . $query;
    }
}
