<?php

declare(strict_types=1);

namespace Coincharge\Shopware\PaymentMethod;

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

abstract class AbstractPaymentMethod implements AsynchronousPaymentHandlerInterface
{
    private BTCPayServerClientInterface $client;
    private ConfigurationService  $configurationService;
    private LoggerInterface $logger;

    public function __construct(BTCPayServerClientInterface $client, ConfigurationService $configurationService, LoggerInterface $logger)
    {
        $this->client = $client;
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
    abstract public function sendReturnUrlToBTCPay(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $context);

    }

   
