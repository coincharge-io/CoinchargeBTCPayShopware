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

use Coincharge\Shopware\Webhook\CoinsnapWebhookService;
use Coincharge\Shopware\Webhook\BTCPayWebhookService;

class WebhookFactory
{
  private ClientInterface $coinsnapClient;
  private ClientInterface $btcpayClient;
  private ConfigurationService $configurationService;
  private OrderTransactionStateHandler $transactionStateHandler;
  private $orderService;
  private EntityRepository $orderRepository;
  private LoggerInterface $logger;

  public function __construct(ClientInterface $coinsnapClient, ClientInterface $btcpayClient, ConfigurationService $configurationService, OrderTransactionStateHandler $transactionStateHandler, $orderService, EntityRepository $orderRepository, LoggerInterface $logger)
  {
    $this->coinsnapClient = $coinsnapClient;
    $this->btcpayClient = $btcpayClient;
    $this->configurationService = $configurationService;
    $this->transactionStateHandler = $transactionStateHandler;
    $this->orderService = $orderService;
    $this->orderRepository = $orderRepository;
    $this->logger = $logger;
  }

  public function create(string $provider): WebhookServiceInterface
  {
    if ($provider === 'coinsnap') {
      return new CoinsnapWebhookService($this->coinsnapClient, $this->configurationService, $this->transactionStateHandler, $this->orderService, $this->orderRepository, $this->logger);
    } elseif ($provider === 'btcpay_server') {
      return new BTCPayWebhookService($this->btcpayClient, $this->configurationService, $this->transactionStateHandler, $this->orderService, $this->orderRepository, $this->logger);
    }
    throw new \RuntimeException('Unsupported webhook provider.');
  }
}
