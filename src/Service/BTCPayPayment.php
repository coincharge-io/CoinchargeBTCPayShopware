<?php declare(strict_types=1);

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

class BTCPayPayment implements AsynchronousPaymentHandlerInterface
{
    private OrderTransactionStateHandler $transactionStateHandler;
    private ConfigurationService  $configurationService;
    
    public function __construct(OrderTransactionStateHandler $transactionStateHandler, ConfigurationService  $configurationService){
        $this->transactionStateHandler = $transactionStateHandler;
        $this->configurationService = $configurationService;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        try{
            $redirectUrl = $this->sendReturnUrlToBTCPay($transaction, $salesChannelContext);
        }catch (\Exception $e){
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
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext):void
    {
        $header = 'Btcpay-Sig';
        $signature = $request->headers->get($header);
        $expectedHeader = 'sha256=' . hash_hmac('sha256', $signature, $this->configurationService->getSetting('btcpayWebhookSecret'));
        if($signature!==$expectedHeader){
            return false;
        }
        $body = $request->getContent();

        if($body['type'] !=='InvoiceSettled'){
            return false;
        }
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token '.$this->configurationService->getSetting('btcpayApiKey')
            ]
        ]);
        $response = $client->request('GET', $this->configurationService->getSetting('btcpayServerUrl').'/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/invoices/'.$body->invoiceId);
        $transactionId = $transaction->getOrderTransaction()->getId();
        /* if($request->query->getBoolean('cancel')){
            throw new CustomerCanceledAsyncPaymenException(
                $transactionId,
                'Customer canceled the payment'
            );
        } */
        $paymentState = $request->request->getAlpha('status');

        $context = $salesChannelContext->getContext();
        $body = json_decode($response->getBody()->getContents());
         /* if($paymentState==='Settled'){
            $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(),$context);
        }else{
            $this->transactionStateHandler->reopen($transaction->getOrderTransaction()->getId(),$context);
        }  */
          if($body->status==='Settled'){
            $this->transactionStateHandler->paid($body['metadata']['orderId'],$context);
        }else{
            $this->transactionStateHandler->reopen($body['metadata']['orderId'],$context);
        }  
        /*BTCPay server doesn't send information about invoice on redirect
         *There are two options
         *We can trust BTCPay server and update state on every call from BTCPay
         *Better option would be to set a webhook and listen to the events from BTCPay server
         */
        $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(),$context);

    }
    private function sendReturnUrlToBTCPay( $transaction, $context):string
    {
        $paymentProviderUrl="";
        // Do some API Call to your payment provider
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token '.$this->configurationService->getSetting('btcpayApiKey')
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
        $response = $client->request('POST', $this->configurationService->getSetting('btcpayServerUrl').'/api/v1/stores/'.$this->configurationService->getSetting('btcpayServerStoreId').'/invoices', [
            'body' => json_encode([
                'amount' => 5,
                'currency'=>'SATS',
                'metadata' =>
                ['orderId' => $transaction->getOrderTransaction()->getId(),
            ],
            'checkout'=>[
                'redirectURL'=>$transaction->getReturnUrl(),
                'redirectAutomatically'=>true
            ]
            ])
        ]);

        /* if (200 !== $response->getStatusCode()) {
            
        } */
        $body = json_decode($response->getBody()->getContents());
        return $body->checkoutLink;
    }

}
