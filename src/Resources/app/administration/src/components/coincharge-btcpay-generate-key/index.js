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
        generate_key() {

            const btcpayServerUrl = document.querySelector("#ShopwareBTCPay.config.btcpayServerUrl").value

            const currentUrl = window.location.href;
            this.isLoading = true;

            this.coinchargeBtcpayApiService.generateApiKey(btcpayServerUrl, currentUrl).then((ApiResponse) => {
                if (ApiResponse.success === false) {
                    this.createNotificationWarning({
                        title: 'BTCPay Server',
                        message: this.$tc('coincharge-btcpay-generate-key.error')
                    })
                    this.isLoading = false;
                    return;
                }
                this.createNotificationSuccess({
                    title: 'BTCPay Server',
                    message: this.$tc('coincharge-btcpay-generate-key.success')
                });
                this.isLoading = false;
            });
        }
    }
});