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

use GuzzleHttp\ClientInterface;
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
        return $this->request(Request::METHOD_GET, $uri, $options);
    }
    protected function post(string $uri, array $options): array
    {
        return $this->request(Request::METHOD_POST, $uri, $options);
    }

    private function request(string $method, string $uri, array $options = []): array
    {
        try{
            $response = $this->client->request($method, $uri, $options);
            $body = $response->getBody()->getContents();
        }catch(\Exception $requestException){
             $this->logger->error($requestException->getMessage());
            throw new \Exception($requestException->getMessage());
        }
        return \json_decode($body, true) ?? [];
    }
}
