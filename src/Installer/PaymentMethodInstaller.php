<?php declare(strict_types=1);

namespace Coincharge\Shopware\Installer;

use Coincharge\Shopware\PaymentMethod\BitcoinPayment;
use Coincharge\Shopware\PaymentMethod\LightningPayment;
class PaymentMethodInstaller
{
    public const PAYMENT_METHODS = [
        BitcoinPayment::class,
        LightningPayment::class
    ];
}