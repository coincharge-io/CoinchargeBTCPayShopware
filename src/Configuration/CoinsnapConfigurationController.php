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

use Coincharge\Shopware\Configuration\Service\CoinsnapIntegrationService;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

#[Route(defaults: ['_routeScope' => ['api']])]
class CoinsnapConfigurationController extends ConfigurationController
{
    private CoinsnapIntegrationService $integrationService;

    public function __construct(CoinsnapIntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    #[Route(path: '/api/_action/coincharge/coinsnap_verify', name: 'api.action.coincharge.coinsnap_verify', methods: ['GET'])]
    public function verifyApiKey(Request $request, Context $context): JsonResponse
    {
        $result = $this->integrationService->verify($request, $context);

        $statusCode = $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

        return new JsonResponse($result, $statusCode);
    }

    #[Route(path: '/api/_action/coincharge/coinsnap/webhook', name: 'api.action.coincharge.coinsnap.webhook.register', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'auth_required' => false])]
    public function registerWebhook(Request $request): JsonResponse
    {
        $result = $this->integrationService->reRegisterWebhook($request);

        $statusCode = $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

        return new JsonResponse($result, $statusCode);
    }
}
