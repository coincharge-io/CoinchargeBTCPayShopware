import BtcpayModal from './btcpay-modal/btcpay-modal.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('BtcpayModal', BtcpayModal, '[data-btcpay-modal]');