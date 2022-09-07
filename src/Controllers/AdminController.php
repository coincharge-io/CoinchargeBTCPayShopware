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
        $this->logger->info = $logger;
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
        
        $header = 'Btcpay-Sig';
        $signature = $request->headers->get($header);
        $expectedHeader = 'sha256=' . hash_hmac('sha256', $signature, $this->configurationService->getSetting('btcpayWebhookSecret'));
        if ($signature !== $expectedHeader) {
            $this->logger->error('Invalid signature');
            return new Response();
        }

        $body = $request->request->all();
        /* if ($body['type'] !== 'InvoicePaymentSettled') {
        } */
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);
        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices/' . $body['invoiceId']);

        $responseBody = json_decode($response->getBody()->getContents());
        //$context = $salesChannelContext->getContext();

        /* if ($responseBody->status === 'Settled') {
            $this->transactionStateHandler->paid($responseBody->metadata->orderId, $context);
        } else {
            $this->transactionStateHandler->reopen($responseBody->metadata->orderId, $context);
        } */
        switch ($body['type']) {
			case 'InvoiceReceivedPayment':
				if ($body['afterExpiration']) {
					$this->transactionStateHandler->paid_partially($responseBody->metadata->orderId, $context);
					$this->logger->info(__('Invoice (partial) payment incoming (unconfirmed) after invoice was already expired.'));
				} else {
					// No need to change order status here, only leave a note.
					$this->logger->info(__('Invoice (partial) payment incoming (unconfirmed). Waiting for settlement.'));
				}

				$this->updateOrderPayments($order);

				break;
			case 'InvoicePaymentSettled':
				// We can't use $body->afterExpiration here as there is a bug affecting all version prior to
				// BTCPay Server v1.7.0.0, see https://github.com/btcpayserver/btcpayserver/issues/
				// Therefore we check if the invoice is in expired or expired paid partial status, instead.
				$orderStatus = $order->get_status();
				if ($orderStatus === 'expired' ||
					$orderStatus === 'expired_paid_partially') {
					// Check if also the invoice is now fully paid.
					if ($this>invoiceIsFullyPaid($body['invoiceId'])) {
						$this->logger->debug('Invoice fully paid.');
						$this->updateStatus($order, 'expired_paid_late');
						$order->add_order_note(__('Invoice fully settled after invoice was already expired. Needs manual checking.'));
						//$order->payment_complete();
					} else {
						$this->logger->debug('Invoice NOT fully paid.');
						$this->updateStatus($order, 'expired_paid_partially');
						$this->logger->info(__('(Partial) payment settled but invoice not settled yet (could be more transactions incoming). Needs manual checking.'));
					}
				} else {
					// No need to change order status here, only leave a note.
					$this->logger->info(__('Invoice (partial) payment settled.'));
				}

				// Store payment data (exchange rate, address).
				$this->updateOrderPayments($order);

				break;
			case 'InvoiceProcessing': // The invoice is paid in full.
				$this->updateStatus($order, 'processing');
				if ($body->overPaid) {
					$this->logger->info(__('Invoice payment received fully with overpayment, waiting for settlement.'));
				} else {
					$this->logger->info(__('Invoice payment received fully, waiting for settlement.'));
				}
				break;
			case 'InvoiceInvalid':
				$this->updateStatus($order, 'invalid');
				if ($body->manuallyMarked) {
					$this->logger->info(__('Invoice manually marked invalid.'));
				} else {
					$this->logger->info(__('Invoice became invalid.'));
				}
				break;
			case 'InvoiceExpired':
				if ($body->partiallyPaid) {
					$this->updateStatus($order, 'expired_paid_partially');
					$this->logger->info(__('Invoice expired but was paid partially, please check.'));
				} else {
					$this->updateStatus($order, 'expired');
					$this->logger->info(__('Invoice expired.'));
				}
				break;
			case 'InvoiceSettled':
				$order->payment_complete();
				if ($body->overPaid) {
					$this->logger->info(__('Invoice payment settled but was overpaid.'));
					$this->updateStatus($order, $configuredOrderStates[OrderStates::SETTLED_PAID_OVER]);
				} else {
					$this->logger->info(__('Invoice payment settled.'));
					 $this->transactionStateHandler->paid($responseBody->metadata->orderId, $context);

				}

				// Store payment data (exchange rate, address).
				$this->updateOrderPayments($order);

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
    /**
     * @Route("/api/_action/btcpay/verify-credentials", name="api.action.btcpay.webhook.endpoint", defaults={"csrf_protected"=false, "XmlHttpRequest"=true, "auth_required"=false}, methods={"POST"})
     */
    public function verifyCredentials(Request $request, Context $context)
    {
        $apiKey = $request->request->get('api-key');
        $this->configurationService->setSetting('btcpayApiKey', $apiKey);


    }


    private function updateOrderPayments(string $invoiceId){
        try { $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);

        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices'.$invoiceId.'/payment-methods');
        $invoicePaymentMethod = json_decode($response->getBody()->getContents());
        foreach ($invoicePaymentMethod as $orderData) {
            if($orderData->paymentMethodPaid==0){
                $this->logger->info("Invoice isn't paid");
                return false;
            }
            $invoiceData = [
                "paymentMethod" => $orderData->paymentMethod,
                "cryptoCode" => $orderData->cryptoCode,
                "destination" => $orderData->destination,
                "paymentLink" => $orderData->paymentLink,
                "rate" => $orderData->rate,
                "paymentMethodPaid" => $orderData->paymentMethodPaid,
                "totalPaid" => $orderData->totalPaid,
                "due" => $orderData->due,
                "amount" => $orderData->amount,
                "networkFee" => $orderData->networkFee,
                "providedComment" => $orderData->providedComment,
                "consumedLightningAddress" => $orderData->consumedLightningAddress,
            ];
            //TODO Update database records
            foreach ($orderData->payments as $index => $trx) {
                //TODO Extract order_id
                $paymentData = [
                    "order_id" => '',
                    "receivedDate" => $trx->receivedDate,
                    "value" => $trx->value,
                    "fee" => $trx->fee,
                    "status" => $trx->status,
                    "destination" => $trx->destination,
                ];
            }
        }
    }catch(\Exception $e)
    {
        $this->logger->error($e);
        $this->logger->error("Error processing payment data for invoice");
    }
    }
    private function updateStatus($order, $status)
    {
        //Update order status in database
    }
    private function invoiceIsFullyPaid(){

    }
}
