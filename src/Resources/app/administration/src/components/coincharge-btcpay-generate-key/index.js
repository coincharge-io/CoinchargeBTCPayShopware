const { Component, Mixin } = Shopware;
import template from './coincharge-btcpay-generate-key.html.twig';


Component.register('coincharge-btcpay-generate-key', {
    template,
    inject: [
        'coinchargeBtcpayApiService'
    ],
    mixins: [
        Mixin.getByName('notification')
    ],
    data() {
        return {
            isLoading: false,
        };
    },
    methods: {
        generate() {
            const btcpayServerUrl = document.getElementById("ShopwareBTCPay.config.btcpayServerUrl").value
            const filteredUrl = this.removeTrailingSlash(btcpayServerUrl)
            this.isLoading = true;
            return window.open(filteredUrl + '/api-keys/authorize/?applicationName=BTCPay%20Shopware%20plugin&permissions=btcpay.store.cancreateinvoice&permissions=btcpay.store.canviewinvoices&permissions=btcpay.store.webhooks.canmodifywebhooks&selectiveStores=true', '_blank');

        },
        removeTrailingSlash(serverUrl) {
            return serverUrl.replace(/\/$/, '')
        }
    }
});