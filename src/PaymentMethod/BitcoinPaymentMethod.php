<?php declare(strict_types=1);

namespace Coincharge\Shopware\PaymentMethod;

use Coincharge\Shopware\PaymentHandler\BitcoinPaymentMethodHandler;

class BitcoinPaymentMethod
{
    public function getName(): string
    {
        return 'Bitcoin';
    }

    
    public function getDescription(): string
    {
        return 'Pay with Bitcoin';
    }

    
    public function getPaymentHandler(): string
    {
        return BitcoinPaymentMethodHandler::class;
    }

}