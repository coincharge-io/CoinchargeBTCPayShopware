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

use Coincharge\Shopware\PaymentHandler\CoinsnapBitcoinPaymentMethodHandler;

final class CoinsnapBitcoinPaymentMethod extends AbstractPaymentMethod
{
    public function __construct()
    {
        parent::__construct(
            'Coinsnap-Bitcoin',
            'coincharge_btcpay_shopware_coinsnap_bitcoin',
            -2,
            CoinsnapBitcoinPaymentMethodHandler::class,
            [
                'de-DE' => [
                    'description' => 'Zahle mit Bitcoin - Coinsnap',
                    'name' => 'Coinsnap-Bitcoin',
                ],
                'en-GB' => [
                    'description' => 'Pay with Bitcoin - Coinsnap',
                    'name' => 'Coinsnap-Bitcoin',
                ],
            ]
        );
    }
}
