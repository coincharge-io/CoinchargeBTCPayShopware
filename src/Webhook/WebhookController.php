<?php

declare(strict_types=1);

namespace Coincharge\Shopware\Webhook;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Coincharge\Shopware\Webhook\WebhookServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;



/**
 * @RouteScope(scopes={"api"})
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
    public function endpoint(Request $request, Context $context):Response
    {
        $this->webhookService->executeWebhook($request,  $context);
        return new Response();
    }
}
