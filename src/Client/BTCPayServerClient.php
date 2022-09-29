<?php declare(strict_types=1);

namespace Coincharge\Shopware\Client;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class BTCPayServerClient extends AbstractClient
{
    public function __construct(LoggerInterface $logger)
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
    public function sendPostRequest(string $resourceUri, array $data, array $headers = [])
    {
        $headers['content-type'] = 'application/json';
        $options = [
            'headers' => $headers,
            'json'  => $data
        ];
        $this->post($resourceUri, $options);
    }
    public function sendGetRequest(string $resourceUri,  array $headers = [])
    {
        $options = [
            'headers' => $headers
        ];
        $this->get($resourceUri, $options);
    }
    private function createAuthHeader(): string
    {
        return 'token ' . $this->configurationService->getSetting('btcpayApiKey');
    }
}