const { Component, Mixin } = Shopware;
import template from './coincharge-btcpay-test-connection.html.twig';
import './coincharge-btcpay-test-connection.scss';


Component.register('coincharge-btcpay-test-connection', {
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
        };
    },
    methods: {
        check() {
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
            });
        },
    }
});