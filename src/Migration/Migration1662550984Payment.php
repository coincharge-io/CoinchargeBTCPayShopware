<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1662550984Payment extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1662550984;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
        CREATE TABLE IF NOT EXISTS `coincharge_payment` (
            `id`   INT NOT NULL,
            `order_id`   VARCHAR(255)  NOT NULL,
            `receivedDate`   DATETIME(3),
            `value`   DECIMAL(16,8)  NOT NULL,
            `fee`   DECIMAL(16,8)  NOT NULL,
            `status`   VARCHAR(255) NOT NULL,
            `destination`   VARCHAR(255),
            `created_at` DATETIME(3),
            `updated_at` DATETIME(3),
            PRIMARY KEY (id),
            CONSTRAINT `fk.coincharge_payment.order_id` FOREIGN KEY (`order_id`) REFERENCES `coincharge_payment` (`order_id`) ON DELETE SET NULL ON UPDATE CASCADE

        )
            ENGINE = InnoDB
            DEFAULT CHARSET = utf8mb4
            COLLATE = utf8mb4_unicode_ci;
        SQL;
                $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
