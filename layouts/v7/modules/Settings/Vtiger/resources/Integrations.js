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
		self.scrollToHashTarget(root);
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
