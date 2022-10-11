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

use Coincharge\Shopware\PaymentHandler\LightningPaymentMethodHandler;

class LightningPaymentMethod
{
    public function getName(): string
    {
        return 'Lightning';
    }

    public function getPosition(): int
    {
        return -1;
    }

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => 'Zahle mit Lightning',
                'name' => 'Lightning',
            ],
            'en-GB' => [
                'description' => 'Pay with Lightning',
                'name' => 'Lightning',
            ],
        ];
    }

    public function getPaymentHandler(): string
    {
        return LightningPaymentMethodHandler::class;
    }
}
