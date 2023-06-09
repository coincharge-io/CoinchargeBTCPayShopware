<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Configuration;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Coincharge\Shopware\Client\ClientInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Coincharge\Shopware\Webhook\WebhookServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Coincharge\Shopware\PaymentMethod\{LightningPaymentMethod, BitcoinPaymentMethod};

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */

class ConfigurationController extends AbstractController
{
    // private ClientInterface $client;
    // private ConfigurationService $configurationService;
    // private WebhookServiceInterface $webhookService;
    // private $paymentRepository;
    //
    // public function __construct(ClientInterface $client, ConfigurationService $configurationService, WebhookServiceInterface $webhookService, $paymentRepository)
    // {
    //     $this->client = $client;
    //     $this->configurationService = $configurationService;
    //     $this->webhookService = $webhookService;
    //     $this->paymentRepository = $paymentRepository;
    // }

    public function verifyApiKey(Request $request, Context $context)
    {
    }
}
