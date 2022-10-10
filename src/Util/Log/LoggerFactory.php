<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Util\Log;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    protected const DEFAULT_LEVEL = Logger::WARNING;
    protected const ALLOWED_LOG_LEVEL = [
        Logger::DEBUG,
        Logger::INFO,
        Logger::NOTICE,
        Logger::WARNING,
        Logger::ERROR,
        Logger::CRITICAL,
        Logger::ALERT,
        Logger::EMERGENCY
    ];
    private const LOG_FORMAT = "[%datetime%] %channel%.%level_name%: %extra.class%::%extra.function% (%extra.line%): %message% %context% %extra%\n";

    /**
     * @phpstan-var Logger::DEBUG|Logger::INFO|Logger::NOTICE|Logger::WARNING|Logger::ERROR|Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY
     */
    protected int $logLevel = self::DEFAULT_LEVEL;

    private string $rotatingFilePathPattern;

    private int $defaultFileRotationCount;

    public function __construct(string $rotatingFilePathPattern, int $defaultFileRotationCount = 14)
    {
        $this->rotatingFilePathPattern = $rotatingFilePathPattern;
        $this->defaultFileRotationCount = $defaultFileRotationCount;
    }

    public function createRotating(string $filePrefix): LoggerInterface
    {
        $filepath = \sprintf($this->rotatingFilePathPattern, $filePrefix);

        $logger = new Logger($filePrefix);
        $handler = new RotatingFileHandler($filepath, $this->defaultFileRotationCount, $this->logLevel);
        $handler->setFormatter(new LineFormatter(self::LOG_FORMAT));
        $logger->pushHandler($handler);
        $logger->pushProcessor(new PsrLogMessageProcessor(null, true));
        $logger->pushProcessor(new IntrospectionProcessor($this->logLevel));
        if ($this->logLevel < Logger::WARNING) {
            $logger->pushProcessor(
                new WebProcessor(
                    null,
                    [
                    'url' => 'REQUEST_URI',
                    'http_method' => 'REQUEST_METHOD',
                    'server' => 'SERVER_NAME',
                    ]
                )
            );
        }

        return $logger;
    }
}
