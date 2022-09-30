<?php

declare(strict_types=1);

namespace Coincharge\Shopware\Webhook;

use Coincharge\Shopware\Client\BTCPayServerClientInterface;
use Coincharge\Shopware\Order\OrderServiceInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Context;

class WebhookService implements WebhookServiceInterface
{
    private BTCPayServerClientInterface $client;
    private OrderTransactionStateHandler $transactionStateHandler;
    private OrderServiceInterface $orderService;
    private LoggerInterface $logger;
    public const WEBHOOK_CREATED = 'created';
    public const REQUIRED_HEADER = 'btcpay-sig';

    public function __construct(BTCPayServerClientInterface $client, OrderTransactionStateHandler $transactionStateHandler, OrderServiceInterface $orderService, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->orderService = $orderService;
        $this->logger = $logger;
    }

    public function registerWebhook(Request $request, ?string $salesChannelId): bool
    {
        if ($this->checkWebhookStatus()) {
            $this->logger->info('Webhook exists');
            return true;
        }
        /* $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]); */
        //TODO Test
        $webhookUrl = $request->server->get('REQUEST_SCHEME') . '://' . $request->server->get('HTTP_HOST') . '/api/_action/coincharge/webhook-endpoint';

        /* $response = $client->request('POST', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/webhooks', [
            'body' => json_encode([
                'url' => $webhookUrl //TODO Define function for shop base url
            ])
        ]); */
        $uri ='/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/webhooks';
        $body = $this->client->sendPostRequest($uri,[
            'url' => $webhookUrl 
        ]);
        if (empty($body)) {
            $this->logger->error("Webhook couldn't be created");
            return false;
        }
        //$body = json_decode($response->getBody()->getContents());

        $this->configurationService->setSetting('btcpayWebhookSecret', $body['secret']);
        $this->configurationService->setSetting('btcpayWebhookId', $body['id']);

        return true;
    }
    public function checkWebhookStatus(): bool
    {
        /* $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]); */
        if (empty($this->configurationService->getSetting('btcpayWebhookId'))) {
            return false;
        }
        //$response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/webhooks/' . $this->configurationService->getSetting('btcpayWebhookId'));
        $uri='/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/webhooks/' . $this->configurationService->getSetting('btcpayWebhookId');
        $response = $this->client->sendGetRequest($uri);
        /* if (!$response) {
            $this->logger->error("An error occurred while trying to fetch webhook with ID:" . $this->configurationService->getSetting('btcpayWebhookId') . " isn't enabled.");
            return false;
        } */
        //$body = json_decode($response->getBody()->getContents());
        if (empty($response)) {
            $this->logger->error("Webhook with ID:" . $this->configurationService->getSetting('btcpayWebhookId') . " doesn't exist.");
            return false;
        }
        if ($response['enabled'] == false) {
            $this->logger->error("Webhook with ID:" . $this->configurationService->getSetting('btcpayWebhookId') . " isn't enabled.");
            return false;
        }
        return true;
    }

