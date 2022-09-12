const { Component, Mixin, ApiService } = Shopware;
import template from './coincharge-btcpay-create-webhook.html.twig';


Component.register('coincharge-btcpay-create-webhook', {
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
            webhookValues:
            {
                'CoinchargePayment.config.btcpayWebhookSecret': '',
                'CoinchargePayment.config.btcpayWebhookId': ''
            }
        };
    },
    methods: {
        createWebhook() {
            this.isLoading = true;
            const systemConfig = ApiService.getByName('systemConfigApiService')

            this.coinchargeBtcpayApiService.generateWebhook().then((ApiResponse) => {

                if (ApiResponse.success === false) {
                    this.createNotificationWarning({
                        title: 'BTCPay Server',
                        message: ApiResponse.message
                    })
                    this.isLoading = false;
                    return;
                }
                this.webhookValues['CoinchargePayment.config.btcpayWebhookSecret'] = ApiResponse.message.secret
                this.webhookValues['CoinchargePayment.config.btcpayWebhookId'] = ApiResponse.message.id
                systemConfig.saveValues(this.webhookValues)

                this.createNotificationSuccess({
                    title: 'BTCPay Server',
                    message: this.$tc('coincharge-btcpay-test-connection.success')
                });
                this.isLoading = false;
            });
        },
    }
});