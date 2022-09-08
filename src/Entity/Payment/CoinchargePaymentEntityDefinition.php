<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Entity\Payment;


use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Coincharge\ShopwareBTCPay\Entity\Order\CoinchargeOrderEntityDefinition;

class CoinchargePaymentEntityDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'coincharge_payment';

    public function getEntityName():string
    {
        return self::ENTITY_NAME;
    }
    protected function defineFields():FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('order_id', 'order_id', CoinchargeOrderEntityDefinition::class))->addFlags(new Required()), 
            (new StringField('receivedDate', 'receivedDate')),
            (new StringField('value', 'value')),
            (new StringField('fee', 'fee')),
            (new StringField('status', 'status')),
            (new StringField('destination', 'destination')),
            new ManyToOneAssociationField('order', 'order_id', CoinchargeOrderEntityDefinition::class, 'order_id'),
        ]);
    }
    public function getCollectionClass(): string
    {
        return CoinchargePaymentEntityCollection::class;
    }
    public function getEntityClass(): string
	{
		return CoinchargePaymentEntity::class;
	}

}