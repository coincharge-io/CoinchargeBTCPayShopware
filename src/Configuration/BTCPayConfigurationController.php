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
use Coincharge\Shopware\PaymentMethod\BitcoinCryptoPaymentMethod;
use Coincharge\Shopware\PaymentMethod\BitcoinPaymentMethod;
use Coincharge\Shopware\PaymentMethod\LightningPaymentMethod;
use Coincharge\Shopware\PaymentMethod\LitecoinPaymentMethod;
use Coincharge\Shopware\PaymentMethod\MoneroPaymentMethod;
use Coincharge\Shopware\Webhook\WebhookServiceInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class BTCPayConfigurationController extends ConfigurationController
{
    private ClientInterface $client;

    private ConfigurationService $configurationService;

    private WebhookServiceInterface $webhookService;

    private $paymentRepository;

    public function __construct(ClientInterface $client, ConfigurationService $configurationService, WebhookServiceInterface $webhookService, $paymentRepository)
    {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->webhookService = $webhookService;
        $this->paymentRepository = $paymentRepository;
    }

    #[Route(path: '/api/_action/coincharge/verify', name: 'api.action.coincharge.verify.webhook', methods: ['GET'])]
    public function verifyApiKey(Request $request, Context $context)
    {
        try {
            $uri = '/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/invoices';

            $response = $this->client->sendGetRequest($uri);
            if (! is_array($response)) {
                $this->configurationService->setSetting('integrationStatus', false);

                return new JsonResponse(['success' => false, 'message' => 'Check server url and API key.']);
            }
            if (! $this->webhookService->register($request, null)) {
                $this->configurationService->setSetting('integrationStatus', false);

                return new JsonResponse(['success' => false, 'message' => "There is a temporary problem with BTCPay Server. A webhook can't be created at the moment. Please try later."]);
            }
            $this->configurationService->setSetting('integrationStatus', true);
            $this->checkEnabledPaymentMethodsBTCPayStore($context);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()]);
        }
    }

    #[Route(path: '/api/_action/coincharge/credentials', name: 'api.action.coincharge.update.credentials', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'auth_required' => false, 'csrf_protected' => false])]
    public function updateCredentials(Request $request): RedirectResponse
    {

        $body = $request->request->all();
        $this->configurationService->setSetting('btcpayApiKey', $body['apiKey']);
        $this->configurationService->setSetting('btcpayServerStoreId', explode(':', $body['permissions'][0])[1]);
        $redirectUrl = $request->server->get('APP_URL').'/admin#/sw/extension/config/CoinchargeBTCPayShopware';

        return new RedirectResponse($redirectUrl);
    }

    private function checkEnabledPaymentMethodsBTCPayStore(Context $context)
    {
        $paymentMethods = ['BTC-CHAIN' => 'BTC', 'BTC-LN' => 'Lightning', 'LTC-CHAIN' => 'Litecoin', 'XMR-CHAIN' => 'Monero'];
        $paymentHandlers = ['BTC-CHAIN' => BitcoinPaymentMethod::class, 'BTC-LN' => LightningPaymentMethod::class, 'LTC-CHAIN' => LitecoinPaymentMethod::class, 'XMR-CHAIN' => MoneroPaymentMethod::class];
        $this->disableBTCPaymentMethodsBeforeTest();
        $uri = '/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/payment-methods';
        $response = $this->client->sendGetRequest($uri);
        foreach ($response as $key => $val) {
            $paymentMethodId = null;
            $enabled = false;

            if (is_array($val) && array_key_exists('paymentMethodId', $val)) {
                $paymentMethodId = $val['paymentMethodId'];
                $enabled = (bool) ($val['enabled'] ?? false);
            } elseif (is_string($key)) {
                $paymentMethodId = $key;
                $enabled = (bool) ($val['enabled'] ?? false);
            }

            $normalizedPaymentMethodId = $this->normalizePaymentMethodId($paymentMethodId);
            if (! $normalizedPaymentMethodId || ! array_key_exists($normalizedPaymentMethodId, $paymentMethods)) {
                continue;
            }

            $configName = 'btcpayStorePaymentMethod'.$paymentMethods[$normalizedPaymentMethodId];
            $this->configurationService->setSetting($configName, $enabled);
            $this->updatePaymentMethodStatus(
                $context,
                $paymentHandlers[$normalizedPaymentMethodId],
                $enabled,
                $this->paymentRepository
            );
        }
        $this->updatePaymentMethodStatus($context, BitcoinCryptoPaymentMethod::class, true, $this->paymentRepository);
        //        $this->enableIntegratedPaymentPage($context);
    }

    private function disableBTCPaymentMethodsBeforeTest()
    {
        $this->configurationService->setSetting('btcpayStorePaymentMethodBTC', false);
        $this->configurationService->setSetting('btcpayStorePaymentMethodLightning', false);
        $this->configurationService->setSetting('btcpayStorePaymentMethodLitecoin', false);
        $this->configurationService->setSetting('btcpayStorePaymentMethodMonero', false);
    }

    private function enableIntegratedPaymentPage(Context $context)
    {
        if ($this->configurationService->getSetting('btcpayStorePaymentMethodBTC') == true || $this->configurationService->getSetting('btcpayStorePaymentMethodLightning') == true) {
        }
    }

    private function normalizePaymentMethodId(?string $paymentMethodId): ?string
    {
        if ($paymentMethodId === null) {
            return null;
        }

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
        ];

        return $mapping[$paymentMethodId] ?? $paymentMethodId;
    }
}
