<?php

declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Controllers;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Framework\Context;
use Symfony\Component\Routing\Annotation\Route;
use Coincharge\ShopwareBTCPay\Service\ConfigurationService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Psr\Log\LoggerInterface;


/**
 * @RouteScope(scopes={"store-api"})
 */
class StorefrontController extends AbstractController
{

}
