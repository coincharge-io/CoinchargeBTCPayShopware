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

Component.register("coincharge-btcpay-buttons", {
	template,
	inject: [["coinchargeBtcpayApiService"]],
	mixins: [Mixin.getByName("notification")],
	data() {
		return {
			isLoading: false,
			isWebhookLoading: false,
			status: {
				connected: false,
				webhookStatus: null,
			},
			helper: {
				showMissingCredentials: false,
			},
			systemConfigService: ApiService.getByName("systemConfigApiService"),
		};
	},
	mounted() {
		this.loadConfiguration();
		this.registerFieldListeners();
	},
	computed: {
		isTestDisabled() {
			return this.isLoading || !this.credentialsExist();
		},
		connectionVariant() {
			return this.status.connected ? "success" : "danger";
		},
		connectionLabel() {
			return this.status.connected
				? this.$tc("coincharge-btcpay-status.connected")
				: this.$tc("coincharge-btcpay-status.disconnected");
		},
		webhookVariant() {
			if (this.status.webhookStatus === "registered") {
				return "success";
			}
			if (this.status.webhookStatus === "error") {
				return "danger";
			}
			return "neutral";
		},
		webhookLabel() {
			if (this.status.webhookStatus === "registered") {
				return this.$tc("coincharge-btcpay-status.webhookRegistered");
			}
			if (this.status.webhookStatus === "error") {
				return this.$tc("coincharge-btcpay-status.webhookError");
			}
			return this.$tc("coincharge-btcpay-status.webhookUnknown");
		},
	},
	methods: {
		generateAPIKey() {
			const serverUrl = this.getFieldValue("CoinchargeBTCPayShopware.config.btcpayServerUrl");
			if (!serverUrl) {
				this.helper.showMissingCredentials = true;
				return;
			}

			const filteredUrl = serverUrl.replace(/\/$/, "");
			this.systemConfigService.saveValues({
				"CoinchargeBTCPayShopware.config.btcpayServerUrl": filteredUrl,
				"CoinchargeBTCPayShopware.config.btcpayApiKey": "",
				"CoinchargeBTCPayShopware.config.btcpayServerStoreId": "",
				"CoinchargeBTCPayShopware.config.btcpayWebhookId": "",
				"CoinchargeBTCPayShopware.config.btcpayWebhookSecret": "",
				"CoinchargeBTCPayShopware.config.integrationStatus": false,
			});

			const clearedPath = window.location.pathname.replace("/admin", "/");
			const redirectUrl = `${window.location.origin}${clearedPath}api/_action/coincharge/credentials`;

			window.open(
				`${filteredUrl}/api-keys/authorize/?applicationName=BTCPayShopwarePlugin&permissions=btcpay.store.cancreateinvoice&permissions=btcpay.store.canviewinvoices&permissions=btcpay.store.webhooks.canmodifywebhooks&permissions=btcpay.store.canviewstoresettings&selectiveStores=true&redirect=${encodeURIComponent(redirectUrl)}`,
				"_blank",
				"noopener"
			);
		},
		testConnection() {
			if (this.isTestDisabled) {
				this.helper.showMissingCredentials = true;
				return;
			}

			this.isLoading = true;
			this.coinchargeBtcpayApiService
				.verifyApiKey()
				.then((response) => this.handleVerificationResponse(response))
				.catch(() => {
					this.createNotificationError({
						title: "BTCPay Server",
						message: this.$tc("coincharge-btcpay-test-connection.error"),
					});
				})
				.finally(() => {
					this.isLoading = false;
				});
		},
		reRegisterWebhook() {
			this.isWebhookLoading = true;
			this.coinchargeBtcpayApiService
				.registerWebhook()
				.then((response) => this.handleWebhookResponse(response))
				.catch(() => {
					this.createNotificationError({
						title: "BTCPay Server",
						message: this.$tc("coincharge-btcpay-status.webhookReRegisterError"),
					});
				})
				.finally(() => {
					this.isWebhookLoading = false;
				});
		},
		handleVerificationResponse(response) {
			this.updateStatusFromResponse(response);

			if (!response.success) {
				this.createNotificationWarning({
					title: "BTCPay Server",
					message: response.message || this.$tc("coincharge-btcpay-test-connection.error"),
				});
				return;
			}

			this.createNotificationSuccess({
				title: "BTCPay Server",
				message: this.$tc("coincharge-btcpay-test-connection.success"),
			});
		},
		handleWebhookResponse(response) {
			this.updateStatusFromResponse(response);
			if (response.success) {
				this.createNotificationSuccess({
					title: "BTCPay Server",
					message: this.$tc("coincharge-btcpay-status.webhookReRegisterSuccess"),
				});
			} else {
				this.createNotificationWarning({
					title: "BTCPay Server",
					message: response.message || this.$tc("coincharge-btcpay-status.webhookReRegisterError"),
				});
			}
		},
		loadConfiguration() {
			this.systemConfigService
				.getValues("CoinchargeBTCPayShopware.config")
				.then((values) => {
					this.status.connected = Boolean(values["CoinchargeBTCPayShopware.config.integrationStatus"]);
					this.status.webhookStatus = values["CoinchargeBTCPayShopware.config.btcpayWebhookStatus"] || null;
					this.helper.showMissingCredentials = !this.credentialsExist();
				});
		},
		updateStatusFromResponse(response) {
			if (typeof response.success === 'boolean') {
				this.status.connected = response.success;
			}
			if (response.webhookStatus) {
				this.status.webhookStatus = response.webhookStatus;
			}
		},
		registerFieldListeners() {
			["CoinchargeBTCPayShopware.config.btcpayServerUrl", "CoinchargeBTCPayShopware.config.btcpayApiKey", "CoinchargeBTCPayShopware.config.btcpayServerStoreId"].forEach((field) => {
				const element = document.getElementById(field);
				if (!element) {
					return;
				}
				element.addEventListener("input", () => {
					this.helper.showMissingCredentials = !this.credentialsExist();
				});
			});
		},
		credentialsExist() {
			return [
				"CoinchargeBTCPayShopware.config.btcpayServerUrl",
				"CoinchargeBTCPayShopware.config.btcpayApiKey",
				"CoinchargeBTCPayShopware.config.btcpayServerStoreId",
			].every((field) => this.getFieldValue(field));
		},
		getFieldValue(fieldId) {
			const field = document.getElementById(fieldId);
			return field ? field.value.trim() : "";
		},
	},
});
