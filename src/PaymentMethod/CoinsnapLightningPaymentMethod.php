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

use Coincharge\Shopware\PaymentHandler\CoinsnapLightningPaymentMethodHandler;

final class CoinsnapLightningPaymentMethod extends AbstractPaymentMethod
{
    public function __construct()
    {
        parent::__construct(
            'Coinsnap-Lightning',
            'coincharge_btcpay_shopware_coinsnap_lightning',
            -1,
            CoinsnapLightningPaymentMethodHandler::class,
            [
                'de-DE' => [
                    'description' => 'Zahle mit Lightning - Coinsnap',
                    'name' => 'Coinsnap-Lightning',
                ],
                'en-GB' => [
                    'description' => 'Pay with Lightning - Coinsnap',
                    'name' => 'Coinsnap-Lightning',
                ],
            ]
        );
    }
}
