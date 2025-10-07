<?php

declare(strict_types=1);

/**
 * Copyright (c) 2025 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\PaymentMethod;

use Coincharge\Shopware\PaymentHandler\UsdtPaymentMethodHandler;

class UsdtPaymentMethod
{
    public function getName(): string
    {
        return 'USDT';
    }

    public function getPosition(): int
    {
        return 6;
    }

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => 'Zahle mit USDT',
                'name' => 'USDT',
            ],
            'en-GB' => [
                'description' => 'Pay with USDT',
                'name' => 'USDT',
            ],
            '2fbb5fe2e29a4d70aa5854ce7ce3e20b' => [
                'description' => 'Pay with USDT',
                'name' => 'USDT',
            ], // Fallback language
        ];
    }

    public function getPaymentHandler(): string
    {
        return UsdtPaymentMethodHandler::class;
    }

    public function getTechnicalName(): string
    {
        return 'coincharge_btcpay_usdt_payment';
    }
}
