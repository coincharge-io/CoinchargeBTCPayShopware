<?php

declare(strict_types=1);

/**
 * Copyright (c) 2025 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Configuration;

use Coincharge\Shopware\Configuration\Service\BTCPayIntegrationService;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class BTCPayConfigurationController extends ConfigurationController
{
    private BTCPayIntegrationService $integrationService;

    private ConfigurationService $configurationService;

    public function __construct(BTCPayIntegrationService $integrationService, ConfigurationService $configurationService)
    {
        $this->integrationService = $integrationService;
        $this->configurationService = $configurationService;
    }

    #[Route(path: '/api/_action/coincharge/verify', name: 'api.action.coincharge.verify.webhook', methods: ['GET'])]
    public function verifyApiKey(Request $request, Context $context): JsonResponse
    {
        $result = $this->integrationService->verify($request, $context);

        $statusCode = $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

        return new JsonResponse($result, $statusCode);
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
}
