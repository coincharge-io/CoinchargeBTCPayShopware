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

const CONFIG_DOMAIN = "CoinchargeBTCPayShopware.config";

Component.register("coincharge-btcpay-buttons", {
	template,
	inject: [["coinchargeBtcpayApiService"]],
	mixins: [Mixin.getByName("notification")],
	data() {
		return {
			isLoading: false,
			isWebhookLoading: false,
			credentials: {
				serverUrl: "",
				apiKey: "",
				storeId: "",
			},
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
				? this.$t("coincharge-btcpay-status.connected")
				: this.$t("coincharge-btcpay-status.disconnected");
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
				return this.$t("coincharge-btcpay-status.webhookRegistered");
			}
			if (this.status.webhookStatus === "error") {
				return this.$t("coincharge-btcpay-status.webhookError");
			}
			return this.$t("coincharge-btcpay-status.webhookUnknown");
		},
	},
	methods: {
		// All credential fields are native system-config inputs; the user edits
		// them and persists with Shopware's own Save button. We therefore read
		// the SAVED config through the system config API rather than the admin
		// form's DOM, which since Shopware 6.7 no longer exposes the config key
		// as an element id.
		generateAPIKey() {
			this.systemConfigService
				.getValues(CONFIG_DOMAIN)
				.then((values) => {
					const serverUrl = (values[`${CONFIG_DOMAIN}.btcpayServerUrl`] || "").replace(/\/$/, "");

					if (!serverUrl) {
						this.helper.showMissingCredentials = true;
						return null;
					}

					this.helper.showMissingCredentials = false;

					// Persist the normalized URL and reset any previously derived
					// credentials before sending the merchant to BTCPay to authorize.
					return this.systemConfigService
						.saveValues({
							[`${CONFIG_DOMAIN}.btcpayServerUrl`]: serverUrl,
							[`${CONFIG_DOMAIN}.btcpayApiKey`]: "",
							[`${CONFIG_DOMAIN}.btcpayServerStoreId`]: "",
							[`${CONFIG_DOMAIN}.btcpayWebhookId`]: "",
							[`${CONFIG_DOMAIN}.btcpayWebhookSecret`]: "",
							[`${CONFIG_DOMAIN}.integrationStatus`]: false,
						})
						.then(() => {
							this.credentials.serverUrl = serverUrl;
							this.credentials.apiKey = "";
							this.credentials.storeId = "";

							const clearedPath = window.location.pathname.replace("/admin", "/");
							const redirectUrl = `${window.location.origin}${clearedPath}api/_action/coincharge/credentials`;

							window.open(
								`${serverUrl}/api-keys/authorize/?applicationName=BTCPayShopwarePlugin&permissions=btcpay.store.cancreateinvoice&permissions=btcpay.store.canviewinvoices&permissions=btcpay.store.webhooks.canmodifywebhooks&permissions=btcpay.store.canviewstoresettings&selectiveStores=true&redirect=${encodeURIComponent(redirectUrl)}`,
								"_blank",
								"noopener"
							);
						});
				})
				.catch(() => {
					this.createNotificationError({
						title: "BTCPay Server",
						message: this.$t("coincharge-btcpay-test-connection.error"),
					});
				});
		},
		testConnection() {
			this.isLoading = true;
			this.systemConfigService
				.getValues(CONFIG_DOMAIN)
				.then((values) => {
					const serverUrl = values[`${CONFIG_DOMAIN}.btcpayServerUrl`];
					const apiKey = values[`${CONFIG_DOMAIN}.btcpayApiKey`];
					const storeId = values[`${CONFIG_DOMAIN}.btcpayServerStoreId`];

					this.credentials.serverUrl = serverUrl || "";
					this.credentials.apiKey = apiKey || "";
					this.credentials.storeId = storeId || "";

					if (!serverUrl || !apiKey || !storeId) {
						this.isLoading = false;
						this.helper.showMissingCredentials = true;
						return null;
					}

					this.helper.showMissingCredentials = false;

					return this.coinchargeBtcpayApiService
						.verifyApiKey()
						.then((response) => {
							this.handleVerificationResponse(response);
							return this.loadConfiguration();
						})
						.catch(() => {
							this.createNotificationError({
								title: "BTCPay Server",
								message: this.$t("coincharge-btcpay-test-connection.error"),
							});
						})
						.finally(() => {
							this.isLoading = false;
						});
				})
				.catch(() => {
					this.isLoading = false;
					this.createNotificationError({
						title: "BTCPay Server",
						message: this.$t("coincharge-btcpay-test-connection.error"),
					});
				});
		},
		reRegisterWebhook() {
			this.isWebhookLoading = true;
			this.coinchargeBtcpayApiService
				.registerWebhook()
				.then((response) => {
					this.handleWebhookResponse(response);
					return this.loadConfiguration();
				})
				.catch(() => {
					this.createNotificationError({
						title: "BTCPay Server",
						message: this.$t("coincharge-btcpay-status.webhookReRegisterError"),
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
					message: response.message || this.$t("coincharge-btcpay-test-connection.error"),
				});
				return;
			}

			this.createNotificationSuccess({
				title: "BTCPay Server",
				message: this.$t("coincharge-btcpay-test-connection.success"),
			});
		},
		handleWebhookResponse(response) {
			this.updateStatusFromResponse(response);
			if (response.success) {
				this.createNotificationSuccess({
					title: "BTCPay Server",
					message: this.$t("coincharge-btcpay-status.webhookReRegisterSuccess"),
				});
			} else {
				this.createNotificationWarning({
					title: "BTCPay Server",
					message: response.message || this.$t("coincharge-btcpay-status.webhookReRegisterError"),
				});
			}
		},
		loadConfiguration() {
			return this.systemConfigService
				.getValues(CONFIG_DOMAIN)
				.then((values) => {
					this.credentials.serverUrl = values[`${CONFIG_DOMAIN}.btcpayServerUrl`] || "";
					this.credentials.apiKey = values[`${CONFIG_DOMAIN}.btcpayApiKey`] || "";
					this.credentials.storeId = values[`${CONFIG_DOMAIN}.btcpayServerStoreId`] || "";
					this.status.connected = Boolean(values[`${CONFIG_DOMAIN}.integrationStatus`]);
					this.status.webhookStatus = values[`${CONFIG_DOMAIN}.btcpayWebhookStatus`] || null;
					this.helper.showMissingCredentials = !this.credentialsExist();
				});
		},
		updateStatusFromResponse(response) {
			if (typeof response.success === "boolean") {
				this.status.connected = response.success;
			}
			if (response.webhookStatus) {
				this.status.webhookStatus = response.webhookStatus;
			}
		},
		credentialsExist() {
			return Boolean(this.credentials.serverUrl && this.credentials.apiKey && this.credentials.storeId);
		},
	},
});
