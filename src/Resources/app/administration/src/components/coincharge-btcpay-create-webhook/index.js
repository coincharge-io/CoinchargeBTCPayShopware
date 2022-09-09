const { Component, Mixin } = Shopware;
import template from './coincharge-btcpay-create-webhook.html.twig';


Component.register('coincharge-btcpay-create-webhook', {
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
        createWebhook() {
            this.isLoading = true;

            this.coinchargeBtcpayApiService.generateWebhook().then((ApiResponse) => {

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