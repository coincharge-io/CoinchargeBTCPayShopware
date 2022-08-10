<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Helpers;

class Config
{
    /** @var SettingsService $settingsService */
    private $settingsService

    /**
     * @param SettingsService $settingsService
     */
    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function btcpayServerUrl(?string $salesChannelId=null):string
    {
        return $this->settingsService->getSetting("btcpayServerUrl",$salesChannelId);
    }

    public function btcpayApiKey(?string $salesChannelId=null):string
    {
        return $this->settingsService->getSetting("btcpayApiKey",$salesChannelId);
    }

}
