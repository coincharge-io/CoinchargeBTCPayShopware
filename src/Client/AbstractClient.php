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
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;

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

    // Path without store, invoice or webhook IDs, and without the query.
    private static function pathForLog(string $uri): string
    {
        $path = (string) parse_url($uri, PHP_URL_PATH);
        return (string) preg_replace('#/(stores|invoices|webhooks|payment-methods)/[^/]+#', '/$1/{id}', $path);
    }

    // Logs method, path and status only: bodies and options can carry secrets or customer data.
    private function request(string $method, string $uri, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $this->logger->error('API request failed: {method} {uri} status {status}', [
                'method' => $method,
                'uri' => self::pathForLog($uri),
                'status' => $status,
            ]);

            throw new ApiRequestException('API request failed with status ' . $status, $status);
        } catch (GuzzleException $e) {
            $this->logger->error('API request failed: {method} {uri} could not connect', [
                'method' => $method,
                'uri' => self::pathForLog($uri),
            ]);

            throw new ApiRequestException('API server unreachable', 0);
        }

        $body = $response->getBody()->getContents();
        if ($body === '') {
            return [];
        }

        $decodedBody = \json_decode($body, true);
        if (!is_array($decodedBody)) {
            $this->logger->error('API returned invalid JSON: {method} {uri}', ['method' => $method, 'uri' => $uri]);

            throw new ApiRequestException('API returned invalid JSON', 502);
        }

        return $decodedBody;
    }
}
