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
		this.registerFieldListeners();
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
				? this.$tc("coincharge-coinsnap-status.connected")
				: this.$tc("coincharge-coinsnap-status.disconnected");
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
				return this.$tc("coincharge-coinsnap-status.webhookRegistered");
			}
			if (this.status.webhookStatus === "error") {
				return this.$tc("coincharge-coinsnap-status.webhookError");
			}
			return this.$tc("coincharge-coinsnap-status.webhookUnknown");
		},
	},
	methods: {
		testConnection() {
			if (this.isDisabled) {
				this.helper.showMissingCredentials = true;
				return;
			}

			this.isLoading = true;
			this.coinchargeCoinsnapApiService
				.verifyApiKey()
				.then((response) => {
					this.handleVerificationResponse(response);
					return this.loadConfiguration();
				})
				.catch(() => {
					this.createNotificationError({
						title: "Coinsnap",
						message: this.$tc("coincharge-coinsnap-test-connection.error"),
					});
				})
				.finally(() => {
					this.isLoading = false;
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
						message: this.$tc("coincharge-coinsnap-status.webhookReRegisterError"),
					});
				})
				.finally(() => {
					this.isWebhookLoading = false;
				});
		},
		saveCredentials() {
			const payload = {
				"CoinchargeBTCPayShopware.config.coinsnapStoreId": this.getFieldValue("CoinchargeBTCPayShopware.config.coinsnapStoreId"),
				"CoinchargeBTCPayShopware.config.coinsnapApiKey": this.getFieldValue("CoinchargeBTCPayShopware.config.coinsnapApiKey"),
			};
			this.systemConfigService.saveValues(payload).then(() => {
				this.loadConfiguration();
			});
		},
		handleVerificationResponse(response) {
			this.updateStatusFromResponse(response);

			if (!response.success) {
				this.createNotificationWarning({
					title: "Coinsnap",
					message: response.message || this.$tc("coincharge-coinsnap-test-connection.error"),
				});
				return;
			}

			this.createNotificationSuccess({
				title: "Coinsnap",
				message: this.$tc("coincharge-coinsnap-test-connection.success"),
			});
		},
		handleWebhookResponse(response) {
			this.updateStatusFromResponse(response);
			if (response.success) {
				this.createNotificationSuccess({
					title: "Coinsnap",
					message: this.$tc("coincharge-coinsnap-status.webhookReRegisterSuccess"),
				});
			} else {
				this.createNotificationWarning({
					title: "Coinsnap",
					message: response.message || this.$tc("coincharge-coinsnap-status.webhookReRegisterError"),
				});
			}
		},
		loadConfiguration() {
			return this.systemConfigService
				.getValues("CoinchargeBTCPayShopware.config")
				.then((values) => {
					this.syncSystemConfigValues(values);
					this.credentials.storeId = values["CoinchargeBTCPayShopware.config.coinsnapStoreId"] || "";
					this.credentials.apiKey = values["CoinchargeBTCPayShopware.config.coinsnapApiKey"] || "";
					this.status.connected = Boolean(values["CoinchargeBTCPayShopware.config.coinsnapIntegrationStatus"]);
					this.status.webhookStatus = values["CoinchargeBTCPayShopware.config.coinsnapWebhookStatus"] || null;
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
			["CoinchargeBTCPayShopware.config.coinsnapStoreId", "CoinchargeBTCPayShopware.config.coinsnapApiKey"].forEach((field) => {
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
			return Boolean(this.getFieldValue("CoinchargeBTCPayShopware.config.coinsnapStoreId") && this.getFieldValue("CoinchargeBTCPayShopware.config.coinsnapApiKey"));
		},
		getFieldValue(fieldId) {
			const field = document.getElementById(fieldId);
			return field ? field.value.trim() : "";
		},
		syncSystemConfigValues(values) {
			this.$nextTick(() => {
				const state = Shopware?.State;
				const systemConfigStore = state?.get?.("swSystemConfig");
				const salesChannelId = systemConfigStore?.currentSalesChannelId ?? null;

				Object.entries(values).forEach(([key, value]) => {
					if (!key.startsWith("CoinchargeBTCPayShopware.config.")) {
						return;
					}

					const updated =
						this.commitSystemConfigValue(key, value, salesChannelId) ||
						this.updateDomFieldValue(key, value);

					if (!updated) {
						this.updateDomFieldValue(key, value); // Fallback to DOM update if mutation missing
					}
				});
			});
		},
		commitSystemConfigValue(key, value, salesChannelId) {
			const state = Shopware?.State;
			if (!state?._mutations) {
				return false;
			}

			const payload = { key, value, salesChannelId };
			const mutations = [
				"swSystemConfig/setActualConfigData",
				"swSystemConfig/setActualConfigValue",
				"swSystemConfig/setActualValue",
				"swSystemConfig/setActualConfigItem",
			];

			for (const mutation of mutations) {
				if (state._mutations[mutation]) {
					state.commit(mutation, payload);
					return true;
				}
			}

			return false;
		},
		updateDomFieldValue(key, value) {
			const element = document.getElementById(key);
			if (!element) {
				return false;
			}

			if (element.type === "checkbox") {
				element.checked = Boolean(value);
			} else {
				element.value = value ?? "";
			}

			element.dispatchEvent(new Event("input", { bubbles: true }));
			element.dispatchEvent(new Event("change", { bubbles: true }));

			return true;
		},
	},
});
