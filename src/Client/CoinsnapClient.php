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

class CoinsnapClient extends AbstractClient implements ClientInterface
{
  protected ConfigurationService $configurationService;

  public function __construct(ConfigurationService $configurationService, LoggerInterface $logger)
  {
    $this->configurationService = $configurationService;

    $configuredBaseUri = (string) $this->configurationService->getSetting('coinsnapBaseUrl');
    $configuredBaseUri = \trim($configuredBaseUri);
    $baseUri = $configuredBaseUri !== '' ? $configuredBaseUri : 'https://app.coinsnap.io';

    if ($configuredBaseUri === '') {
      $logger->notice('Coinsnap base URL not configured, falling back to default endpoint.', [
        'base_uri' => $baseUri,
      ]);
    }

    if (\filter_var($baseUri, FILTER_VALIDATE_URL) === false) {
      $logger->critical('Coinsnap base URL configuration is not a valid URL.', [
        'base_uri' => $baseUri,
      ]);
      throw new \InvalidArgumentException('Invalid Coinsnap base URL configuration.');
    }

    $authorizationHeader = $this->createAuthHeader();

    if ($authorizationHeader === null) {
      $logger->critical('Coinsnap API key is missing from configuration.');
      throw new \InvalidArgumentException('Missing Coinsnap API key configuration.');
    }

    $client = new Client(
      [
        'base_uri' => $baseUri,
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
    $apiKey = $this->configurationService->getSetting('coinsnapApiKey');

    if (!\is_string($apiKey) || \trim($apiKey) === '') {
      return null;
    }

    return \trim($apiKey);
  }
}
