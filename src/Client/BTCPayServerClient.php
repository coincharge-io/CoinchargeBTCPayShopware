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
    protected LoggerInterface $logger;

    public function __construct(ConfigurationService $configurationService, LoggerInterface $logger)
    {
        $this->configurationService = $configurationService;

        $authorizationHeader = $this->createAuthHeader();

        $client = new Client(
            [
                'base_uri' => $this->configurationService->getSetting('btcpayServerUrl'),
                'headers' => [
                    'Authorization' => $authorizationHeader
                ]
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
    public function createAuthHeader(): string
    {
        return 'token ' . $this->configurationService->getSetting('btcpayApiKey');
    }
}
