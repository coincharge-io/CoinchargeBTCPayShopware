<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Util\Log;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class LoggerFactory
{
    public function __construct(
        private readonly string $logPath,
        private readonly int $rotationCount,
    ) {
    }

    public function createLogger(): Logger
    {
        $logger = new Logger('btcpay_logger');
        $handler = new RotatingFileHandler($this->logPath, $this->rotationCount);
        $logger->pushHandler($handler);

        return $logger;
    }
}
