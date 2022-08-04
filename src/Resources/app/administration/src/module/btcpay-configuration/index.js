

Shopware.Module.register('btcpay-configuration', {
    type: 'plugin',
    name: 'BTCPay Configuration',
    title: 'btcpay-configuration.general.mainMenuItemGeneral',
    description: 'sw-property.general.descriptionTextModule',
    color: '#ff3d58',
    icon: 'default-shopping-paper-bag-product',


    navigation: [{
        label: 'btcpay-configuration.general.mainMenuItemGeneral',
        color: '#ff3d58',
        path: 'btcpay.configuration.list',
        icon: 'default-shopping-paper-bag-product',
        position: 100
    }]
});