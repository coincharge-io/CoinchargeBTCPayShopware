<?php

declare(strict_types=1);

namespace Coincharge\Shopware\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Psr\Log\LoggerInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Coincharge\Shopware\Client\BTCPayServerClientInterface;

class BitcoinPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    private BTCPayServerClientInterface $client;
    private ConfigurationService  $configurationService;
    private LoggerInterface $logger;

    public function __construct(BTCPayServerClientInterface $client, ConfigurationService $configurationService, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->logger = $logger;
        parent::__construct($client, $configurationService, $logger);
    }
    public function sendReturnUrlToBTCPay(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $context)
    {
        try {
            $accountUrl = parse_url($transaction->getReturnUrl(), PHP_URL_SCHEME) . '://' . parse_url($transaction->getReturnUrl(), PHP_URL_HOST) . '/account/order';

            $uri = '/api/v1/stores/' . $this->configurationService->getSetting('btcpayServerStoreId') . '/invoices';
            $response = $this->client->sendPostRequest(
                $uri,
                [
                    'amount' => $transaction->getOrderTransaction()->getAmount()->getTotalPrice(),
                    'currency' => $context->getCurrency()->getIsoCode(),
                    'metadata' =>
                    [
                        'orderId' => $transaction->getOrderTransaction()->getId(),
                        'orderNumber' => $transaction->getOrder()->getOrderNumber()
                    ],
                    'checkout' => [
                        'redirectURL' => $accountUrl,
                        'redirectAutomatically' => true,
                        'paymentMethods' => ['BTC']
                    ]
                ]
            );

            return $response['checkoutLink'];
        } catch (\Exception $e) {
            $this->logger->error(print_r($e, true));
            throw new \Exception;
        }
    }
    public function getName(): string
    {
        return 'Bitcoin';
    }
    public function getDescription(): string
    {
        return 'Pay with Bitcoin';
    }
}
