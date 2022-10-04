<?php

declare(strict_types=1);

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


/**
 * @RouteScope(scopes={"api"})
 */

class ConfigurationController extends AbstractController
{
    private BTCPayServerClientInterface $client;
    private ConfigurationService $configurationService;
    private WebhookServiceInterface $webhookService;

    public function __construct(BTCPayServerClientInterface $client, ConfigurationService $configurationService, WebhookServiceInterface $webhookService)
    {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->webhookService = $webhookService;
    }

    /**
     * @Route("/api/_action/coincharge/verify", name="api.action.coincharge.verify.webhook", methods={"GET"})
     */
    public function verifyApiKey(Request $request)
    {
        /* $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);

        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices');
 */
        /* $this->configurationService->setSetting('integrationStatus', false);
        return; */
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

        return new JsonResponse(['success' => true]);
    }
    /**
     * @Route("/api/_action/coincharge/credentials", name="api.action.coincharge.update.credentials", defaults={"csrf_protected"=false, "XmlHttpRequest"=true, "auth_required"=false}, methods={"POST"})
     */
    public function updateCredentials(Request $request): RedirectResponse
    {

        $body = $request->request->all();

        if ($body['apiKey']) {
            $this->configurationService->setSetting('btcpayApiKey', $body['apiKey']);
        }
        if ($body['permissions']) {
            $this->configurationService->setSetting('btcpayServerStoreId', explode(':', $body['permissions'][0])[1]);
        }
        $this->configurationService->setSetting('btcpayWebhookSecret', '');
        $this->configurationService->setSetting('btcpayWebhookId', '');
        $this->webhookService->registerWebhook($request, null);
        
        $redirectUrl = $request->server->get('REQUEST_SCHEME') . '://' . $request->server->get('HTTP_HOST') . '/admin#/sw/extension/config/BTCPay';

        return new RedirectResponse($redirectUrl);
    }
}
