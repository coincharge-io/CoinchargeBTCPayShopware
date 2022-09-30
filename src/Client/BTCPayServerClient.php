<?php

declare(strict_types=1);

namespace Coincharge\Shopware\Client;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Coincharge\Shopware\Service\ConfigurationService;

class BTCPayServerClient extends AbstractClient
{
    private ConfigurationService $configurationService;
    public function __construct(LoggerInterface $logger, ConfigurationService $configurationService)
    {
        $authorizationHeader = $this->createAuthHeader();

        $client = new Client([
            'base_uri' => $this->configurationService->getSetting('btcpayServerUrl'),
            'headers' => [
                'Authorization' => $authorizationHeader
            ]
        ]);
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
    public function sendGetRequest(string $resourceUri,  array $headers = []): array
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
