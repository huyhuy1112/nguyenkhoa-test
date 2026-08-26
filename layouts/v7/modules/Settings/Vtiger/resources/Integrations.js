/* Settings → Tích hợp hệ thống */
Vtiger.Class("Settings_Vtiger_Integrations_Js", {}, {
	init: function () {
		this.addComponents();
	},

	addComponents: function () {
		this.addModuleSpecificComponent("Index", app.module(), app.getParentModuleName());
	},

	registerEvents: function () {
		var root = jQuery("#NkSystemIntegrations");
		if (!root.length) {
			return;
		}
		var self = this;
		root.on("submit", ".nk-integ-form", function (e) {
			e.preventDefault();
			self.saveForm(jQuery(this));
		});
		root.on("click", ".nk-integ-test", function (e) {
			e.preventDefault();
			var btn = jQuery(this);
			if (btn.hasClass("is-busy")) {
				return;
			}
			var code = btn.data("code");
			var form = root.find('.nk-integ-form[data-code="' + code + '"]');
			self.testConnection(form, btn);
		});
		root.on("click", ".nk-integ-zalo-oauth", function (e) {
			e.preventDefault();
			var btn = jQuery(this);
			if (btn.hasClass("is-busy")) {
				return;
			}
			var form = root.find('.nk-integ-form[data-code="zalo_oa"]');
			self.startZaloOAuth(form, btn);
		});
		self.scrollToHashTarget(root);
		self.showZaloOAuthFlash(root);
	},

	showZaloOAuthFlash: function (root) {
		try {
			var params = new URLSearchParams(window.location.search || "");
			var ok = params.get("zalo_oauth");
			if (ok === null || ok === "") {
				return;
			}
			var form = root.find('.nk-integ-form[data-code="zalo_oa"]');
			if (!form.length) {
				return;
			}
			if (ok === "1") {
				var msgOk = "Đã kết nối Zalo OA — token đã lưu. Bấm Test để xác nhận.";
				this.showMessage(form, msgOk, false);
				this.notify(msgOk, false);
			} else {
				var err = params.get("zalo_err") || "Kết nối Zalo OA thất bại.";
				try {
					err = decodeURIComponent(err);
				} catch (e0) {
					/* ignore */
				}
				this.showMessage(form, err, true);
				this.notify(err, true);
			}
			if (window.history && window.history.replaceState) {
				window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/([?&])zalo_oauth=[^&]*/g, "").replace(/[?&]zalo_err=[^&]*/g, "").replace(/[?&]$/, "") + (window.location.hash || "#code=zalo_oa"));
			}
		} catch (e) {
			/* ignore */
		}
	},

	startZaloOAuth: function (form, btn) {
		var self = this;
		var payload;
		try {
			payload = this.collectPayload(form);
		} catch (err) {
			this.showMessage(form, err.message, true);
			this.notify(err.message, true);
			return;
		}
		if (!String(payload.app_id || "").trim()) {
			var m1 = "Nhập App ID trước khi kết nối Zalo OA.";
			this.showMessage(form, m1, true);
			this.notify(m1, true);
			return;
		}
		if (!String(payload.secret_key || "").trim() && !form.find("[data-role=zalo-secret-chip]").length) {
			var m2 = "Nhập Secret Key (lần đầu) trước khi kết nối OAuth.";
			this.showMessage(form, m2, true);
			this.notify(m2, true);
			return;
		}
		this.setBusy(btn, true);
		this.post("zaloOAuthStart", "zalo_oa", payload).then(function (err, data) {
			self.setBusy(btn, false);
			if (err) {
				var msg = (err && err.message) || "Không mở được OAuth Zalo.";
				self.showMessage(form, msg, true);
				self.notify(msg, true);
				return;
			}
			self.applyConnection(form, data && data.connection);
			var url = data && data.authorize_url;
			if (!url) {
				self.showMessage(form, "Thiếu authorize_url từ server.", true);
				return;
			}
			window.location.href = url;
		});
	},

	scrollToHashTarget: function (root) {
		var hash = String(window.location.hash || "").replace(/^#/, "");
		if (!hash || hash.indexOf("code=") !== 0) {
			return;
		}
		var code = decodeURIComponent(hash.slice(5));
		var card = root.find('.nk-integ-card[data-code="' + code + '"]');
		if (!card.length) {
			return;
		}
		window.setTimeout(function () {
			card[0].scrollIntoView({ behavior: "smooth", block: "start" });
			card.addClass("is-target");
			window.setTimeout(function () {
				card.removeClass("is-target");
			}, 2200);
		}, 120);
	},

	collectPayload: function (form) {
		var payload = {
			enabled: form.find('input[name="enabled"]').is(":checked") ? 1 : 0
		};
		form.find("input[type=text], input[type=password], textarea").each(function () {
			var el = jQuery(this);
			var name = el.attr("name");
			if (!name) {
				return;
			}
			var val = el.val();
			if (name === "column_map") {
				var raw = String(val || "").trim();
				if (!raw) {
					return;
				}
				try {
					payload.column_map = JSON.parse(raw);
				} catch (err) {
					throw new Error("column_map không phải JSON hợp lệ.");
				}
				return;
			}
			if (el.attr("type") === "password" || name === "service_account_json") {
				if (String(val || "").trim() === "") {
					return;
				}
			}
			payload[name] = val;
		});
		return payload;
	},

	post: function (mode, code, payload) {
		var data = {
			module: app.getModuleName(),
			parent: app.getParentModuleName(),
			action: "IntegrationsAjax",
			mode: mode,
			code: code
		};
		if (payload) {
			data.payload = JSON.stringify(payload);
		}
		return app.request.post({ data: data });
	},

	setBusy: function (btn, busy) {
		if (!btn || !btn.length) {
			return;
		}
		btn.toggleClass("is-busy", !!busy);
	},

	notify: function (message, isError) {
		if (!message) {
			return;
		}
		if (app.helper) {
			if (isError && app.helper.showErrorNotification) {
				app.helper.showErrorNotification({ message: message });
				return;
			}
			if (!isError && app.helper.showSuccessNotification) {
				app.helper.showSuccessNotification({ message: message });
				return;
			}
		}
	},

	saveForm: function (form) {
		var self = this;
		var code = form.data("code");
		var saveBtn = form.find(".nk-integ-save");
		var payload;
		try {
			payload = this.collectPayload(form);
		} catch (err) {
			this.showMessage(form, err.message, true);
			this.notify(err.message, true);
			return;
		}
		this.setBusy(saveBtn, true);
		this.post("save", code, payload).then(function (err, data) {
			self.setBusy(saveBtn, false);
			if (err) {
				var msg = (err && err.message) || "Không lưu được.";
				self.showMessage(form, msg, true);
				self.notify(msg, true);
				return;
			}
			self.applyConnection(form, data && data.connection);
			var okMsg = (data && data.message) || "Đã lưu.";
			self.showMessage(form, okMsg, false);
			self.notify(okMsg, false);
			form.find('input[type=password], textarea[name="service_account_json"]').val("");
		});
	},

	testConnection: function (form, testBtn) {
		var self = this;
		var code = form.data("code");
		this.setBusy(testBtn, true);
		this.post("test", code, null).then(function (err, data) {
			self.setBusy(testBtn, false);
			if (err) {
				var msg = (err && err.message) || "Test thất bại.";
				self.showMessage(form, msg, true);
				self.notify(msg, true);
				return;
			}
			self.applyConnection(form, data && data.connection);
			var ok = !!(data && data.success);
			var msg = (data && data.message) || (ok ? "Kết nối thành công." : "Chưa kết nối được.");
			self.showMessage(form, msg, !ok);
			self.notify(msg, !ok);
		});
	},

	applyConnection: function (form, conn) {
		if (!conn) {
			return;
		}
		var card = form.closest(".nk-integ-card");
		var status = conn.status || "not_configured";
		card.attr("data-status", status);

		var badge = card.find("[data-role=status]");
		badge.attr("class", "nk-integ-badge nk-integ-badge--" + status);
		badge.find(".nk-integ-badge__text").text(conn.status_label || status);

		var lastSync = conn.last_sync || "—";
		var lastError = conn.last_error || "—";
		card.find("[data-role=last-sync]").text(lastSync);
		card.find("[data-role=last-error]").text(lastError);

		var errStat = card.find(".nk-integ-stat--error");
		if (conn.last_error) {
			errStat.addClass("is-active");
		} else {
			errStat.removeClass("is-active");
		}

		form.find('input[name="enabled"]').prop("checked", !!conn.enabled);

		if (conn.code === "zalo_oa" && conn.extra) {
			var ex = conn.extra;
			if (ex.app_id != null) {
				form.find('input[name="app_id"]').val(ex.app_id);
			}
			if (ex.oa_id != null) {
				form.find('input[name="oa_id"]').val(ex.oa_id);
			}
			var $oaChip = card.find("[data-role=zalo-oa-chip]");
			var $oaName = card.find("[data-role=zalo-oa-name]");
			if (ex.oa_name) {
				$oaName.text(ex.oa_name);
				$oaChip.removeAttr("hidden").removeClass("nk-integ-chip--muted");
			}
			var $exp = card.find("[data-role=zalo-expiry]");
			if (ex.expires_at) {
				$exp.text("Hết hạn access token: " + ex.expires_at).removeAttr("hidden");
			}
			if (ex.secret_configured && !form.find("[data-role=zalo-secret-chip]").length) {
				form.find('input[name="secret_key"]').after(
					'<em class="nk-integ-field__hint" data-role="zalo-secret-chip">đã cấu hình</em>'
				);
			}
			if (ex.refresh_token_configured && !form.find("[data-role=zalo-refresh-chip]").length) {
				form.find('input[name="refresh_token"]').after(
					'<em class="nk-integ-field__hint" data-role="zalo-refresh-chip">đã cấu hình</em>'
				);
			}
		}
	},

	showMessage: function (form, message, isError) {
		var box = form.find("[data-role=message]");
		if (!message) {
			box.attr("hidden", "hidden").text("").removeClass("is-error");
			return;
		}
		box.removeAttr("hidden").toggleClass("is-error", !!isError).text(message);
	}
});
