<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Entity\Order;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class CoinchargeOrderEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CoinchargeOrderEntity::class;
    }
    
}