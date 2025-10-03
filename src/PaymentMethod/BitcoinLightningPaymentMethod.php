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

use Coincharge\Shopware\PaymentHandler\BitcoinLightningPaymentMethodHandler;

final class BitcoinLightningPaymentMethod extends AbstractPaymentMethod
{
    public function __construct()
    {
        parent::__construct(
            'Bitcoin+Lightning',
            'coincharge_btcpay_shopware_btcpay_bitcoin_lightning',
            -3,
            BitcoinLightningPaymentMethodHandler::class,
            [
                'de-DE' => [
                    'description' => 'Zahle mit Bitcoin/Lightning',
                    'name' => 'Bitcoin-Lightning',
                ],
                'en-GB' => [
                    'description' => 'Pay with Bitcoin/Lightning',
                    'name' => 'Bitcoin-Lightning',
                ],
            ]
        );
    }
}
