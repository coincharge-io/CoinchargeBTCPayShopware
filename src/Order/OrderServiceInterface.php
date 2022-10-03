<?php declare(strict_types=1);

namespace Coincharge\Shopware\Order;

use Shopware\Core\Framework\Context;

interface OrderServiceInterface
{
    public function update(string $orderNumber, array $fields, Context $context): void;
}