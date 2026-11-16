<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Webhook;

use Doctrine\DBAL\Connection;

// The primary key on invoice_id lets only one webhook per invoice mark a transaction paid.
class ProcessedInvoices
{
    public const TABLE = 'coincharge_processed_invoice';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    // Runs $markPaid once per invoice; returns false when the invoice was already handled.
    public function markPaidOnce(string $invoiceId, string $transactionId, callable $markPaid): bool
    {
        $this->connection->beginTransaction();
        try {
            $inserted = $this->connection->executeStatement(
                'INSERT IGNORE INTO `' . self::TABLE . '` (`invoice_id`, `transaction_id`, `created_at`) VALUES (:invoiceId, :transactionId, NOW(3))',
                ['invoiceId' => $invoiceId, 'transactionId' => $transactionId]
            );
            if ($inserted === 0) {
                $this->connection->rollBack();

                return false;
            }

            $markPaid();
            $this->connection->commit();

            return true;
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
