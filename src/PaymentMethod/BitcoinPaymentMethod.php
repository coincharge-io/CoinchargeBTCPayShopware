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
