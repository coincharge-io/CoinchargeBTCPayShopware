<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Controllers;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

class AdminController extends AbstractController{

    protected function generateApiKey(string $btcpayServerUrl): JsonResponse{
        $client = HttpClient::create();
        $response = $client->request('GET', $btcpayServerUrl+'/api-keys/authorize', [
            'body'=>[
                'permissions' => [],
                'applicationName' => 'BTCPay Shopware plugin',
                'strict'    => true,
                'selectiveStores' => true,
                'redirect' => $redirectUrl

            ]
        ])
    }
    protected function testConnection(): JsonResponse{

    }
}