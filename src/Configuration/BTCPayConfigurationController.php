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
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class BTCPayConfigurationController extends ConfigurationController
{
    private BTCPayIntegrationService $integrationService;

    public function __construct(BTCPayIntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    #[Route(path: '/api/_action/coincharge/verify', name: 'api.action.coincharge.verify.webhook', methods: ['GET'])]
    public function verifyApiKey(Request $request, Context $context): JsonResponse
    {
        $result = $this->integrationService->verify($request, $context);

        $statusCode = $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

        return new JsonResponse($result, $statusCode);
    }

    #[Route(path: '/api/_action/coincharge/verify/webhook', name: 'api.action.coincharge.verify.webhook.register', methods: ['POST'], defaults: ['XmlHttpRequest' => true])]
    public function registerWebhook(Request $request): JsonResponse
    {
        $result = $this->integrationService->reRegisterWebhook($request);

        $statusCode = $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

        return new JsonResponse($result, $statusCode);
    }

    // BTCPay posts the key back through a browser navigation without the admin token, so this route
    // stays public and is guarded by the one-time state the admin created before leaving.
    #[Route(path: '/api/_action/coincharge/credentials', name: 'api.action.coincharge.update.credentials', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'auth_required' => false])]
    public function updateCredentials(Request $request): RedirectResponse
    {
        $this->integrationService->acceptCredentials($request->query->get('state'), $request->request->get('apiKey'));

        return new RedirectResponse($request->server->get('APP_URL').'/admin#/sw/extension/config/CoinchargeBTCPayShopware');
    }
}
