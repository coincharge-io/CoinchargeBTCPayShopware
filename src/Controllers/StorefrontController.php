<?php

declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Controllers;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use GuzzleHttp\Client;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\LoginRequired;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * @RouteScope(scopes={"store-api"})
 */
class StorefrontController extends AbstractController
{


    /**
     * @LoginRequired(allowGuest=true)
     * @Route("/store-api/btcpay/invoice", name="api.action.coincharge-btcpay.invoice",
     *     methods={"POST"})
     */
    public function generateInvoice(Request $request, SalesChannelContext $context, CustomerEntity $customer)
    {
        //TODO Sanitize cookie
        $invoiceId=$_COOKIE['btcpayInvoiceId'];

        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token 4c6acb3bd448081f500778067adda8cc72'
            ]
        ]);
        $response = $client->request('POST', '/api/v1/stores/38Yo2tRcswNdqGiTeqRkyxUMuszQTzXqxXEYKyyn63w2/invoices', [
            'body' => json_encode([
                'amount' => '1',
                'currency' => 'SATS',
                'metadata' =>
                ['orderId' => 4]
            ])
        ]);

        if (200 !== $response->getStatusCode() || $invoiceId) {
            return new JsonResponse(["success" => false, "message" => "Something went wrong. Double check server url."]);
        }
        $body = json_decode($response->getBody()->getContents());
        
        setcookie('btcpayInvoiceId', $body->id, time() + 900, '/');
        return new JsonResponse(["success" => true, "message" =>  $body]);
    }

    /**
     * @LoginRequired(allowGuest=true)
     * @Route("/store-api/btcpay/invoice", name="api.action.coincharge-btcpay.paid.invoice",
     *     methods={"GET"})
     */
    public function isPaidInvoice(Request $request, SalesChannelContext $context, CustomerEntity $customer)
    {
        //TODO Sanitize cookie
        $invoiceId=$_COOKIE['btcpayInvoiceId'];
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token 4c6acb3bd448081f500778067adda8cc72'
            ]
        ]);
        //https://btcpay.example.com/api/v1/api/v1/stores/{storeId}/invoices/{invoiceId}
        $response = $client->request('GET', '/api/v1/stores/38Yo2tRcswNdqGiTeqRkyxUMuszQTzXqxXEYKyyn63w2/invoices/'.$invoiceId,);

        if (200 !== $response->getStatusCode()) {
            return new JsonResponse(["success" => false, "message" => "Something went wrong. Double check server url."]);
        }
        return new JsonResponse(["success" => true, "message" =>  json_decode($response->getBody()->getContents())]);
    }
}
