<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Client;

use Coincharge\Shopware\Configuration\ConfigurationService;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;

class CoinsnapClient extends AbstractClient implements ClientInterface
{
    protected ConfigurationService $configurationService;

    protected LoggerInterface $logger;

    public function __construct(ConfigurationService $configurationService, LoggerInterface $logger)
    {
        $this->configurationService = $configurationService;

        $authorizationHeader = $this->createAuthHeader();

        $client = new Client(
            [
                'base_uri' => 'https://app.coinsnap.io',
                'headers' => [
                    'X-Api-Key' => $authorizationHeader,
                ],
            ]
        );
        parent::__construct($client, $logger);
    }

    public function sendPostRequest(string $resourceUri, array $data, array $headers = []): array
    {
        $headers['content-type'] = 'application/json';
        $options = [
            'headers' => $headers,
            'json' => $data,
        ];

        return $this->post($resourceUri, $options);
    }

    public function sendGetRequest(string $resourceUri, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
        ];

        return $this->get($resourceUri, $options);
    }

    public function createAuthHeader(): ?string
    {
        return $this->configurationService->getSetting('coinsnapApiKey');
    }

    // Creates an invoice on Coinsnap and returns it as the API answered.
    public function createInvoice(
        float $amount,
        string $currency,
        string $orderId,
        string $orderNumber,
        string $transactionId,
        string $redirectUrl,
        Context $context,
        ?array $enabledPaymentMethods = null
    ): array {
        $uri = '/api/v1/stores/'.$this->configurationService->getSetting('coinsnapStoreId').'/invoices';

        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'referralCode' => 'DEV17612c35cd8c54d3fad381615',
            'metadata' => [
                'orderNumber' => $orderNumber,
                'transactionId' => $transactionId,
            ],
            'orderId' => $orderId,
            'redirectUrl' => $redirectUrl,
        ];

        // Only include if specified
        if (! empty($enabledPaymentMethods)) {
            $payload['enabledPaymentMethods'] = $enabledPaymentMethods;
        }

        // Failures are logged by the client without the payload.
        return $this->sendPostRequest($uri, $payload);
    }
}
