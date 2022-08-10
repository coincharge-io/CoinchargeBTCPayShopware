<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Service;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class BTCPayPayment implements AsynchronousPaymentHandlerInterface
{
    private OrderTransactionStateHandler $transactionStateHandler;

    public function __construct(OrderTransactionStateHandler $transactionStateHandler){
        $this->transactionStateHandler = $transactionStateHandler;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        try{
            $redirectUrl = $this->sendReturnUrlToBTCPay($transaction->getReturnUrl());
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
        $transactionId = $transaction->getOrderTransaction()->getId();

        if($request->query->getBoolean('cancel')){
            throw new CustomerCanceledAsyncPaymenException(
                $transactionId,
                'Customer canceled the payment'
            );
        }
        $paymentState = $request->query->getAlpha('status');

        $context = $salesChannelContext->getContext();
        if($paymentState==='completed'){
            $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(),$context);
        }else{
            $this->transactionStateHandler->reopen($transaction->getOrderTransaction()->getId(),$context);
        }
    }
    private function sendReturnUrlToBTCPay(string $getReturnUrl):string
    {
        $paymentProviderUrl="";
        // Do some API Call to your payment provider

        return $paymentProviderUrl;
    }

}
