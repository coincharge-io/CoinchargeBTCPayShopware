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
use Coincharge\Shopware\Client\BTCPayServerClientInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Coincharge\Shopware\Webhook\WebhookServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Coincharge\Shopware\PaymentMethod\{LightningPaymentMethod, BitcoinPaymentMethod};


/**
 * @RouteScope(scopes={"api"})
 */

class ConfigurationController extends AbstractController
{
    private BTCPayServerClientInterface $client;
    private ConfigurationService $configurationService;
    private WebhookServiceInterface $webhookService;
    private $paymentRepository;

    public function __construct(BTCPayServerClientInterface $client, ConfigurationService $configurationService, WebhookServiceInterface $webhookService, $paymentRepository)
    {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->webhookService = $webhookService;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @Route("/api/_action/coincharge/verify", name="api.action.coincharge.verify.webhook", methods={"GET"})
     */
    public function verifyApiKey(Request $request, Context $context)
    {
        $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices';

        $response = $this->client->sendGetRequest($uri);

        if (!is_array($response)) {
            $this->configurationService->setSetting('integrationStatus', false);
            return new JsonResponse(['success' => false, 'message' => 'Check server url and API key.']);
        }
        if (!$this->webhookService->registerWebhook($request, null)) {
            $this->configurationService->setSetting('integrationStatus', false);
            return new JsonResponse(['success' => false, 'message' => "There is a temporary problem with BTCPay Server. A webhook can't be created at the moment. Please try later."]);
        }
        $this->configurationService->setSetting('integrationStatus', true);
        $this->checkEnabledPaymentMethodsBTCPayStore($context);
        return new JsonResponse(['success' => true]);
    }
    /**
     * @Route("/api/_action/coincharge/credentials", name="api.action.coincharge.update.credentials", defaults={"csrf_protected"=false, "XmlHttpRequest"=true, "auth_required"=false}, methods={"POST"})
     */
    public function updateCredentials(Request $request): RedirectResponse
    {

        $body = $request->request->all();
        $this->configurationService->setSetting('btcpayApiKey', $body['apiKey']);

        $this->configurationService->setSetting('btcpayServerStoreId', explode(':', $body['permissions'][0])[1]);

        $redirectUrl = $request->server->get('REQUEST_SCHEME') . '://' . $request->server->get('HTTP_HOST') . '/admin#/sw/extension/config/BTCPay';

        return new RedirectResponse($redirectUrl);
    }

    private function checkEnabledPaymentMethodsBTCPayStore(Context $context)
    {
        $paymentMethods = ['BTC' => 'BTC', 'BTC-LightningNetwork' => 'Lightning'];
        $paymentHandlers = ['BTC' => BitcoinPaymentMethod::class, 'BTC-LightningNetwork' => LightningPaymentMethod::class];
        $this->disableBTCPaymentMethodsBeforeTest();
        $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/payment-methods';
        $response = $this->client->sendGetRequest($uri);
        foreach ($response as $key => $val) {
            if (array_key_exists($key, $paymentMethods)) {
                $configName = 'btcpayStorePaymentMethod' . $paymentMethods[$key];
                $this->configurationService->setSetting($configName, $val['enabled']);
                $this->updatePaymentMethodStatus($context, $paymentHandlers[$key], $val['enabled']);
            }
        }
    }
    private function updatePaymentMethodStatus(Context $context, string $paymentMethod, bool $status)
    {
        $paymentMethodClass = new $paymentMethod();
        $paymentCriteria = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', $paymentMethodClass->getPaymentHandler()));
        $paymentMethodId = $this->paymentRepository->searchIds($paymentCriteria, Context::createDefaultContext())->firstId();
        // Payment does not even exist, so nothing to (de-)activate here
        if (!$paymentMethodId) {
            return;
        }

        $paymentMethod = [
            'id' => $paymentMethodId,
            'active' => $status,
        ];
        $this->paymentRepository->update([$paymentMethod], $context);
    }
    private function disableBTCPaymentMethodsBeforeTest()
    {
        $this->configurationService->setSetting('BTC', false);
        $this->configurationService->setSetting('Lightning', false);
    }
}
