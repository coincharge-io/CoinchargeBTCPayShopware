<?php declare(strict_types=1);

namespace Coincharge\Shopware\PaymentMethod;
use Coincharge\Shopware\PaymentMethod\BitcoinPaymentMethod;
use Coincharge\Shopware\PaymentMethod\LightningPaymentMethod;

class PaymentMethods
{
    public const PAYMENT_METHODS = [
        BitcoinPaymentMethod::class,
        LightningPaymentMethod::class
    ];
}