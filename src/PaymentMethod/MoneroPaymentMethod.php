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

use Coincharge\Shopware\PaymentHandler\MoneroPaymentMethodHandler;

class MoneroPaymentMethod
{
  public function getName(): string
  {
    return 'Monero';
  }

  public function getPosition(): int
  {
    return -1;
  }

  public function getTranslations(): array
  {
    return [
      'de-DE' => [
        'description' => 'Zahle mit Monero',
        'name' => 'Monero',
      ],
      'en-GB' => [
        'description' => 'Pay with Monero',
        'name' => 'Monero',
      ],
      '2fbb5fe2e29a4d70aa5854ce7ce3e20b' => [
        'description' => 'Pay with Monero',
        'name' => 'Monero',
      ], //Fallback language
    ];
  }

  public function getPaymentHandler(): string
  {
    return MoneroPaymentMethodHandler::class;
  }
}
