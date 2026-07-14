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
			// Đọc thẳng bootstrap inject (alerts đã tính sẵn trên server).
			var seeded = [];
			if (global.MK_TAG_RULE_STATE && global.MK_TAG_RULE_STATE.alerts) {
				seeded = global.MK_TAG_RULE_STATE.alerts;
			}
			if ((!seeded || !seeded.length) && store.getAlerts) {
				try { seeded = store.getAlerts(); } catch (e0) { seeded = []; }
			}
			if (seeded && seeded.length) {
				this.alerts = seeded;
			} else {
				try {
					this.alerts = store.loadAlerts();
				} catch (e) {
					this.alerts = [];
					this.loadError = (e && e.message) ? e.message : 'Không tải được cảnh báo';
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
			var late7 = alerts.filter(function (a) { return (a.days_idle - (a.rule.alert_days || 0)) >= 7; }).length;

			var cards = alerts.map(function (a) {
				var overdue = Math.max(0, (a.days_idle || 0) - (a.rule.alert_days || 0));
				var severe = overdue >= 7;
				var tagHtml = (a.rule.tag_ids || []).map(function (tid) {
					return chip(tagById[tid] ? tagById[tid].name : tid);
				}).join('');
				return ''
					+ '<article class="mk-tre-alert-card' + (severe ? ' mk-tre-alert-card--severe' : ' mk-tre-alert-card--warn') + '">'
					+ '  <div class="mk-tre-alert-card__top">'
					+ '    <div class="mk-tre-alert-card__who">'
					+ '      <div class="mk-tre-alert-card__name-row">'
					+ '        <strong class="mk-tre-alert-card__name">' + esc(a.name) + '</strong>'
					+ '        <span class="mk-tre-chip">Lead #' + esc(a.lead_id) + '</span>'
					+ '      </div>'
					+ '      <div class="mk-tre-muted mk-tre-alert-card__meta">'
					+ esc(a.phone || '—') + ' · Idle ' + (a.days_idle || 0) + ' ngày'
					+ '      </div>'
					+ '    </div>'
					+ '    <span class="mk-tre-alert-badge' + (severe ? ' mk-tre-alert-badge--severe' : '') + '">Trễ ' + overdue + ' ngày</span>'
					+ '  </div>'
					+ '  <div class="mk-tre-alert-card__body">'
					+ '    <div class="mk-tre-alert-card__status">→ ' + esc(a.rule.status_label) + '</div>'
					+ (a.rule.next_action
						? '<div class="mk-tre-alert-card__action"><span class="mk-tre-muted">Thì → </span>' + esc(a.rule.next_action) + '</div>'
						: '')
					+ (a.rule.require_note
						? '<div class="mk-tre-alert-card__action"><span class="mk-tre-chip mk-tre-chip--warn">Bắt buộc ghi chú lý do</span></div>'
						: '')
					+ '    <div class="mk-tre-chips">' + tagHtml + '</div>'
					+ '  </div>'
					+ '  <div class="mk-tre-alert-card__actions">'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--primary js-tre-alert-done" data-lid="' + esc(a.lead_id) + '" data-rid="' + esc(a.rule.id) + '">Đã xử lý</button>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-alert-snooze" data-lid="' + esc(a.lead_id) + '" data-rid="' + esc(a.rule.id) + '" data-days="1">Hoãn 1 ngày</button>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-alert-snooze" data-lid="' + esc(a.lead_id) + '" data-rid="' + esc(a.rule.id) + '" data-days="3">Hoãn 3 ngày</button>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-alert-snooze" data-lid="' + esc(a.lead_id) + '" data-rid="' + esc(a.rule.id) + '" data-days="7">Hoãn 7 ngày</button>'
					+ '  </div>'
					+ '</article>';
			}).join('');

			if (!cards) {
				cards = ''
					+ '<div class="mk-tre-alert-empty">'
					+ '  <p><strong>Chưa có cảnh báo quá hạn.</strong></p>'
					+ '  <p class="mk-tre-muted">Cảnh báo chỉ hiện khi lead <em>có tag khớp rule</em> và '
					+ '<em>idle ≥ alert_days</em> của rule đó (ví dụ R02 Chưa học = 2 ngày). '
					+ 'Lead vừa tạo/sửa hôm nay (idle 0) sẽ chưa xuất hiện.</p>'
					+ (this.loadError ? '<p class="mk-tre-muted">Lỗi tải: ' + esc(this.loadError) + '</p>' : '')
					+ '</div>';
			}

			var html = ''
				+ '<div class="mk-tre-page mk-tre-alerts-page" lang="vi">'
				+ '  <header class="mk-tre-hero">'
				+ '    <div class="mk-tre-hero__copy">'
				+ '      <p class="mk-tre-eyebrow">Tag Rule Engine · Hỗ trợ</p>'
				+ '      <h1 class="mk-tre-title">Cảnh báo</h1>'
				+ '      <p class="mk-tre-desc">Lead khớp rule quá hạn xử lý: idle ≥ alert_days → hiện hành động tiếp theo của rule.</p>'
				+ '    </div>'
				+ '  </header>'				+ '  <div class="mk-tre-stats">'
				+ '    <div class="mk-tre-stat"><span class="mk-tre-stat__n">' + alerts.length + '</span><span class="mk-tre-stat__l">Cảnh báo</span></div>'
				+ '    <div class="mk-tre-stat"><span class="mk-tre-stat__n">' + alertRules + '</span><span class="mk-tre-stat__l">Rule có hạn</span></div>'
				+ '    <div class="mk-tre-stat"><span class="mk-tre-stat__n">' + late7 + '</span><span class="mk-tre-stat__l">Trễ ≥7 ngày</span></div>'
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
