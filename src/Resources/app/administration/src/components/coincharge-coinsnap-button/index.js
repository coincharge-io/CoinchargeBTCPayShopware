/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

const { Component, Mixin, ApiService } = Shopware;
import template from "./coincharge-coinsnap-button.html.twig";
import "./coincharge-coinsnap-button.scss";

const CONFIG_DOMAIN = "CoinchargeBTCPayShopware.config";

Component.register("coincharge-coinsnap-button", {
	template,
	inject: [["coinchargeCoinsnapApiService"]],
	mixins: [Mixin.getByName("notification")],
	data() {
		return {
			isLoading: false,
			isWebhookLoading: false,
			credentials: {
				storeId: "",
				apiKey: "",
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
		isDisabled() {
			return this.isLoading || !this.credentialsExist();
		},
		connectionVariant() {
			return this.status.connected ? "success" : "danger";
		},
		connectionLabel() {
			return this.status.connected
				? this.$t("coincharge-coinsnap-status.connected")
				: this.$t("coincharge-coinsnap-status.disconnected");
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
				return this.$t("coincharge-coinsnap-status.webhookRegistered");
			}
			if (this.status.webhookStatus === "error") {
				return this.$t("coincharge-coinsnap-status.webhookError");
			}
			return this.$t("coincharge-coinsnap-status.webhookUnknown");
		},
	},
	methods: {
		// Credentials are native system-config inputs saved with Shopware's own
		// Save button; we read the SAVED config through the system config API
		// rather than the admin form's DOM, which since Shopware 6.7 no longer
		// exposes the config key as an element id.
		testConnection() {
			this.isLoading = true;
			this.systemConfigService
				.getValues(CONFIG_DOMAIN)
				.then((values) => {
					const storeId = values[`${CONFIG_DOMAIN}.coinsnapStoreId`];
					const apiKey = values[`${CONFIG_DOMAIN}.coinsnapApiKey`];

					this.credentials.storeId = storeId || "";
					this.credentials.apiKey = apiKey || "";

					if (!storeId || !apiKey) {
						this.isLoading = false;
						this.helper.showMissingCredentials = true;
						return null;
					}

					this.helper.showMissingCredentials = false;

					return this.coinchargeCoinsnapApiService
						.verifyApiKey()
						.then((response) => {
							this.handleVerificationResponse(response);
							return this.loadConfiguration();
						})
						.catch(() => {
							this.createNotificationError({
								title: "Coinsnap",
								message: this.$t("coincharge-coinsnap-test-connection.error"),
							});
						})
						.finally(() => {
							this.isLoading = false;
						});
				})
				.catch(() => {
					this.isLoading = false;
					this.createNotificationError({
						title: "Coinsnap",
						message: this.$t("coincharge-coinsnap-test-connection.error"),
					});
				});
		},
		reRegisterWebhook() {
			this.isWebhookLoading = true;
			this.coinchargeCoinsnapApiService
				.registerWebhook()
				.then((response) => {
					this.handleWebhookResponse(response);
					return this.loadConfiguration();
				})
				.catch(() => {
					this.createNotificationError({
						title: "Coinsnap",
						message: this.$t("coincharge-coinsnap-status.webhookReRegisterError"),
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
					title: "Coinsnap",
					message: response.message || this.$t("coincharge-coinsnap-test-connection.error"),
				});
				return;
			}

			this.createNotificationSuccess({
				title: "Coinsnap",
				message: this.$t("coincharge-coinsnap-test-connection.success"),
			});
		},
		handleWebhookResponse(response) {
			this.updateStatusFromResponse(response);
			if (response.success) {
				this.createNotificationSuccess({
					title: "Coinsnap",
					message: this.$t("coincharge-coinsnap-status.webhookReRegisterSuccess"),
				});
			} else {
				this.createNotificationWarning({
					title: "Coinsnap",
					message: response.message || this.$t("coincharge-coinsnap-status.webhookReRegisterError"),
				});
			}
		},
		loadConfiguration() {
			return this.systemConfigService
				.getValues(CONFIG_DOMAIN)
				.then((values) => {
					this.credentials.storeId = values[`${CONFIG_DOMAIN}.coinsnapStoreId`] || "";
					this.credentials.apiKey = values[`${CONFIG_DOMAIN}.coinsnapApiKey`] || "";
					this.status.connected = Boolean(values[`${CONFIG_DOMAIN}.coinsnapIntegrationStatus`]);
					this.status.webhookStatus = values[`${CONFIG_DOMAIN}.coinsnapWebhookStatus`] || null;
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
			return Boolean(this.credentials.storeId && this.credentials.apiKey);
		},
	},
});
