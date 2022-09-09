<?php

declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Service;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymenException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Coincharge\ShopwareBTCPay\Service\ConfigurationService;

class BTCPayPayment implements AsynchronousPaymentHandlerInterface
{
    private ConfigurationService  $configurationService;
    private LoggerInterface $logger;

    public function __construct(ConfigurationService $configurationService, LoggerInterface $logger)
    {
        $this->configurationService = $configurationService;
        $this->logger = $logger;

    }
    
    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {

        //$redirectUrl = $request->server->get('REQUEST_SCHEME').'://'.$request->server->get('HTTP_HOST')


        try {
            $redirectUrl = $this->sendReturnUrlToBTCPay($transaction, $salesChannelContext);
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
        return new RedirectResponse($redirectUrl);
    }
    /**
     * @throws CustomerCanceledAsyncPaymenException
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
    }
    /*  public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext):void
    {
        $header = 'Btcpay-Sig';
        $signature = $request->headers->get($header);
        $expectedHeader = 'sha256=' . hash_hmac('sha256', $signature, $this->configurationService->getSetting('btcpayWebhookSecret'));
        if($signature!==$expectedHeader){
            //return false;
        }
        $body = $request->getContent();

        if($body['type'] !=='InvoiceSettled'){
           // return false;
        }
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token '.$this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);
        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl').'/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/invoices/'.$body->invoiceId);
        $transactionId = $transaction->getOrderTransaction()->getId();
        
        $paymentState = $request->request->getAlpha('status');

        $context = $salesChannelContext->getContext();
        $body = json_decode($response->getBody()->getContents());
       
          if($body->status==='Settled'){
            $this->transactionStateHandler->paid($body['metadata']['orderId'],$context);
        }else{
            $this->transactionStateHandler->reopen($body['metadata']['orderId'],$context);
        }  
       
        $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(),$context);

    } */
    private function sendReturnUrlToBTCPay($transaction, $context): string
    {
        //$orderRepository = $this->container->get('coincharge_order.repository');

        $paymentProviderUrl = "";
        // Do some API Call to your payment provider
        try {
            $client = new Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
                ]
            ]);

            /* $response = $client->request('POST', '/api/v1/stores/iTeqRkyxUMuszQTzXqxXEYKyyn63w2/invoices', [
            'body' => json_encode([
                'amount' => $transaction->getOrderTransaction()->getAmount()->getTotalPrice(),
                'currency'=>$context->getCurrency()->getIsoCode(),
                'metadata' =>
                ['orderId' => $transaction->getOrderTransaction()->getId()],
            'checkout'=>[
                'redirectURL'=>$transaction->getReturnUrl(),
                'redirectAutomatically'=>true
            ]
            ])
        ]); */
            $response = $client->request('POST', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices', [
                'body' => json_encode([
                    'amount' => 5,
                    'currency' => 'SATS',
                    'metadata' =>
                    [
                        'orderId' => $transaction->getOrderTransaction()->getId(),
                    ],
                    'checkout' => [
                        'redirectURL' => 'http://localhost/account/order',
                        'redirectAutomatically' => true
                    ]
                ])
            ]);

            /* if (200 !== $response->getStatusCode()) {
            
        } */
            $body = json_decode($response->getBody()->getContents());
           
            return $body->checkoutLink;
        } catch (\Exception $e) {
            //$this->logger->error($e);
            throw new \Exception;
        }
    }
}
