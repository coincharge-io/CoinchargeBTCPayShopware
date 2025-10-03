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
use GuzzleHttp\Exception\RequestException;
use JsonException;
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
    try {
      $response = $this->client->request($method, $uri, $options);
      $body = (string) $response->getBody();

      $this->logger->debug(
        '{method} {uri} with following response: {response}',
        [
          'method' => \mb_strtoupper($method),
          'uri' => $uri,
          'response' => $body,
        ]
      );

      if ($body === '') {
        $this->logger->warning('Empty response body received from payment provider', [
          'method' => $method,
          'uri' => $uri,
          'request_options' => $options,
        ]);

        return [];
      }

      try {
        return \json_decode($body, true, 512, JSON_THROW_ON_ERROR);
      } catch (JsonException $decodeException) {
        $this->logger->error('Failed to decode JSON response from payment provider', [
          'method' => $method,
          'uri' => $uri,
          'request_options' => $options,
          'response' => $body,
          'error' => $decodeException->getMessage(),
        ]);

        throw new \RuntimeException('Malformed response received from payment provider.', 0, $decodeException);
      }

    } catch (RequestException $e) {
      if ($e->hasResponse()) {
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();
        $responseBody = $response->getBody()->getContents();

        // Log more detailed error information
        $this->logger->error('Guzzle request failed: {status} {reason}', [
          'status' => $statusCode,
          'reason' => $reasonPhrase,
          'uri' => $uri,
          'method' => $method,
          'request_options' => $options,
          'response_body' => $responseBody,
        ]);

        // For 422 errors, try to extract validation errors
        if ($statusCode === 422) {
          $errorData = \json_decode($responseBody, true);
          $errorMessage = 'Validation failed: ';

          if (is_array($errorData)) {
            $errorMessage .= json_encode($errorData);
          } else {
            $errorMessage .= $responseBody;
          }

          throw new \Exception($errorMessage, $statusCode);
        }

        $message = $reasonPhrase ?: sprintf('HTTP %s error', $statusCode);
        throw new \RuntimeException($message, $statusCode, $e);
      }

      $this->logger->error('Guzzle request failed: Unknown error', [
        'uri' => $uri,
        'method' => $method,
        'request_options' => $options,
        'error' => $e->getMessage(),
      ]);

      throw new \RuntimeException('Unknown error: ' . $e->getMessage(), 0, $e);
    }
  }
}
