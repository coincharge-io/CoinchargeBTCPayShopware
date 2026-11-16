<?php


declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Webhook;

use Coincharge\Shopware\Webhook\Factory\WebhookFactory;
use Coincharge\Shopware\Webhook\BTCPayWebhookService;
use Coincharge\Shopware\Webhook\CoinsnapWebhookService;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookRouter
{
    private WebhookFactory $webhookFactory;
    private ConfigurationService $configurationService;

    public function __construct(WebhookFactory $webhookFactory, ConfigurationService $configurationService)
    {
        $this->webhookFactory = $webhookFactory;
        $this->configurationService = $configurationService;
    }
    public function route(Request $request, Context $context): Response
    {
        $provider = $this->getProviderFromRequest($request);
        if ($provider !== null) {
            $webhook = $this->webhookFactory->create($provider);
            return $webhook->process($request, $context);
        }

        // Unidentified, ambiguous, or not-connected provider. Respond with a
        // generic 401 rather than throwing (which would surface as a 500 on
        // this unauthenticated route), and never reveal which check failed.
        return new Response(
            json_encode(['error' => 'Unauthorized']),
            Response::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/json']
        );
    }
    public function getProviderFromRequest(Request $request): ?string
    {
        // Exactly one known signature header must be present, and that provider
        // must be connected (its webhook secret is set). Reject ambiguous,
        // unidentified, or not-connected requests instead of defaulting.
        $hasBtcpay = $request->headers->has(BTCPayWebhookService::REQUIRED_HEADER);
        $hasCoinsnap = $request->headers->has(CoinsnapWebhookService::REQUIRED_HEADER);

        if ($hasBtcpay === $hasCoinsnap) {
            return null;
        }

        if ($hasBtcpay) {
            return empty($this->configurationService->getSetting('btcpayWebhookSecret')) ? null : 'btcpay';
        }

        return empty($this->configurationService->getSetting('coinsnapWebhookSecret')) ? null : 'coinsnap';
    }
}
