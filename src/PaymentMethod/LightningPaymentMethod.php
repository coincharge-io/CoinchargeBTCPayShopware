<?php declare(strict_types=1);

namespace Coincharge\Shopware\PaymentMethod;

use Coincharge\Shopware\PaymentHandler\LightningPaymentMethodHandler;

class LightningPaymentMethod
{
    public function getName(): string
    {
        return 'Lightning';
    }
    
    public function getDescription(): string
    {
        return 'Pay with Lightning';
    }

    
    public function getPaymentHandler(): string
    {
        return LightningPaymentMethodHandler::class;
    }

}