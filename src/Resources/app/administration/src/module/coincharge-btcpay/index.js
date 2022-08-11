const { Module } = Shopware;

import './page/coincharge-settings';
import './extension/sw-settings-index';

Module.register('coincharge-payment', {
    type: 'plugin',
    name: 'CoinchargePayment',
    title: 'coincharge.general.title',
    description: 'coincharge.general.description',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#000000',
    icon: 'default-action-settings',




    routes: {
        index: {
            component: 'coincharge-settings',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    }
});