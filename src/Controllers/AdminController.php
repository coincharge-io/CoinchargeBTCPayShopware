<?php

declare(strict_types=1);

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
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteScope(scopes={"api"})
 */

class AdminController extends AbstractController
{
    private ConfigurationService  $configurationService;
    private OrderTransactionStateHandler $transactionStateHandler;
    protected $logger;

    public function __construct(ConfigurationService $configurationService, OrderTransactionStateHandler $transactionStateHandler, LoggerInterface $logger)
    {
        $this->configurationService = $configurationService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->logger = $logger;
    }
    /**
     * @Route("/api/_action/btcpay/webhook", name="api.action.btcpay.webhook", methods={"POST"})
     */
    public function generateWebhook(Request $request, Context $context)
    {
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);
        //TODO Test
        $webhookUrl = $request->server->get('REQUEST_SCHEME').'://'.$request->server->get('HTTP_HOST') . '/api/_action/btcpay/webhook-endpoint';

        $response = $client->request('POST', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/webhooks', [
            'body' => json_encode([
                'url' => $webhookUrl //TODO Define function for shop base url
            ])
        ]);
        $body = json_decode($response->getBody()->getContents());

        if (200 !== $response->getStatusCode()) {
            return new JsonResponse(['success' => false, 'data' => $body]);
        }
        $this->configurationService->setSetting('btcpayWebhookSecret', $body->secret);
        return new JsonResponse(['success' => true, 'data' => $body]);
    }

    /**
     * @Route("/api/_action/btcpay/verify", name="api.action.btcpay.verify.webhook", methods={"GET"})
     */
    public function verifyApiKey()
    {
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);

        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices');

        if (200 !== $response->getStatusCode()) {
            return new JsonResponse(['success' => false]);
        }
        return new JsonResponse(['success' => true]);
    }
    /**
     * @Route("/api/_action/btcpay/webhook-endpoint", name="api.action.btcpay.webhook.endpoint", defaults={"csrf_protected"=false, "XmlHttpRequest"=true, "auth_required"=false}, methods={"POST"})
     */
    public function webhookEndpoint(Request $request, Context $context)
    {
        //$context = Context::createDefaultContext();
        $this->logger->info('context->' . print_r($context,true));

        $this->logger->info('request ' . $request);
        $header = 'Btcpay-Sig';
        $signature = $request->headers->get($header);
        $expectedHeader = 'sha256=' . hash_hmac('sha256', $signature, $this->configurationService->getSetting('btcpayWebhookSecret'));
        if ($signature !== $expectedHeader) {
            //return false;
        }
        $this->logger->info('$signature!==$expectedHeader ' . $signature);
        $this->logger->info('$signature!==$expectedHeader ' . $expectedHeader);
        $this->logger->info($signature !== $expectedHeader);

        $body = $request->request->all();
        if ($body['type'] !== 'InvoiceSettled') {
            // return false;
        }
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);
        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices/' . $body['invoiceId']);

        $responseBody = json_decode($response->getBody()->getContents());
        //$context = $salesChannelContext->getContext();

        if ($responseBody->status === 'Settled') {
            $this->transactionStateHandler->paid($responseBody->metadata->orderId, $context);
        } else {
            $this->transactionStateHandler->reopen($responseBody->metadata->orderId, $context);
        }
        return new Response();

        /*BTCPay server doesn't send information about invoice on redirect
         *There are two options
         *We can trust BTCPay server and update state on every call from BTCPay
         *Better option would be to set a webhook and listen to the events from BTCPay server
         */
        //$this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(),$context);
    }
}
