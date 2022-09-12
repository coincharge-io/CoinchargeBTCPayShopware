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
            apiKeyValue:
                { 'CoinchargePayment.config.btcpayServerUrl': '' }
        };
    },
    methods: {
        generate() {
            const systemConfig = ApiService.getByName('systemConfigApiService')

            const btcpayServerUrl = document.getElementById("CoinchargePayment.config.btcpayServerUrl").value
            const filteredUrl = this.removeTrailingSlash(btcpayServerUrl)
            this.apiKeyValue['CoinchargePayment.config.btcpayServerUrl'] = filteredUrl

            const url = window.location.origin + '/api/_action/coincharge/credentials';
            systemConfig.saveValues(this.apiKeyValue)
            console.log(systemConfig.saveValues(this.apiKeyValue))
            return window.open(filteredUrl + '/api-keys/authorize/?applicationName=CoinchargePaymentPlugin&permissions=btcpay.store.cancreateinvoice&permissions=btcpay.store.canviewinvoices&permissions=btcpay.store.webhooks.canmodifywebhooks&selectiveStores=true&redirect=' + url, '_blank');

        },
        removeTrailingSlash(serverUrl) {
            return serverUrl.replace(/\/$/, '')
        }
    }
});