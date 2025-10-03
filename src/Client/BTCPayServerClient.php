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

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;

class BTCPayServerClient extends AbstractClient implements ClientInterface
{
    protected ConfigurationService $configurationService;

    public function __construct(ConfigurationService $configurationService, LoggerInterface $logger)
    {
        $this->configurationService = $configurationService;

        $baseUri = (string) $this->configurationService->getSetting('btcpayServerUrl');
        $baseUri = \trim($baseUri);
        if ($baseUri === '') {
            $logger->critical('BTCPay Server base URL is missing from configuration.');
            throw new \InvalidArgumentException('Missing BTCPay Server base URL configuration.');
        }

        if (\filter_var($baseUri, FILTER_VALIDATE_URL) === false) {
            $logger->critical('BTCPay Server base URL configuration is not a valid URL.', [
                'base_uri' => $baseUri,
            ]);
            throw new \InvalidArgumentException('Invalid BTCPay Server base URL configuration.');
        }

        $authorizationHeader = $this->createAuthHeader();
        if ($authorizationHeader === null) {
            $logger->critical('BTCPay Server API key is missing from configuration.');
            throw new \InvalidArgumentException('Missing BTCPay Server API key configuration.');
        }

        $client = new Client(
            [
                'base_uri' => $baseUri,
                'headers' => [
                    'Authorization' => $authorizationHeader,
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
            'json'  => $data
        ];
        return $this->post($resourceUri, $options);
    }
    public function sendGetRequest(string $resourceUri, array $headers = []): array
    {
        $options = [
            'headers' => $headers
        ];
        return $this->get($resourceUri, $options);
    }
    public function createAuthHeader(): ?string
    {
        $apiKey = $this->configurationService->getSetting('btcpayApiKey');

        if (!\is_string($apiKey) || \trim($apiKey) === '') {
            return null;
        }

        return 'token ' . \trim($apiKey);
    }
}
