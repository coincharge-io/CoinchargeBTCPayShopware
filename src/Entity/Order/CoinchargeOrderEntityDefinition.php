<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Entity\Order;


use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Coincharge\ShopwareBTCPay\Entity\Payment\CoinchargePaymentEntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;

class CoinchargeOrderEntityDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'coincharge_orders';

    public function getEntityName():string
    {
        return self::ENTITY_NAME;
    }
    protected function defineFields():FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
			(new FkField('order_id', 'order_id', OrderDefinition::class))->addFlags(new Required()),
            (new StringField('orderNumber', 'orderNumber')),
            (new StringField('invoiceId', 'invoiceId')),
            (new StringField('paymentMethod', 'paymentMethod')),
            (new StringField('cryptoCode', 'cryptoCode')),
            (new StringField('destination', 'destination')),
            (new StringField('paymentLink', 'paymentLink')),
            (new StringField('rate', 'rate')),
            (new StringField('paymentMethodPaid', 'paymentMethodPaid')),
            (new StringField('totalPaid', 'totalPaid')),
            (new StringField('due', 'due')),
            (new StringField('amount', 'amount')),
            (new StringField('networkFee', 'networkFee')),
            (new StringField('providedComment', 'providedComment'))
            (new StringField('consumedLightningAddress', 'consumedLightningAddress'))
            (new OneToManyAssociationField('payments', CoinchargePaymentEntityDefinition::class, 'order_id'))
            (new OneToOneAssociationField('order', 'order_id', 'id', OrderDefinition::class, true)),

           ]);
    }
    public function getCollectionClass(): string
    {
        return CoinchargeOrderEntityCollection::class;
    }

}