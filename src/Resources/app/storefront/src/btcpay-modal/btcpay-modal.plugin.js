import Plugin from 'src/plugin-system/plugin.class';
import StoreApiClient from 'src/service/store-api-client.service';

export default class BtcpayModal extends Plugin {
    init() {
        this._client = new StoreApiClient();
        this.button = this.el.children['btcpay-button'];

        let script = document.createElement("script");
        script.src = "/modal/btcpay.js";

        document.head.appendChild(script);
        this._registerEvents();
    }

    _registerEvents() {
        this.button.onclick = this._showInvoice.bind(this);
    }

    _showInvoice() {
        this._client.post('/store-api/btcpay/invoice', JSON.stringify({}), (response) => {
            const parsedResponse = JSON.parse(response)
            window.btcpay.showInvoice(parsedResponse.message.id)
            this._checkInvoiceStatus()
        });
    }
    _checkInvoiceStatus() {
        window.btcpay.onModalReceiveMessage((event) => {
            if (event.data.status === 'complete') {
                this._client.get('/store-api/btcpay/invoice', (response) => {
                    const parsedResponse = JSON.parse(response)
                    console.log({ isPaid: parsedResponse })

                });
            }
        })
    }
}