<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Entity\Payment;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class CoinchargePaymentEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CoinchargePaymentEntity::class;
    }
    
}