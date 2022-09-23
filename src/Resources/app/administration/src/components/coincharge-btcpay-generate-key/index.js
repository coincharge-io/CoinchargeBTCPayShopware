const { Component, Mixin, ApiService } = Shopware;
import template from './coincharge-btcpay-generate-key.html.twig';


Component.register('coincharge-btcpay-generate-key', {
    template,
    inject: [
        ['coinchargeBtcpayApiService']
    ],
    mixins: [
        Mixin.getByName('notification')
    ],
    data() {
        return {
            isLoading: false,
            serverUrl:
                { 'BTCPay.config.btcpayServerUrl': '' }
        };
    },
    methods: {
        generate() {
            const systemConfig = ApiService.getByName('systemConfigApiService')

            const btcpayServerUrl = document.getElementById("BTCPay.config.btcpayServerUrl").value
            const filteredUrl = this.removeTrailingSlash(btcpayServerUrl)
            this.serverUrl['BTCPay.config.btcpayServerUrl'] = filteredUrl

            const url = window.location.origin + '/api/_action/coincharge/credentials';
            systemConfig.saveValues(this.serverUrl)
            return window.location.replace(filteredUrl + '/api-keys/authorize/?applicationName=BTCPayShopwarePlugin&permissions=btcpay.store.cancreateinvoice&permissions=btcpay.store.canviewinvoices&permissions=btcpay.store.webhooks.canmodifywebhooks&selectiveStores=true&redirect=' + url);

        },
        removeTrailingSlash(serverUrl) {
            return serverUrl.replace(/\/$/, '')
        }
    }
});