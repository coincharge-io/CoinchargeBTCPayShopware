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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Coincharge\Shopware\Webhook\WebhookServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Context;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */


class WebhookController extends AbstractController
{
    private WebhookServiceInterface $webhookService;

    public function __construct(WebhookServiceInterface $webhookService)
    {
        $this->webhookService = $webhookService;
    }
    /**
     * @Route("/api/_action/coincharge/webhook-endpoint", name="api.action.coincharge.webhook.endpoint", defaults={"csrf_protected"=false, "XmlHttpRequest"=true, "auth_required"=false}, methods={"POST"})
     */
    public function endpoint(Request $request, Context $context): Response
    {
        return $this->webhookService->executeWebhook($request, $context);
    }
}
