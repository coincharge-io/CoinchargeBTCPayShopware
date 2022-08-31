<?php

declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigurationService
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function getSetting(string $setting, ?string $salesChannelId = null): ?string
    {
        return $this->systemConfigService->get("ShopwareBTCPay.config." . $setting, $salesChannelId);
    }
    public function getShopName(string $salesChannelId): string
    {
        return $this->systemConfigService->get("core.basicInformation.shopName");
    }
}
