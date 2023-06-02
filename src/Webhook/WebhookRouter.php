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

class WebhookRouter
{
    private $webhooks;

    public function __construct()
    {
        $this->webhooks = [
          'btcpay_server' => new BTCPayWebhookService(),
          'coinsnap' => new CoinsnapWebhookService()
        ];
    }
    public function route(Request $request)
    {
        $provider = $this->getProviderFromRequest($request);
        if (isset($this->webhooks[$provider])) {
            $webhook = $this->webhooks[$provider];
            return $this->process($webhook);
        }
        throw new \RuntimeException('No webhook defined for the payment provider.');
    }
    public function getProviderFromRequest(Request $request): string
    {
        if ($request->headers->has('X-Coinsnap-Sig')) {
            return 'coinsnap';
        } elseif ($request->headers->has('btcpay-sig')) {
            return 'btcpay_server';
        }
    }
}
