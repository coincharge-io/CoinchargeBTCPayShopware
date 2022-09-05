<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Entity;


use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;


class CoinchargeTransactionEntityDefinition
{
    public const ENTITY_NAME = 'coincharge_transactions';

    public function getEntityName():string
    {
        return self::ENTITY_NAME;
    }
    protected function defineFields():FieldCollection
    {
        return new FieldCollection([]);
    }

}