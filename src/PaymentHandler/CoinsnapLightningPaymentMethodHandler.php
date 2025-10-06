use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Psr\Log\LoggerInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Coincharge\Shopware\Client\ClientInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;

class CoinsnapLightningPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    private ClientInterface $client;
    private ConfigurationService  $configurationService;
    private OrderTransactionStateHandler $transactionStateHandler;
    private LoggerInterface $logger;

    public function __construct(ClientInterface $client, ConfigurationService $configurationService, OrderTransactionStateHandler $transactionStateHandler, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->logger = $logger;
        parent::__construct($client, $configurationService, $transactionStateHandler, $logger);
    }
    public function sendReturnUrlToCheckout(PaymentTransactionStruct $transaction, SalesChannelContext $context)
    {
        try {
            $accountUrl = $this->baseSuccessUrl . $transaction->getOrderTransaction()->getOrderId();
            if ($transaction->getOrderTransaction()->getAmount()->getTotalPrice() == 0) {
                $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context->getContext());
                return $accountUrl;
            }
            $uri = '/api/v1/stores/' . $this->configurationService->getSetting('coinsnapStoreId') . '/invoices';
            $response = $this->client->sendPostRequest(
                $uri,
                [
                    'amount' => $transaction->getOrderTransaction()->getAmount()->getTotalPrice(),
                    'currency' => $context->getCurrency()->getIsoCode(),
                    'referralCode' => 'DEV17612c35cd8c54d3fad381615',
                    'metadata' =>
                    [
                        'orderNumber' => $transaction->getOrder()->getOrderNumber(),
                        'transactionId' => $transaction->getOrderTransaction()->getId()
                    ],
                    'orderId' => $transaction->getOrderTransaction()->getOrderId(),
                    'redirectUrl' => $accountUrl,
                    'enabledPaymentMethods' => ['Lightning']
                ]
            );

            return $response['checkoutLink'];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}
