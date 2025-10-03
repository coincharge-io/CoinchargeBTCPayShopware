/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

const { Component, Mixin, ApiService } = Shopware;
import template from "./coincharge-btcpay-buttons.html.twig";
import "./coincharge-btcpay-buttons.scss";

const CONFIG_PREFIX = "CoinchargeBTCPayShopware.config.";
const CONFIG_KEYS = {
	serverUrl: `${CONFIG_PREFIX}btcpayServerUrl`,
	apiKey: `${CONFIG_PREFIX}btcpayApiKey`,
	storeId: `${CONFIG_PREFIX}btcpayServerStoreId`,
	webhookId: `${CONFIG_PREFIX}btcpayWebhookId`,
	webhookSecret: `${CONFIG_PREFIX}btcpayWebhookSecret`,
	integrationStatus: `${CONFIG_PREFIX}integrationStatus`,
	methodBTC: `${CONFIG_PREFIX}btcpayStorePaymentMethodBTC`,
	methodLightning: `${CONFIG_PREFIX}btcpayStorePaymentMethodLightning`,
	methodMonero: `${CONFIG_PREFIX}btcpayStorePaymentMethodMonero`,
	methodLitecoin: `${CONFIG_PREFIX}btcpayStorePaymentMethodLitecoin`,
};

Component.register("coincharge-btcpay-buttons", {
	template,
	inject: ["coinchargeBtcpayApiService"],
	mixins: [Mixin.getByName("notification")],
	data() {
		return {
			isLoading: false,
			systemConfigService: ApiService.getByName("systemConfigApiService"),
		};
	},
	computed: {
		hasCredentials() {
			return [CONFIG_KEYS.serverUrl, CONFIG_KEYS.apiKey, CONFIG_KEYS.storeId].every(
				(key) => this.getFieldValue(key) !== ""
			);
		},
	},
	methods: {
		async generateAPIKey() {
			const rawServerUrl = this.getFieldValue(CONFIG_KEYS.serverUrl);

			if (!rawServerUrl) {
				this.createNotificationWarning({
					title: "BTCPay Server",
					message: this.$tc(
						"coincharge-btcpay-generate-credentials.missing_server"
					),
				});

				return;
			}

			const filteredUrl = this.removeTrailingSlash(rawServerUrl);
			await this.systemConfigService.saveValues({
				[CONFIG_KEYS.serverUrl]: filteredUrl,
				[CONFIG_KEYS.apiKey]: "",
				[CONFIG_KEYS.storeId]: "",
				[CONFIG_KEYS.webhookId]: "",
				[CONFIG_KEYS.webhookSecret]: "",
				[CONFIG_KEYS.integrationStatus]: false,
				[CONFIG_KEYS.methodBTC]: false,
				[CONFIG_KEYS.methodLightning]: false,
				[CONFIG_KEYS.methodMonero]: false,
				[CONFIG_KEYS.methodLitecoin]: false,
			});

			const redirectTarget = this.buildRedirectUrl();
			const authorizeUrl = `${filteredUrl}/api-keys/authorize/?applicationName=BTCPayShopwarePlugin&permissions=btcpay.store.cancreateinvoice&permissions=btcpay.store.canviewinvoices&permissions=btcpay.store.webhooks.canmodifywebhooks&permissions=btcpay.store.canviewstoresettings&selectiveStores=true&redirect=${encodeURIComponent(redirectTarget)}`;

			window.open(authorizeUrl, "_blank", "noopener");
		},

		removeTrailingSlash(serverUrl) {
			return serverUrl.replace(/\/$/, "");
		},

		buildRedirectUrl() {
			const origin = window.location.origin;
			const basePath = window.location.pathname.replace(/\/?admin\/?$/, "/");

			return `${this.removeTrailingSlash(`${origin}${basePath}`)}/api/_action/coincharge/credentials`;
		},

		async testConnection() {
			if (!this.hasCredentials) {
				this.createNotificationWarning({
					title: "BTCPay Server",
					message: this.$tc(
						"coincharge-btcpay-test-connection.missing_credentials"
					),
				});

				return;
			}

			this.isLoading = true;

			try {
				const response = await this.coinchargeBtcpayApiService.verifyApiKey();

				if (!response?.success) {
					this.createNotificationWarning({
						title: "BTCPay Server",
						message: response?.message ?? this.$tc("coincharge-btcpay-test-connection.error"),
					});

					return;
				}

				this.createNotificationSuccess({
					title: "BTCPay Server",
					message: this.$tc("coincharge-btcpay-test-connection.success"),
				});

				window.location.reload();
			} catch (error) {
				this.createNotificationError({
					title: "BTCPay Server",
					message: this.$tc("coincharge-btcpay-test-connection.error"),
				});
			} finally {
				this.isLoading = false;
			}
		},

		getFieldValue(fieldName) {
			const element = document.getElementById(fieldName);

			if (!element) {
				return "";
			}

			return element.value.trim();
		},
	},
});
