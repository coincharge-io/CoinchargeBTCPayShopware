<?php

declare(strict_types=1);

namespace Coincharge\Shopware\PaymentMethod;

use Coincharge\Shopware\PaymentHandler\LightningPaymentMethodHandler;

class LightningPaymentMethod
{
    

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => 'Zahle mit Lightning',
                'name' => 'Lightning',
            ],
            'en-GB' => [
                'description' => 'Pay with Lightning',
                'name' => 'Lightning',
            ],
        ];
    }
    
    public function getPaymentHandler(): string
    {
        return LightningPaymentMethodHandler::class;
    }
}