    public function executeWebhook(Request $request, Context $context)
    {

        //$header = 'btcpay-sig';
        $signature = $request->headers->get(self::REQUIRED_HEADER);
        $body = $request->request->all();

        $expectedHeader = 'sha256=' . hash_hmac('sha256', file_get_contents("php://input"), $this->configurationService->getSetting('btcpayWebhookSecret'));
        //TODO file_get_contents("php://input") use it for body or change it to be uniform
        if ($signature !== $expectedHeader) {
            $this->logger->error('Invalid signature');
            return new Response();
        }

        $body = $request->request->all();
        /* $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);
        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices/' . $body['invoiceId']);

        $responseBody = json_decode($response->getBody()->getContents());
         */
        $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices/' . $body['invoiceId'];
        $responseBody = $this->client->sendGetRequest($uri);
         //$criteria = (new Criteria([$responseBody->metadata->orderNumber]));
        //$order = $this->orderRepository->search($criteria, $context)->get($responseBody['metadata']['orderId']);
        /* $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $responseBody->metadata->orderNumber));
        $orderId = $this->orderRepository->searchIds($criteria, $context)->firstId();
 */

        switch ($body['type']) {
            case 'InvoiceReceivedPayment':
                if ($body['afterExpiration']) {
                    $this->transactionStateHandler->payPartially($responseBody['metadata']['orderId'], $context);
                    $this->logger->info('Invoice (partial) payment incoming (unconfirmed) after invoice was already expired.');

                    /* $this->orderRepository->upsert([
                        [
                            'id' => $orderId,
                            'customFields' => [
                                'btcpayOrderStatus' => 'partiallyPaid',
                                'paidAfterExpiration' => true,
                                'paymentMethod' => $body['paymentMethod']
                            ],
                        ],
                    ], $context); */
                    $this->orderService->update($responseBody['metadata']['orderId'],[
                        'btcpayOrderStatus' => 'partiallyPaid',
                        'paidAfterExpiration' => true,
                        'paymentMethod' => $body['paymentMethod']
                    ], $context);

                } else {
                    $this->orderService->update($responseBody['metadata']['orderId'],[
                        'btcpayOrderStatus' => 'waitingForSettlement',
                    ], $context);
        
                    $this->logger->info('Invoice (partial) payment incoming (unconfirmed). Waiting for settlement.');
                }

                break;
            case 'InvoicePaymentSettled':
                // We can't use $body->afterExpiration here as there is a bug affecting all version prior to
                // BTCPay Server v1.7.0.0, see https://github.com/btcpayserver/btcpayserver/issues/
                // Therefore we check if the invoice is in expired or expired paid partial status, instead.
                if (
                    $responseBody['status'] == 'expired' ||
                    ($responseBody['status'] == 'expired' && $responseBody['additionalStatus'] == 'PaidPartial')
                ) {
                    // Check if also the invoice is now fully paid.
                    if ($this->orderService->invoiceIsFullyPaid($body['invoiceId'])) {
                        $this->orderService->update($responseBody['metadata']['orderId'],[
                            'btcpayOrderStatus' => 'settled',
                            'paidAfterExpiration' => true,
                            'paymentMethod' => $body['paymentMethod']
                        ], $context);
                        $this->logger->info('Invoice fully settled after invoice was already expired. Needs manual checking.');
                    } else {
                        /* $this->orderRepository->upsert([
                            [
                                'id' => $orderId,
                                'customFields' => [
                                    'btcpayOrderStatus' => 'notFullyPaid'
                                ],
                            ],
                        ], $context); */
                        $this->orderService->update($responseBody['metadata']['orderId'],[
                            'btcpayOrderStatus' => 'notFullyPaid',
                        ], $context);
                        $this->logger->debug('Invoice with orderId:'.$responseBody['metadata']['orderId'].' NOT fully paid.');
                        $this->logger->info('(Partial) payment settled but invoice not settled yet (could be more transactions incoming). Needs manual checking.');
                    }
                } else {
                    // No need to change order status here, only leave a note.
                    $this->logger->info('Invoice (partial) payment settled.');
                }

                break;
            case 'InvoiceProcessing': // The invoice is paid in full.
                $this->transactionStateHandler->process($responseBody['metadata']['orderId'], $context);
                if ($body['overPaid']) {
                    $this->orderService->update($responseBody['metadata']['orderId'],[
                        'btcpayOrderStatus' => 'waitingSettlement',
                        'overpaid' => true
                    ], $context);
                    $this->logger->info('Invoice payment received fully with overpayment, waiting for settlement.');
                } else {
                    $this->orderService->update($responseBody['metadata']['orderId'],[
                        'btcpayOrderStatus' => 'waitingSettlement',
                        'overpaid'  => false
                    ], $context);
                    $this->logger->info('Invoice payment received fully, waiting for settlement.');
                }
                break;
            case 'InvoiceInvalid':
                $this->transactionStateHandler->cancel($responseBody['metadata']['orderId'], $context);
                if ($body['manuallyMarked']) {
                    $this->orderService->update($responseBody['metadata']['orderId'],[
                        'btcpayOrderStatus' => 'manuallyMarked',
                    ], $context);
                    $this->logger->info('Invoice manually marked invalid.');
                } else {
                    $this->orderService->update($responseBody['metadata']['orderId'],[
                        'btcpayOrderStatus' => 'invalid',
                    ], $context);
                    $this->logger->info('Invoice became invalid.');
                }
                break;
            case 'InvoiceExpired':
                if ($body['partiallyPaid']) {

                    
                    $this->orderService->update($responseBody['metadata']['orderId'],[
                        'btcpayOrderStatus' => 'invoiceExpiredPaidPartially',
                    ], $context);
                    $this->transactionStateHandler->payPartially($responseBody['metadata']['orderId'], $context);
                    $this->logger->info('Invoice expired but was paid partially, please check.');
                } else {
                    
                    $this->orderService->update($responseBody['metadata']['orderId'],[
                        'btcpayOrderStatus' => 'invoiceExpired',
                    ], $context);
                    $this->transactionStateHandler->fail($responseBody['metadata']['orderId'], $context);
                    $this->logger->info('Invoice expired.');
                }
                break;
            case 'InvoiceSettled':
                if ($body['overPaid']) {
                    $this->orderService->update($responseBody['metadata']['orderId'],[
                        'btcpayOrderStatus' => 'paid',
                        'overpaid' => true
                    ], $context);
                    $this->transactionStateHandler->paid($responseBody['metadata']['orderId'], $context);
                    $this->logger->info('Invoice payment settled but was overpaid.');
                } else {
                    $this->orderService->update($responseBody['metadata']['orderId'],[
                        'btcpayOrderStatus' => 'paid',
                    ], $context);
                    $this->logger->info('Invoice payment settled.');
                    $this->transactionStateHandler->paid($responseBody['metadata']['orderId'], $context);
                }
                break;
        }
        return new Response();
    }
}
