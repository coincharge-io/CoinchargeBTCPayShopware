<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Controllers;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdminController extends AbstractController
{
    /**
    * @Route("/api/btcpay/coincharge-btcpay/generate-api-key", name="api.action.coincharge-btcpay.generate-api-key", methods={"GET"})
    */
    protected function generateApiKey(string $btcpayServerUrl, string $shopwareSettingsPageUrl): RedirectResponse
    {
        $client = HttpClient::create();
        $response = $client->request('GET', $btcpayServerUrl+'/api-keys/authorize', [
            'json'=>[
                'permissions' => ["btcpay.store.cancreateinvoice", "btcpay.store.canviewinvoice"],
                'applicationName' => 'BTCPay Shopware plugin',
                'strict'    => true,
                'selectiveStores' => true,
                'redirect' => $shopwareSettingsPageUrl
            ]
            ]);
        
        if(200 !== $response->getStatusCode()){
            return new JsonResponse(["success"=>false, "message"=>"Something went wrong. Double check server url."]);
        }
        return new JsonResponse(["success"=>true, "body"=>$response->getBody()]);

        
    }
    /**
     * @Route("/api/btcpay/coincharge-btcpay/verify-api-key", name="api.action.coincharge-btcpay.verify-api-key", methods={"GET"}) 
     * */ 

    protected function testConnection(): JsonResponse{

    }
}