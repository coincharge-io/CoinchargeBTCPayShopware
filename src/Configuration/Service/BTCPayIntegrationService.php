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
use Coincharge\Shopware\PaymentMethod\BitcoinLightningPaymentMethod;
use Coincharge\Shopware\PaymentMethod\BitcoinCryptoPaymentMethod;
use Coincharge\Shopware\PaymentMethod\BitcoinPaymentMethod;
use Coincharge\Shopware\PaymentMethod\LitecoinPaymentMethod;
use Coincharge\Shopware\PaymentMethod\MoneroPaymentMethod;
use Coincharge\Shopware\PaymentMethod\UsdtPaymentMethod;
use Coincharge\Shopware\Webhook\WebhookServiceInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;

class BTCPayIntegrationService
{
    /** @var array<string, string> */
    private const PAYMENT_METHODS = [
        'BTC-CHAIN' => BitcoinPaymentMethod::class,
        'BTC-LN' => BitcoinLightningPaymentMethod::class,
        'LTC-CHAIN' => LitecoinPaymentMethod::class,
        'XMR-CHAIN' => MoneroPaymentMethod::class,
        'USDT-TRON' => UsdtPaymentMethod::class,
        'USDT' => UsdtPaymentMethod::class,
    ];

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
            return ['success' => false, 'message' => "There is a temporary problem with BTCPay Server. A webhook can't be created at the moment. Please try later."];
        }

        $this->synchronisePaymentMethods($context);

        $this->configurationService->setSetting('integrationStatus', true);

        return ['success' => true];
    }

    private function hasValidCredentials(): bool
    {
        try {
            $uri = '/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/invoices';
            $response = $this->client->sendGetRequest($uri);

            if (! \is_array($response)) {
                $this->configurationService->setSetting('integrationStatus', false);

                return false;
            }

            return true;
        } catch (\Throwable $throwable) {
            $this->configurationService->setSetting('integrationStatus', false);
            $this->logger->error('BTCPay credential verification failed', ['exception' => $throwable]);

            return false;
        }
    }

    private function synchronisePaymentMethods(Context $context): void
    {
        $this->disablePaymentFlags();

        try {
            $status = $this->fetchRemotePaymentStatus();
        } catch (\Throwable $throwable) {
            $this->logger->error('Unable to fetch BTCPay payment methods', ['exception' => $throwable]);

            return;
        }

        foreach ($status as $remoteCode => $enabled) {
            if (! isset(self::PAYMENT_METHODS[$remoteCode])) {
                continue;
            }

            $configKey = 'btcpayStorePaymentMethod'.$this->normaliseConfigSuffix($remoteCode);
            $this->configurationService->setSetting($configKey, $enabled);
            $this->paymentMethodManager->setActive(self::PAYMENT_METHODS[$remoteCode], $enabled, $context);
        }

        $this->paymentMethodManager->setActive(BitcoinCryptoPaymentMethod::class, true, $context);
    }

    /**
     * @return array<string, bool>
     */
    private function fetchRemotePaymentStatus(): array
    {
        $uri = '/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/payment-methods';
        $response = $this->client->sendGetRequest($uri);

        $status = [];

        foreach ($response as $key => $value) {
            if (\is_array($value) && isset($value['paymentMethodId'])) {
                $normalised = $this->normaliseMethodId($value['paymentMethodId']);
                if ($normalised !== null) {
                    $status[$normalised] = (bool) ($value['enabled'] ?? false);
                }
                continue;
            }

            if (\is_string($key)) {
                $normalised = $this->normaliseMethodId($key);
                if ($normalised !== null) {
                    $status[$normalised] = (bool) ($value['enabled'] ?? false);
                }
            }
        }

        return $status;
    }

    private function normaliseMethodId(string $paymentMethodId): ?string
    {
        $mapping = [
            'BTC' => 'BTC-CHAIN',
            'BTC-OnChain' => 'BTC-CHAIN',
            'BTC_BitcoinLike' => 'BTC-CHAIN',
            'BTC-BitcoinLike' => 'BTC-CHAIN',
            'BTC-BTCLike' => 'BTC-CHAIN',
            'BTC-LN' => 'BTC-LN',
            'BTC-LightningNetwork' => 'BTC-LN',
            'BTC_LightningLike' => 'BTC-LN',
            'BTC_OffChain' => 'BTC-LN',
            'BTC_Off-Chain' => 'BTC-LN',
            'LTC' => 'LTC-CHAIN',
            'LTC-LitecoinLike' => 'LTC-CHAIN',
            'LTC_LitecoinLike' => 'LTC-CHAIN',
            'XMR' => 'XMR-CHAIN',
            'XMR-MoneroLike' => 'XMR-CHAIN',
            'XMR_MoneroLike' => 'XMR-CHAIN',
            'USDt' => 'USDT-TRON',
            'USDt-StablecoinLike' => 'USDT-TRON',
            'USDt_StablecoinLike' => 'USDT-TRON',
            'USDT-TRON' => 'USDT-TRON',
            'USDT_TRON' => 'USDT-TRON',
            'USDT' => 'USDT-TRON',
            'USDT-StablecoinLike' => 'USDT-TRON',
            'USDT_StablecoinLike' => 'USDT-TRON',
        ];

        return $mapping[$paymentMethodId] ?? $paymentMethodId;
    }

    private function normaliseConfigSuffix(string $paymentMethodId): string
    {
        return match ($paymentMethodId) {
            'BTC-CHAIN' => 'BTC',
            'BTC-LN' => 'Lightning',
            'LTC-CHAIN' => 'Litecoin',
            'XMR-CHAIN' => 'Monero',
            'USDT-TRON', 'USDT' => 'USDT',
            default => $paymentMethodId,
        };
    }

    private function disablePaymentFlags(): void
    {
        $this->configurationService->setSetting('btcpayStorePaymentMethodBTC', false);
        $this->configurationService->setSetting('btcpayStorePaymentMethodLightning', false);
        $this->configurationService->setSetting('btcpayStorePaymentMethodLitecoin', false);
        $this->configurationService->setSetting('btcpayStorePaymentMethodMonero', false);
        $this->configurationService->setSetting('btcpayStorePaymentMethodUSDT', false);
    }
}
