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

class CoinsnapBitcoinLightningPaymentMethod
{
    public function getName(): string
    {
        return 'Coinsnap-Bitcoin+Lightning';
    }

    public function getPosition(): int
    {
        return -2;
    }

    public function getTranslations(): array
    {
        return [
          'de-DE' => [
            'description' => 'Zahle mit Bitcoin/Lightning - Coinsnap',
            'name' => 'Coinsnap-Bitcoin-Lightning',
          ],
          'en-GB' => [
            'description' => 'Pay with Bitcoin/Lightning - Coinsnap',
            'name' => 'Coinsnap-Bitcoin-Lightning',
          ],
          '2fbb5fe2e29a4d70aa5854ce7ce3e20b' => [
            'description' => 'Pay with Bitcoin/Lightning - Coinsnap',
            'name' => 'Coinsnap-Bitcoin-Lightning',
          ], //Fallback language
        ];
    }

    public function getPaymentHandler(): string
    {
        return CoinsnapBitcoinLightningPaymentMethodHandler::class;
    }
}
