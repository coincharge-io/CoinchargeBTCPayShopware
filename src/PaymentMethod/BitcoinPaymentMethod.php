<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\PaymentMethod;

use Coincharge\Shopware\PaymentHandler\BitcoinPaymentMethodHandler;

class BitcoinPaymentMethod
{
    public function getName(): string
    {
        return 'Bitcoin';
    }

    public function getPosition(): int
    {
        return -2;
    }

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
            '2fbb5fe2e29a4d70aa5854ce7ce3e20b' => [
                'description' => 'Pay with Bitcoin',
                'name' => 'Bitcoin',
            ], //Fallback language
        ];
    }


    public function getPaymentHandler(): string
    {
        return BitcoinPaymentMethodHandler::class;
    }
}
