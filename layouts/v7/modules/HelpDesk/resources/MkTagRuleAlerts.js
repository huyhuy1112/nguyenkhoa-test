/**
 * Tag Rule Engine — Cảnh báo hành động từ Lead thật (DB).
 */
(function ($, global) {
	'use strict';

	var store = global.MkTagRuleEngineStore;
	if (!store) return;

	function esc(s) {
		if (s == null) return '';
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function toast(msg) {
		if (typeof app !== 'undefined' && app.helper && app.helper.showSuccessNotification) {
			app.helper.showSuccessNotification({ message: msg });
		} else {
			window.alert(msg);
		}
	}

	function chip(name) {
		return '<span class="mk-tre-chip mk-tre-chip--primary">' + esc(name) + '</span>';
	}

	var MkTagRuleAlerts = {
		$root: null,
		alerts: [],

		init: function () {
			this.$root = $('#mk-tag-rule-alerts');
			if (!this.$root.length) return;
			if (this.$root.data('mk-tre-inited')) {
				this.render();
				return;
			}
			this.$root.data('mk-tre-inited', 1);
			// Ưu tiên bootstrap inject từ server; nếu trống thì gọi API mode=alerts.
			var seeded = [];
			if (global.MK_TAG_RULE_STATE && Array.isArray(global.MK_TAG_RULE_STATE.alerts)) {
				seeded = global.MK_TAG_RULE_STATE.alerts;
			}
			if (seeded && seeded.length) {
				this.alerts = seeded;
				if (store && typeof store === 'object') {
					try { store.getAlerts && store.getAlerts(); } catch (eIgnore) { /* hydrate */ }
				}
			} else {
				try {
					this.alerts = store.loadAlerts ? store.loadAlerts() : [];
				} catch (e) {
					try {
						this.alerts = store.getAlerts ? store.getAlerts() : [];
					} catch (e2) {
						this.alerts = [];
					}
					if (!this.alerts || !this.alerts.length) {
						this.loadError = (e && e.message) ? e.message : 'Không tải được cảnh báo';
					}
				}
			}
			this.render();
			this.bindEvents();
		},

		render: function () {
			var alerts = this.alerts || [];
			var tags = store.getTags();
			var tagById = {};
			tags.forEach(function (t) { tagById[t.id] = t; });

			var rules = store.getRules();
			var alertRules = rules.filter(function (r) {
				return r.is_active && r.alert_days != null;
			}).length;
			var cskhDays = (global.MK_TAG_RULE_STATE && global.MK_TAG_RULE_STATE.cskh_alert_days)
				? parseInt(global.MK_TAG_RULE_STATE.cskh_alert_days, 10) : 7;
			if (!cskhDays || cskhDays < 1) cskhDays = 7;
			var cskhCount = alerts.filter(function (a) {
				return a.alert_type === 'cskh' || (a.rule && a.rule.id === 'rule-cskh');
			}).length;
			var ruleCount = alerts.length - cskhCount;
			var late7 = alerts.filter(function (a) { return (a.days_idle - (a.rule.alert_days || 0)) >= 7; }).length;

			var cards = alerts.map(function (a) {
				var isCskh = a.alert_type === 'cskh' || (a.rule && a.rule.id === 'rule-cskh');
				var overdue = Math.max(0, (a.days_idle || 0) - ((a.rule && a.rule.alert_days) || 0));
				var severe = isCskh ? (a.days_idle || 0) >= (cskhDays + 7) : overdue >= 7;
				var tagHtml = ((a.rule && a.rule.tag_ids) || []).map(function (tid) {
					return chip(tagById[tid] ? tagById[tid].name : tid);
				}).join('');
				if (!tagHtml && a.tags && a.tags.length) {
					tagHtml = a.tags.slice(0, 6).map(function (lbl) { return chip(lbl); }).join('');
				}
				var badgeLabel = isCskh
					? 'Cần CSKH'
					: ('Trễ ' + overdue + ' ngày');
				var detailUrl = a.detail_url
					? a.detail_url
					: ('index.php?module=Leads&view=Detail&record=' + encodeURIComponent(a.lead_id) + '&app=SALES');
				var nameHtml = '<a class="mk-tre-alert-card__name-link" href="' + esc(detailUrl) + '">' + esc(a.name) + '</a>';
				return ''
					+ '<article class="mk-tre-alert-card' + (isCskh ? ' mk-tre-alert-card--cskh' : (severe ? ' mk-tre-alert-card--severe' : ' mk-tre-alert-card--warn')) + '">'
					+ '  <div class="mk-tre-alert-card__top">'
					+ '    <div class="mk-tre-alert-card__who">'
					+ '      <div class="mk-tre-alert-card__name-row">'
					+ '        <strong class="mk-tre-alert-card__name">' + nameHtml + '</strong>'
					+ '        <span class="mk-tre-chip">Lead #' + esc(a.lead_id) + '</span>'
					+ (isCskh ? ' <span class="mk-tre-chip mk-tre-chip--cskh">Cần CSKH</span>' : '')
					+ '      </div>'
					+ '      <div class="mk-tre-muted mk-tre-alert-card__meta">'
					+ esc(a.phone || '—') + ' · Idle ' + (a.days_idle || 0) + ' ngày'
					+ '      </div>'
					+ '    </div>'
					+ '    <span class="mk-tre-alert-badge' + (isCskh ? ' mk-tre-alert-badge--cskh' : (severe ? ' mk-tre-alert-badge--severe' : '')) + '">' + esc(badgeLabel) + '</span>'
					+ '  </div>'
					+ '  <div class="mk-tre-alert-card__body">'
					+ '    <div class="mk-tre-alert-card__status">→ ' + esc((a.rule && a.rule.status_label) || '') + '</div>'
					+ (a.rule && (a.rule.next_action || a.next_action)
						? '<div class="mk-tre-alert-card__action"><span class="mk-tre-muted">Thì → </span>' + esc(a.rule.next_action || a.next_action) + '</div>'
						: '')
					+ (a.rule && a.rule.require_note
						? '<div class="mk-tre-alert-card__action"><span class="mk-tre-chip mk-tre-chip--warn">Bắt buộc ghi chú lý do</span></div>'
						: '')
					+ (tagHtml ? '    <div class="mk-tre-chips">' + tagHtml + '</div>' : '')
					+ '  </div>'
					+ '  <div class="mk-tre-alert-card__actions">'
					+ '    <a class="mk-tre-btn mk-tre-btn--ghost" href="' + esc(detailUrl) + '">Mở lead</a>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--primary js-tre-alert-done" data-lid="' + esc(a.lead_id) + '" data-rid="' + esc(a.rule && a.rule.id) + '">Đã xử lý</button>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-alert-snooze" data-lid="' + esc(a.lead_id) + '" data-rid="' + esc(a.rule && a.rule.id) + '" data-days="1">Hoãn 1 ngày</button>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-alert-snooze" data-lid="' + esc(a.lead_id) + '" data-rid="' + esc(a.rule && a.rule.id) + '" data-days="3">Hoãn 3 ngày</button>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-alert-snooze" data-lid="' + esc(a.lead_id) + '" data-rid="' + esc(a.rule && a.rule.id) + '" data-days="7">Hoãn 7 ngày</button>'
					+ '  </div>'
					+ '</article>';
			}).join('');

			if (!cards) {
				cards = ''
					+ '<div class="mk-tre-alert-empty">'
					+ '  <p><strong>Chưa có cảnh báo.</strong></p>'
					+ '  <p class="mk-tre-muted">Hiện khi: (1) lead khớp rule và idle ≥ <em>alert_days</em> của rule; '
					+ 'hoặc (2) <strong>Cần CSKH</strong> — không tương tác ≥ <strong>' + cskhDays + ' ngày</strong> '
					+ '(trừ tag Ngừng chăm sóc / Dừng chăm sóc / Không tham gia). Lead vừa tạo hôm nay chưa xuất hiện.</p>'
					+ (this.loadError ? '<p class="mk-tre-muted">Lỗi tải: ' + esc(this.loadError) + '</p>' : '')
					+ '</div>';
			}

			var html = ''
				+ '<div class="mk-tre-page mk-tre-alerts-page" lang="vi">'
				+ '  <header class="mk-tre-hero">'
				+ '    <div class="mk-tre-hero__copy">'
				+ '      <p class="mk-tre-eyebrow">Tag Rule Engine · Hỗ trợ</p>'
				+ '      <h1 class="mk-tre-title">Cảnh báo</h1>'
				+ '      <p class="mk-tre-desc">Rule quá hạn (tag khớp + idle ≥ alert_days) và <strong>Cần CSKH</strong> (không tương tác ≥ ' + cskhDays + ' ngày).</p>'
				+ '    </div>'
				+ '  </header>'
				+ '  <div class="mk-tre-stats mk-tre-stats--3">'
				+ '    <div class="mk-tre-stat"><span class="mk-tre-stat__n">' + alerts.length + '</span><span class="mk-tre-stat__l">Tổng cảnh báo</span></div>'
				+ '    <div class="mk-tre-stat"><span class="mk-tre-stat__n">' + ruleCount + '</span><span class="mk-tre-stat__l">Theo rule</span></div>'
				+ '    <div class="mk-tre-stat"><span class="mk-tre-stat__n">' + cskhCount + '</span><span class="mk-tre-stat__l">Cần CSKH</span></div>'
				+ '  </div>'
				+ '  <div class="mk-tre-alert-list">' + cards + '</div>'
				+ '</div>';

			this.$root.html(html);
		},

		bindEvents: function () {
			var self = this;
			this.$root.on('click', '.js-tre-alert-done', function () {
				var lid = $(this).data('lid');
				var rid = $(this).data('rid');
				try {
					self.alerts = store.dismissAlert(lid, rid, null);
					self.render();
					toast('Đã đánh dấu xử lý');
				} catch (e) {
					window.alert(e.message || 'Lỗi');
				}
			});
			this.$root.on('click', '.js-tre-alert-snooze', function () {
				var lid = $(this).data('lid');
				var rid = $(this).data('rid');
				var days = parseInt($(this).data('days'), 10) || 1;
				try {
					self.alerts = store.dismissAlert(lid, rid, days);
					self.render();
					toast('Đã hoãn ' + days + ' ngày');
				} catch (e) {
					window.alert(e.message || 'Lỗi');
				}
			});
		},
	};

	global.MkTagRuleAlerts = MkTagRuleAlerts;
}(window.jQuery, window));
