Module.register('coincharge-btcpay', {
    type: 'plugin',
    name: 'CoinchargeBTCPay',
    title: 'coincharge-btcpay.general.mainMenuItemGeneral',
    description: 'coincharge-btcpay.general.descriptionTextModule',
    version: '1.1.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'regular-cog',

    routes: {
        index: {
            component: 'coincharge-btcpay',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
            },
        },
    },

    settingsItem: {
        group: 'plugins',
        to: 'coincharge-btcpay.index',
        iconComponent: 'coincharge-btcpay-settings-icon',
        backgroundEnabled: true,
    },
});
