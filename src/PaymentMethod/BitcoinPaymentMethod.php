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

final class BitcoinPaymentMethod extends AbstractPaymentMethod
{
    public function __construct()
    {
        parent::__construct(
            'Bitcoin',
            'coincharge_btcpay_shopware_btcpay_bitcoin',
            -2,
            BitcoinPaymentMethodHandler::class,
            [
                'de-DE' => [
                    'description' => 'Zahle mit Bitcoin',
                    'name' => 'Bitcoin',
                ],
                'en-GB' => [
                    'description' => 'Pay with Bitcoin',
                    'name' => 'Bitcoin',
                ],
            ]
        );
    }
}
