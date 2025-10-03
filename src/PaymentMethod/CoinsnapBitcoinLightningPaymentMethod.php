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

use Coincharge\Shopware\PaymentHandler\CoinsnapBitcoinLightningPaymentMethodHandler;

final class CoinsnapBitcoinLightningPaymentMethod extends AbstractPaymentMethod
{
    public function __construct()
    {
        parent::__construct(
            'Coinsnap-Bitcoin+Lightning',
            'coincharge_btcpay_shopware_coinsnap_bitcoin_lightning',
            -2,
            CoinsnapBitcoinLightningPaymentMethodHandler::class,
            [
                'de-DE' => [
                    'description' => 'Zahle mit Bitcoin/Lightning - Coinsnap',
                    'name' => 'Coinsnap-Bitcoin-Lightning',
                ],
                'en-GB' => [
                    'description' => 'Pay with Bitcoin/Lightning - Coinsnap',
                    'name' => 'Coinsnap-Bitcoin-Lightning',
                ],
            ]
        );
    }
}
