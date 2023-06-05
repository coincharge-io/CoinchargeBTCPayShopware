<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Coincharge\Shopware\Client\BTCPayServerClientInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;

abstract class AbstractPaymentMethodHandler implements AsynchronousPaymentHandlerInterface
{
    private ClientInterface $client;
    private ConfigurationService  $configurationService;
    private OrderTransactionStateHandler $transactionStateHandler;
    private LoggerInterface $logger;
    public string $baseSuccessUrl;

    public function __construct(ClientInterface $client, ConfigurationService $configurationService, OrderTransactionStateHandler $transactionStateHandler, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->logger = $logger;
        $appUrl = $_SERVER['APP_URL'];
        $url =  "$appUrl/checkout/finish?orderId=";
        $this->baseSuccessUrl = $url;
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
    abstract public function sendReturnUrlToBTCPay(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $context);
}
