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
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Coincharge\ShopwareBTCPay\Service\ConfigurationService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * @RouteScope(scopes={"api"})
 */

class AdminController extends AbstractController
{
    private ConfigurationService  $configurationService;
    private OrderTransactionStateHandler $transactionStateHandler;
    protected $logger;
    private EntityRepository $orderRepository;


    public function __construct(ConfigurationService  $configurationService, OrderTransactionStateHandler $transactionStateHandler, LoggerInterface $logger, EntityRepository $orderRepository)
    {
        $this->configurationService = $configurationService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
    }
    /**
     * @Route("/api/_action/coincharge/webhook", name="api.action.coincharge.webhook", methods={"POST"})
     */
    public function generateWebhook(Request $request)
    {
        if($this->isWebhookEnabled()){
            return new JsonResponse(['success' => true, 'message' => 'Webhook already created.']);
        }
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);
        //TODO Test
        $webhookUrl = $request->server->get('REQUEST_SCHEME') . '://' . $request->server->get('HTTP_HOST') . '/api/_action/btcpay/webhook-endpoint';

        $response = $client->request('POST', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/webhooks', [
            'body' => json_encode([
                'url' => $webhookUrl //TODO Define function for shop base url
            ])
        ]);
        $body = json_decode($response->getBody()->getContents());

        if (200 !== $response->getStatusCode()) {
            return new JsonResponse(['success' => false, 'message' => $body]);
        }
        $this->configurationService->setSetting('btcpayWebhookSecret', $body->secret);
        $this->configurationService->setSetting('btcpayWebhookId', $body->id);

        return new JsonResponse(['success' => true, 'message' => $body]);
    }

    /**
     * @Route("/api/_action/coincharge/verify", name="api.action.coincharge.verify.webhook", methods={"GET"})
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
            return new JsonResponse(['success' => false, 'message' => 'Check server url and API key.']);
        }
        if(!$this->isWebhookEnabled()){
            return new JsonResponse(['success' => true, 'message' => 'You need to create a webhook.']);
        }
        return new JsonResponse(['success' => true]);
    }
    /**
     * @Route("/api/_action/coincharge/webhook-endpoint", name="api.action.coincharge.webhook.endpoint", defaults={"csrf_protected"=false, "XmlHttpRequest"=true, "auth_required"=false}, methods={"POST"})
     */
    public function webhookEndpoint(Request $request, Context $context)
    {
       
        $header = 'btcpay-sig';
        $signature = $request->headers->get($header);
        $body = $request->request->all();

        $expectedHeader = 'sha256=' . hash_hmac('sha256', file_get_contents("php://input"), $this->configurationService->getSetting('btcpayWebhookSecret'));
        //TODO file_get_contents("php://input") use it for body or change it to be uniform
        if ($signature !== $expectedHeader) {
            $this->logger->error('Invalid signature');
            return new Response();
        }

        $body = $request->request->all();
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);
        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices/' . $body['invoiceId']);

        $responseBody = json_decode($response->getBody()->getContents());
        //$criteria = (new Criteria([$responseBody->metadata->orderNumber]));
        //$order = $this->orderRepository->search($criteria, $context)->get($responseBody->metadata->orderId);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $responseBody->metadata->orderNumber));
        //check custom field order status
        $orderId = $this->orderRepository->searchIds($criteria, $context)->firstId();
        
        
        switch ($body['type']) {
            case 'InvoiceReceivedPayment':
                if ($body['afterExpiration']) {
                    $this->transactionStateHandler->payPartially($responseBody->metadata->orderId, $context);
                    $this->logger->info('Invoice (partial) payment incoming (unconfirmed) after invoice was already expired.');

                    $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'partiallyPaid',
                                'paidAfterExpiration' => true,
                                'paymentMethod' => $body['paymentMethod']
                            ],
                        ],
                    ], $context);
                } else {

                    $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'waitingForSettlement'
                            ],
                        ],
                    ], $context);
                    $this->logger->info('Invoice (partial) payment incoming (unconfirmed). Waiting for settlement.');
                }

                break;
            case 'InvoicePaymentSettled':
                // We can't use $body->afterExpiration here as there is a bug affecting all version prior to
                // BTCPay Server v1.7.0.0, see https://github.com/btcpayserver/btcpayserver/issues/
                // Therefore we check if the invoice is in expired or expired paid partial status, instead.
                if (
                    $responseBody->status === 'expired' ||
                    ($responseBody->status === 'expired' && $responseBody->additionalStatus === 'PaidPartial')
                ) {
                    // Check if also the invoice is now fully paid.
                    if ($this->invoiceIsFullyPaid($body['invoiceId'])) {
                        $this->orderRepository->upsert([
                            [
                                'id' => $orderId,
                                'customFields' => [
                                    'btcpayOrderStatus' => 'settled',
                                    'paidAfterExpiration' => true,
                                    'paymentMethod' => $body['paymentMethod']
                                ],
                            ],
                        ], $context);
                        $this->logger->debug('Invoice fully paid.');
                        $this->logger->info('Invoice fully settled after invoice was already expired. Needs manual checking.');
                    } else {
                        $this->orderRepository->upsert([
                            [
                                'id' => $orderId,
                                'customFields' => [
                                    'btcpayOrderStatus' => 'notFullyPaid'
                                ],
                            ],
                        ], $context);
                        $this->logger->debug('Invoice NOT fully paid.');
                        $this->logger->info('(Partial) payment settled but invoice not settled yet (could be more transactions incoming). Needs manual checking.');
                    }
                } else {
                    // No need to change order status here, only leave a note.
                    $this->logger->info('Invoice (partial) payment settled.');
                }

                break;
            case 'InvoiceProcessing': // The invoice is paid in full.
                $this->transactionStateHandler->process($responseBody->metadata->orderId, $context);
                if ($body['overPaid']) {
                    $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'paidFullyWithOverpayment',
                                'overpaid' => true
                            ],
                        ],
                    ], $context);
                    $this->logger->info('Invoice payment received fully with overpayment, waiting for settlement.');
                } else {

                    $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'paidFully',
                                'overpaid' => false
                            ],
                        ],
                    ], $context);
                    $this->logger->info('Invoice payment received fully, waiting for settlement.');
                }
                break;
            case 'InvoiceInvalid':
                $this->transactionStateHandler->cancel($responseBody->metadata->orderId, $context);
                if ($body['manuallyMarked']) {

                    $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'manuallyMarked'
                            ],
                        ],
                    ], $context);
                    $this->logger->info('Invoice manually marked invalid.');
                } else {
                    $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'invalid'
                            ],
                        ],
                    ], $context);
                    $this->logger->info('Invoice became invalid.');
                }
                break;
            case 'InvoiceExpired':
                if ($body['partiallyPaid']) {

                    $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'invoiceExpiredPaidPartially'
                            ],
                        ],
                    ], $context);
                    $this->transactionStateHandler->payPartially($responseBody->metadata->orderId, $context);
                    $this->logger->info('Invoice expired but was paid partially, please check.');
                } else {
                    $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'invoiceExpired'
                            ],
                        ],
                    ], $context);
                    $this->transactionStateHandler->fail($responseBody->metadata->orderId, $context);
                    $this->logger->info('Invoice expired.');
                }
                break;
            case 'InvoiceSettled':
                if ($body['overPaid']) {
                    $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'settledOverpaid'
                            ],
                        ],
                    ], $context);
                    $this->transactionStateHandler->paid($responseBody->metadata->orderId, $context);
                    $this->logger->info('Invoice payment settled but was overpaid.');
                } else {
                    $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'paid'
                            ],
                        ],
                    ], $context);
                    $this->logger->info('Invoice payment settled.');
                    $this->transactionStateHandler->paid($responseBody->metadata->orderId, $context);
                }
                break;
        }
        return new Response();

        /*BTCPay server doesn't send information about invoice on redirect
         *There are two options
         *We can trust BTCPay server and update state on every call from BTCPay
         *Better option would be to set a webhook and listen to the events from BTCPay server
         */
        //$this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(),$context);
    }


    
    private function isWebhookEnabled()
    {
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);
        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/webhooks/' . $this->configurationService->getSetting('btcpayWebhookId'));
        $body = json_decode($response->getBody()->getContents());

        if (200 !== $response->getStatusCode()||($body->enabled==false)) {
            return false;
        }
        return true;
    }
    private function invoiceIsFullyPaid($invoiceId)
    {
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);
        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices/' . $invoiceId);

        $responseBody = json_decode($response->getBody()->getContents());
        if ($responseBody->status !== 'Settled') {
            return false;
        }
        return true;
    }

    /**
     * @Route("/api/_action/coincharge/credentials", name="api.action.coincharge.webhook.endpoint", defaults={"csrf_protected"=false, "XmlHttpRequest"=true, "auth_required"=false}, methods={"POST"})
     */
    public function updateCredentials(Request $request){
        $body = $request->request->all();
        $this->logger->error($body);
        if($body['apiKey']){
            $this->configurationService->setSetting('btcpayApiKey', $body['apiKey']);
        }
        return new RedirectResponse('http://localhost/admin#/sw/extension/config/ShopwareBTCPay');

    }
}
