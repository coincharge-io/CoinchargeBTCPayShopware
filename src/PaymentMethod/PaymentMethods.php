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

use Coincharge\Shopware\PaymentMethod\BitcoinPaymentMethod;
use Coincharge\Shopware\PaymentMethod\LightningPaymentMethod;

class PaymentMethods
{
    public const PAYMENT_METHODS = [
        BitcoinPaymentMethod::class,
        LightningPaymentMethod::class
    ];
}
