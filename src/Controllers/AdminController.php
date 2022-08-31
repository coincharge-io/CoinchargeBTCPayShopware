<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Controllers;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Context;
use Coincharge\ShopwareBTCPay\Service\ConfigurationService;


/**
 * @RouteScope(scopes={"api"})
 */

class AdminController extends AbstractController
{
    private ConfigurationService  $configurationService;
    
    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;

    }
    /**
     * @Route("/api/_action/btcpay/webhook", name="api.action.btcpay.webhook", methods={"POST"})
     */
    public function generateWebhook(Request $request, Context $context)
    {
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token '.$this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);

        $response = $client->request('POST', $this->configurationService->getSetting('btcpayServerUrl').'/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/webhooks', [
            'body' => json_encode([
                'url'=>'http://localhost/payment/finalize-transaction'
            ])
        ]);
        if (200 !== $response->getStatusCode()) {
            return new JsonResponse(['success' => false, 'response' => $response]);
        }
        return new JsonResponse(['success' => true, 'response' => $response]);


    }

    /**
     * @Route("/api/_action/btcpay/verify", name="api.action.btcpay.verify.webhook", methods={"GET"})
     */
    public function verifyApiKey()
    {
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token '.$this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);

        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl').'/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/invoices');
        if (200 !== $response->getStatusCode()) {
            return new JsonResponse(['success' => false]);
        }
        return new JsonResponse(['success' => true]);


    }
    
}