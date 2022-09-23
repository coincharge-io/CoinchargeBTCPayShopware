<?php

declare(strict_types=1);

namespace Coincharge\Shopware\Service;

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
use Coincharge\Shopware\Service\ConfigurationService;

class BTCPayServerPayment implements AsynchronousPaymentHandlerInterface
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
    
    //Webhook handles this part
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
    }

    private function sendReturnUrlToBTCPay(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $context): string
    {

        try {

            $client = new Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'token ' . $this->configurationService->getSetting('btcpayApiKey')
                ]
            ]);
            $accountUrl = parse_url($transaction->getReturnUrl(), PHP_URL_SCHEME) . '://' . parse_url($transaction->getReturnUrl(), PHP_URL_HOST) . '/account/order';
            $response = $client->request('POST', $this->configurationService->getSetting('btcpayServerUrl') . '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices', [
                 'body' => json_encode([
                    'amount' => $transaction->getOrderTransaction()->getAmount()->getTotalPrice(),
                    'currency' => $context->getCurrency()->getIsoCode(),
                    'metadata' =>
                    [
                        'orderId' => $transaction->getOrderTransaction()->getId(),
                        'orderNumber' => $transaction->getOrder()->getOrderNumber()
                    ],
                    'checkout' => [
                        'redirectURL' => $accountUrl,
                        'redirectAutomatically' => true
                    ]
                ]) 
            ]);  

            $body = json_decode($response->getBody()->getContents());
            
            return $body->checkoutLink;
        } catch (\Exception $e) {
            $this->logger->error(print_r($e,true));
            throw new \Exception;
        }
    }
}
