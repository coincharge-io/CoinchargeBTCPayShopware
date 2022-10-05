<?php

declare(strict_types=1);

namespace Coincharge\Shopware\PaymentMethod;

use Coincharge\Shopware\PaymentHandler\BitcoinPaymentMethodHandler;

class BitcoinPaymentMethod
{
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => 'Zahle mit Bitcoin',
                'name' => 'Bitcoin',
            ],
            'en-GB' => [
                'description' => 'Pay with Bitcoin',
                'name' => 'Bitcoin',
            ],
        ];
    }


    public function getPaymentHandler(): string
    {
        return BitcoinPaymentMethodHandler::class;
    }
}
