import './components/coincharge-btcpay-generate-key';
import './components/coincharge-btcpay-test-connection';
import './components/coincharge-btcpay-create-webhook';
import CoinchargeBtcpayApiService from './service/CoinchargeBtcpayAPI.service';
import localeDE from './snippets/de_DE.json';
import localeEN from './snippets/en_GB.json';

const { Application } = Shopware;



Application.addServiceProvider('coinchargeBtcpayApiService', (container) => {
    const initContainer = Application.getContainer('init');
    return new CoinchargeBtcpayApiService(initContainer.httpClient, container.loginService);
});

Shopware.Locale.extend('de-DE', localeDE);
Shopware.Locale.extend('en-GB', localeEN);