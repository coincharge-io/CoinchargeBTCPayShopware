<?php

declare(strict_types=1);

namespace Coincharge\Shopware\Webhook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Framework\Context;


interface WebhookServiceInterface
{
    public function registerWebhook(Request $request, ?string $salesChannelId): bool;

    public function checkWebhookStatus(): bool;

    public function executeWebhook(Request $request, Context $context): Response;
}
