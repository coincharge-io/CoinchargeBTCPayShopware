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
                'BTCPay.config.btcpayServerUrl': '',
                'BTCPay.config.integrationStatus': false
            },
        };
    },
    methods: {
        generateAPIKey() {
            const systemConfig = ApiService.getByName('systemConfigApiService')

            const btcpayServerUrl = document.getElementById("BTCPay.config.btcpayServerUrl").value
            const filteredUrl = this.removeTrailingSlash(btcpayServerUrl)
            this.config['BTCPay.config.btcpayServerUrl'] = filteredUrl

            const url = window.location.origin + '/api/_action/coincharge/credentials';
            systemConfig.saveValues(this.config['BTCPay.config.btcpayServerUrl'])
            return window.location.replace(filteredUrl + '/api-keys/authorize/?applicationName=BTCPayShopwarePlugin&permissions=btcpay.store.cancreateinvoice&permissions=btcpay.store.canviewinvoices&permissions=btcpay.store.webhooks.canmodifywebhooks&selectiveStores=true&redirect=' + url);

        },
        removeTrailingSlash(serverUrl) {
            return serverUrl.replace(/\/$/, '')
        },
        testConnection() {
            this.isLoading = true;
            const systemConfig = ApiService.getByName('systemConfigApiService')
            this.coinchargeBtcpayApiService.verifyApiKey().then((ApiResponse) => {
                this.config['BTCPay.config.integrationStatus'] = ApiResponse.success ? true : false;
                systemConfig.saveValues(this.config['BTCPay.config.integrationStatus'])
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
            });
        },
    }
});