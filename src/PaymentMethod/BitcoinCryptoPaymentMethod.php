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

final class BitcoinCryptoPaymentMethod extends AbstractPaymentMethod
{
    public function __construct()
    {
        parent::__construct(
            'Bitcoin+Crypto',
            'coincharge_btcpay_shopware_btcpay_bitcoin_crypto',
            -3,
            BitcoinCryptoPaymentMethodHandler::class,
            [
                'de-DE' => [
                    'description' => 'Zahle mit Bitcoin/Crypto',
                    'name' => 'Bitcoin-Crypto',
                ],
                'en-GB' => [
                    'description' => 'Pay with Bitcoin/Crypto',
                    'name' => 'Bitcoin-Crypto',
                ],
            ]
        );
    }
}
