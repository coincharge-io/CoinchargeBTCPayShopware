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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Framework\Context;


interface WebhookServiceInterface
{
    public function registerWebhook(Request $request, ?string $salesChannelId): bool;

    public function isWebhookEnabled(): bool;

    public function executeWebhook(Request $request, Context $context): Response;
}
