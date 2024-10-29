<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\PaymentMethod;

use Coincharge\Shopware\PaymentHandler\BitcoinCryptoPaymentMethodHandler;

class BitcoinCryptoPaymentMethod
{
    public function getName(): string
    {
        return 'Bitcoin+Crypto';
    }

    public function getPosition(): int
    {
        return -3;
    }

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => 'Zahle mit Bitcoin/Crypto',
                'name' => 'Bitcoin-Crypto',
            ],
            'en-GB' => [
                'description' => 'Pay with Bitcoin/Crypto',
                'name' => 'Bitcoin-Crypto',
            ],
            '2fbb5fe2e29a4d70aa5854ce7ce3e20b' => [
                'description' => 'Pay with Bitcoin/Crypto',
                'name' => 'Bitcoin-Crypto',
            ], //Fallback language
        ];
    }


    public function getPaymentHandler(): string
    {
        return BitcoinCryptoPaymentMethodHandler::class;
    }
}
