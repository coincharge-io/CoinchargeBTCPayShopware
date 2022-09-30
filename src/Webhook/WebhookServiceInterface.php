<?php declare(strict_types=1);

namespace Coincharge\Shopware\Webhook;
use Symfony\Component\HttpFoundation\Request;

interface WebhookServiceInterface
{
    public function registerWebhook(Request $request,?string $salesChannelId) : bool;

    public function checkWebhookStatus() : bool;

}