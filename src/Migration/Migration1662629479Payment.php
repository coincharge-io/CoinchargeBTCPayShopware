<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1662629479Payment extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1662629479;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
        CREATE TABLE IF NOT EXISTS `coincharge_payment` (
            `id` BINARY(16) NOT NULL,
            `order_id`   BINARY(16) NOT NULL,
            `receivedDate`   DATETIME(3),
            `value`   DECIMAL(16,8)  NOT NULL,
            `fee`   DECIMAL(16,8)  NOT NULL,
            `status`   VARCHAR(255) NOT NULL,
            `destination`   VARCHAR(255),
            `created_at` DATETIME(3),
            `updated_at` DATETIME(3),
            PRIMARY KEY (id),
            KEY `fk.coincharge_payment.order_id` (`order_id`),
            CONSTRAINT `fk.coincharge_payment.order_id` FOREIGN KEY (`order_id`) REFERENCES `coincharge_order` (`order_id`) ON DELETE CASCADE

        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;
                $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
