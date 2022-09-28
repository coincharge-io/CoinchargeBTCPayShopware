<?php

declare(strict_types=1);

namespace Coincharge\Shopware\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class AbstractClient
{
    protected ClientInterface $client;

    protected LoggerInterface $logger;

    public function __construct(ClientInterface $client, LoggerInterface $logger)
    {

        $this->client = $client;
        $this->logger = $logger;
    }
    protected function get(string $uri, array $options): array
    {
        return $this->request->get(Request::METHOD_GET, $uri, $options);
    }
    protected function post(string $uri, array $options): array
    {
        return $this->request->post(Request::METHOD_POST, $uri, $options);
    }

    private function request(string $method, string $uri, array $options = []): array
    {
        try{
            $response = $this->client->request($method, $uri, $options);
            $body = $response->getBody()->getContents();
        }catch(RequestException $requestException){
            throw $this->logger->error("Exception ".$requestException." - options ". $options);
        }
        return \json_decode($body, true) ?? [];
    }
}
