<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Webhook\Factory;

use Coincharge\Shopware\Client\ClientInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Context;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Coincharge\Shopware\Webhook\WebhookServiceInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class WebhookFactory
{
  private ClientInterface $client;
  private ConfigurationService $configurationService;
  private OrderTransactionStateHandler $transactionStateHandler;
  private $orderService;
  private EntityRepository $orderRepository;
  private LoggerInterface $logger;

  public function __construct(ClientInterface $client, ConfigurationService $configurationService, OrderTransactionStateHandler $transactionStateHandler, $orderService, EntityRepository $orderRepository, LoggerInterface $logger)
  {
    $this->client = $client;
    $this->configurationService = $configurationService;
    $this->transactionStateHandler = $transactionStateHandler;
    $this->orderService = $orderService;
    $this->orderRepository = $orderRepository;
    $this->logger = $logger;
  }

  public function create(string $provider): WebhookServiceInterface
  {
    if ($provider === 'coinsnap') {
      return new CoinsnapWebhookService($client, $configurationService, $transactionStateHandler, $orderService, $orderRepository, $logger);
    } elseif ($provider === 'btcpay_server') {
      return new BTCPayWebhookService($client, $configurationService, $transactionStateHandler, $orderService, $orderRepository, $logger);
    }
    throw new \RuntimeException('Unsupported webhook provider.');
  }
}
