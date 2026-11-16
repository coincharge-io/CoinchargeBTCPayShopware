<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Configuration;

// Accepts a BTCPay API key only when every permission is limited to one store.
class BTCPayKeyPolicy
{
    public const REQUIRED_PERMISSIONS = [
        'btcpay.store.cancreateinvoice',
        'btcpay.store.canviewinvoices',
        'btcpay.store.webhooks.canmodifywebhooks',
        'btcpay.store.canviewstoresettings',
    ];

    // Returns the store ID the key is limited to, or null when the key must be refused.
    public static function storeIdFor(array $permissions): ?string
    {
        $storeId = null;
        $granted = [];

        foreach ($permissions as $permission) {
            [$policy, $scope] = array_pad(explode(':', (string) $permission, 2), 2, '');

            // Unrestricted, server or user rights, or a store permission valid for every store.
            if (!str_starts_with($policy, 'btcpay.store.') || $scope === '') {
                return null;
            }
            if ($storeId !== null && $scope !== $storeId) {
                return null;
            }

            $storeId = $scope;
            $granted[] = $policy;
        }

        if ($storeId === null || array_diff(self::REQUIRED_PERMISSIONS, $granted) !== []) {
            return null;
        }

        return $storeId;
    }
}
