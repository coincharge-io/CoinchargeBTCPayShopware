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

interface BTCPayServerClientInterface
{
    public function sendPostRequest(string $resourceUri, array $data, array $headers = []): array;

    public function sendGetRequest(string $resourceUri, array $headers = []): array;

    public function createAuthHeader(): string;
}
