/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

const { Component, Mixin, ApiService } = Shopware;
import template from './coincharge-btcpay-buttons.html.twig';
import './coincharge-btcpay-buttons.scss';

Component.register('coincharge-btcpay-buttons', {
    template: template,
    inject: [
        ['coinchargeBtcpayApiService']
    ],
    mixins: [
        Mixin.getByName('notification')
    ],
    data() {
        return {
            isLoading: false,
            config: {
                'CoinchargeBTCPayShopware.config.btcpayServerUrl': ''
            },
        };
    },
    methods: {
        generateAPIKey() {
            const systemConfig = ApiService.getByName('systemConfigApiService')

            const btcpayServerUrl = document.getElementById("CoinchargeBTCPayShopware.config.btcpayServerUrl").value
            if (!btcpayServerUrl) {
                return this.createNotificationWarning({
                    title: 'BTCPay Server',
                    message: this.$tc('coincharge-btcpay-generate-credentials.missing_server')
                })
            }
            const filteredUrl = this.removeTrailingSlash(btcpayServerUrl)
            this.config['CoinchargeBTCPayShopware.config.btcpayServerUrl'] = filteredUrl
            const url = window.location.origin + '/api/_action/coincharge/credentials';
            systemConfig.saveValues({
                'CoinchargeBTCPayShopware.config.btcpayServerUrl': this.config['CoinchargeBTCPayShopware.config.btcpayServerUrl'],
                'CoinchargeBTCPayShopware.config.btcpayApiKey': '',
                'CoinchargeBTCPayShopware.config.btcpayServerStoreId': '',
                'CoinchargeBTCPayShopware.config.btcpayWebhookId': '',
                'CoinchargeBTCPayShopware.config.btcpayWebhookSecret': '',
                'CoinchargeBTCPayShopware.config.integrationStatus': false,
                'CoinchargeBTCPayShopware.config.btcpayStorePaymentMethodBTC': false,
                'CoinchargeBTCPayShopware.config.btcpayStorePaymentMethodLightning': false
            })
            document.cookie = "coincharge_btcpay_shopware_redirect=" + window.location.href + "; path=" + window.location.hostname + 'SameSite=Strict';
            return window.location.replace(filteredUrl + '/api-keys/authorize/?applicationName=BTCPayShopwarePlugin&permissions=btcpay.store.cancreateinvoice&permissions=btcpay.store.canviewinvoices&permissions=btcpay.store.webhooks.canmodifywebhooks&permissions=btcpay.store.canviewstoresettings&selectiveStores=true&redirect=' + url);

        },
        removeTrailingSlash(serverUrl) {
            return serverUrl.replace(/\/$/, '')
        },
        testConnection() {
            this.isLoading = true;
            this.coinchargeBtcpayApiService.verifyApiKey().then((ApiResponse) => {
                if (ApiResponse.success === false) {
                    this.createNotificationWarning({
                        title: 'BTCPay Server',
                        message: ApiResponse.message
                    })
                    this.isLoading = false;
                    return;
                }
                this.createNotificationSuccess({
                    title: 'BTCPay Server',
                    message: this.$tc('coincharge-btcpay-test-connection.success')
                });

                this.isLoading = false;
                window.location.reload();
            }).catch(e => {
                return this.createNotificationWarning({
                    title: 'BTCPay Server',
                    message: this.$tc('coincharge-btcpay-test-connection.error')
                })
            });
        },
    }
});