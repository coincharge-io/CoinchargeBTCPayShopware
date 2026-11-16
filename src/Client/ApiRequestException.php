<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Client;

// Code is the HTTP status, or 0 when the server could not be reached.
class ApiRequestException extends \RuntimeException
{
    public function isUnavailable(): bool
    {
        return $this->getCode() === 0 || $this->getCode() >= 500;
    }

    // Coinsnap answers 400 for an unknown resource, BTCPay 404.
    public function isNotFound(): bool
    {
        return in_array($this->getCode(), [400, 404], true);
    }
}
