<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Webhook;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

// One line per webhook outcome with a fixed reason, so a log search finds every attempt.
class WebhookLog
{
    private const SECURITY_LINES_PER_MINUTE = 10;

    private LoggerInterface $logger;
    private CacheItemPoolInterface $cache;

    public function __construct(LoggerInterface $logger, CacheItemPoolInterface $cache)
    {
        $this->logger = $logger;
        $this->cache = $cache;
    }

    // Someone may be faking a payment.
    public function security(string $provider, string $reason, string $invoiceId = ''): void
    {
        if (!$this->securityLineAllowed()) {
            return;
        }

        $this->logger->warning($this->line('SECURITY', $provider, $reason, $invoiceId));
    }

    public function info(string $provider, string $reason, string $invoiceId = ''): void
    {
        $this->logger->info($this->line('INFO', $provider, $reason, $invoiceId));
    }

    private function line(string $level, string $provider, string $reason, string $invoiceId): string
    {
        // The invoice ID comes from the request.
        $id = substr((string) preg_replace('/[^A-Za-z0-9_-]/', '', $invoiceId), 0, 64);

        return sprintf('%s %s webhook reason=%s invoice=%s', $level, $provider, $reason, $id !== '' ? $id : '-');
    }

    // Caps security lines so a flood of fake requests can't fill the disk.
    private function securityLineAllowed(): bool
    {
        $item = $this->cache->getItem('coincharge_webhook_seclog_' . gmdate('YmdHi'));
        $count = (int) $item->get() + 1;
        $item->set($count);
        $item->expiresAfter(120);
        $this->cache->save($item);

        if ($count === self::SECURITY_LINES_PER_MINUTE + 1) {
            $this->logger->warning('SECURITY webhook: more rejections this minute, not logged');
        }

        return $count <= self::SECURITY_LINES_PER_MINUTE;
    }
}
