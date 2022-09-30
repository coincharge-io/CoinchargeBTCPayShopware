<?php declare(strict_types=1);

namespace Coincharge\Shopware\Order;

use Shopware\Core\Framework\Context;

interface OrderServiceInterface
{
    public function update(int $orderNumber, array $fields = ['btcpayOrderStatus' => 'New'], Context $context): void;
}