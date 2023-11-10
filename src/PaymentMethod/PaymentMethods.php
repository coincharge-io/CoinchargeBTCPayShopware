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
use Coincharge\Shopware\PaymentMethod\BitcoinLightningPaymentMethod;
use Coincharge\Shopware\PaymentMethod\CoinsnapBitcoinPaymentMethod;
use Coincharge\Shopware\PaymentMethod\CoinsnapLightningPaymentMethod;
use Coincharge\Shopware\PaymentMethod\CoinsnapBitcoinLightningPaymentMethod;
use Coincharge\Shopware\PaymentMethod\MoneroPaymentMethod;
use Coincharge\Shopware\PaymentMethod\LitecoinPaymentMethod;

class PaymentMethods
{
    public const PAYMENT_METHODS = [
        CoinsnapBitcoinPaymentMethod::class,
        CoinsnapLightningPaymentMethod::class,
        CoinsnapBitcoinLightningPaymentMethod::class,
        BitcoinPaymentMethod::class,
        LightningPaymentMethod::class,
        BitcoinLightningPaymentMethod::class,
        MoneroPaymentMethod::class,
        LitecoinPaymentMethod::class,
    ];
}
