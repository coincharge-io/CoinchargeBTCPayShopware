<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1662550970Order extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1662550970;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
        CREATE TABLE IF NOT EXISTS `coincharge_order` (
            `id`                INT             NOT NULL,
            `order_id`   VARCHAR(255)    NOT NULL,
            `orderNumber`   VARCHAR(255),
            `invoiceId`   VARCHAR(255)    NOT NULL,
            `paymentMethod`   CHAR(20),
            `cryptoCode`   CHAR(3),
            `destination`   VARCHAR(255),
            `paymentLink`   VARCHAR(255),
            `rate`   DECIMAL(16,8),
            `paymentMethodPaid`   DECIMAL(16,8),
            `totalPaid`   DECIMAL(16,8),
            `due`   DECIMAL(16,8),
            `amount`   DECIMAL(16,8),
            `networkFee`   DECIMAL(16,8),
            `providedComment`   VARCHAR(255),
            `consumedLightningAddress`   VARCHAR(255),
            `created_at` DATETIME(3),
            `updated_at` DATETIME(3),
            PRIMARY KEY (id),
            CONSTRAINT `fk.coincharge_order.order_id` FOREIGN KEY (`id`) REFERENCES `order` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
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
