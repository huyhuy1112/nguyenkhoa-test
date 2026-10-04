/**
 * Tag Rule Engine — Cảnh báo hành động từ Lead thật (DB).
 * Search tên / SĐT, sắp xếp sớm↔trễ, lọc khoảng ngày last_touch.
 * Filter bar stays mounted (không re-render) để gõ dấu IME không bị cắt.
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

	function fold(s) {
		var t = String(s || '').toLowerCase();
		try {
			if (typeof t.normalize === 'function') {
				t = t.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
			}
		} catch (e) { /* ignore */ }
		return t.replace(/đ/g, 'd');
	}

	function overdueDays(a, cskhDays) {
		var isCskh = a.alert_type === 'cskh' || (a.rule && a.rule.id === 'rule-cskh');
		var idle = parseInt(a.days_idle, 10) || 0;
		if (isCskh) {
			return Math.max(0, idle - (cskhDays || 7));
		}
		var ad = (a.rule && a.rule.alert_days != null) ? parseInt(a.rule.alert_days, 10) : 0;
		return Math.max(0, idle - (ad || 0));
	}

	function touchDate(a) {
		var raw = String(a.last_touch || '').trim();
		if (raw.length >= 10) {
			return raw.slice(0, 10);
		}
		var idle = parseInt(a.days_idle, 10);
		if (isNaN(idle) || idle < 0) {
			return '';
		}
		var d = new Date();
		d.setHours(12, 0, 0, 0);
		d.setDate(d.getDate() - idle);
		var m = d.getMonth() + 1;
		var day = d.getDate();
		return d.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (day < 10 ? '0' : '') + day;
	}

	var MkTagRuleAlerts = {
		$root: null,
		alerts: [],
		shellReady: false,
		composing: false,
		searchTimer: null,
		filters: {
			q: '',
			sort: 'late',
			from: '',
			to: '',
		},

		init: function () {
			this.$root = $('#mk-tag-rule-alerts');
			if (!this.$root.length) return;
			if (this.$root.data('mk-tre-inited')) {
				this.render();
				return;
			}
			this.$root.data('mk-tre-inited', 1);
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
			this.render(true);
			this.bindEvents();
		},

		cskhDays: function () {
			var cskhDays = (global.MK_TAG_RULE_STATE && global.MK_TAG_RULE_STATE.cskh_alert_days)
				? parseInt(global.MK_TAG_RULE_STATE.cskh_alert_days, 10) : 7;
			return (!cskhDays || cskhDays < 1) ? 7 : cskhDays;
		},

		readFiltersFromDom: function () {
			if (!this.$root) return;
			var $q = this.$root.find('.js-tre-alert-q');
			var $sort = this.$root.find('.js-tre-alert-sort');
			var $from = this.$root.find('.js-tre-alert-from');
			var $to = this.$root.find('.js-tre-alert-to');
			if ($q.length) this.filters.q = String($q.val() || '');
			if ($sort.length) this.filters.sort = String($sort.val() || 'late');
			if ($from.length) this.filters.from = String($from.val() || '');
			if ($to.length) this.filters.to = String($to.val() || '');
		},

		hasActiveFilter: function () {
			var f = this.filters;
			return !!(String(f.q || '').trim() || f.from || f.to || (f.sort && f.sort !== 'late'));
		},

		syncClearButton: function () {
			var $btn = this.$root.find('.js-tre-alert-clear');
			if (!$btn.length) return;
			if (this.hasActiveFilter()) {
				$btn.removeAttr('hidden');
			} else {
				$btn.attr('hidden', 'hidden');
			}
		},

		getFilteredAlerts: function (cskhDays) {
			var q = fold(this.filters.q).trim();
			var from = String(this.filters.from || '').trim();
			var to = String(this.filters.to || '').trim();
			var list = (this.alerts || []).slice();

			list = list.filter(function (a) {
				if (q) {
					var hay = fold([a.name, a.phone, a.lead_id, a.next_action,
						(a.rule && a.rule.status_label) || '',
						(a.rule && a.rule.next_action) || ''].join(' '));
					if (hay.indexOf(q) < 0) {
						return false;
					}
				}
				var touch = touchDate(a);
				if (from && (!touch || touch < from)) {
					return false;
				}
				if (to && (!touch || touch > to)) {
					return false;
				}
				return true;
			});

			var lateFirst = this.filters.sort !== 'early';
			list.sort(function (a, b) {
				var oa = overdueDays(a, cskhDays);
				var ob = overdueDays(b, cskhDays);
				if (oa !== ob) {
					return lateFirst ? (ob - oa) : (oa - ob);
				}
				var da = parseInt(a.days_idle, 10) || 0;
				var db = parseInt(b.days_idle, 10) || 0;
				if (da !== db) {
					return lateFirst ? (db - da) : (da - db);
				}
				return (parseInt(a.lead_id, 10) || 0) - (parseInt(b.lead_id, 10) || 0);
			});
			return list;
		},

		buildCardsHtml: function (alerts, all, cskhDays) {
			var tags = store.getTags();
			var tagById = {};
			tags.forEach(function (t) { tagById[t.id] = t; });

			var cards = alerts.map(function (a) {
				var isCskh = a.alert_type === 'cskh' || (a.rule && a.rule.id === 'rule-cskh');
				var overdue = overdueDays(a, cskhDays);
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
				var touch = touchDate(a);
				var touchLabel = touch
					? touch.split('-').reverse().join('/')
					: '—';
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
					+ ' · Tương tác cuối: ' + esc(touchLabel)
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

			if (cards) {
				return cards;
			}
			return ''
				+ '<div class="mk-tre-alert-empty">'
				+ (all.length
					? '<p><strong>Không khớp bộ lọc.</strong></p><p class="mk-tre-muted">Thử xoá search / đổi khoảng ngày hoặc sắp xếp.</p>'
					: ('<p><strong>Chưa có cảnh báo.</strong></p>'
						+ '<p class="mk-tre-muted">Hiện khi: (1) lead khớp rule và idle ≥ <em>alert_days</em> của rule; '
						+ 'hoặc (2) <strong>Cần CSKH</strong> — không tương tác ≥ <strong>' + cskhDays + ' ngày</strong> '
						+ '(trừ tag Ngừng chăm sóc / Dừng chăm sóc / Không tham gia). Lead vừa tạo hôm nay chưa xuất hiện.</p>'))
				+ (this.loadError ? '<p class="mk-tre-muted">Lỗi tải: ' + esc(this.loadError) + '</p>' : '')
				+ '</div>';
		},

		/** Chỉ cập nhật stats + list — không đụng filter inputs. */
		renderResults: function () {
			if (!this.$root || !this.$root.length) return;
			if (!this.shellReady || !this.$root.find('.js-tre-alert-list').length) {
				this.renderShell();
			}
			var all = this.alerts || [];
			var cskhDays = this.cskhDays();
			var alerts = this.getFilteredAlerts(cskhDays);
			var cskhCount = alerts.filter(function (a) {
				return a.alert_type === 'cskh' || (a.rule && a.rule.id === 'rule-cskh');
			}).length;
			var ruleCount = alerts.length - cskhCount;
			var hasFilter = this.hasActiveFilter();

			this.$root.find('.js-tre-alert-stats').html(
				'<div class="mk-tre-stat"><span class="mk-tre-stat__n">' + alerts.length + '</span><span class="mk-tre-stat__l">'
				+ (hasFilter ? 'Đang hiện / ' + all.length : 'Tổng cảnh báo') + '</span></div>'
				+ '<div class="mk-tre-stat"><span class="mk-tre-stat__n">' + ruleCount + '</span><span class="mk-tre-stat__l">Theo rule</span></div>'
				+ '<div class="mk-tre-stat"><span class="mk-tre-stat__n">' + cskhCount + '</span><span class="mk-tre-stat__l">Cần CSKH</span></div>'
			);
			this.$root.find('.js-tre-alert-list').html(this.buildCardsHtml(alerts, all, cskhDays));
			this.syncClearButton();
		},

		renderShell: function () {
			var cskhDays = this.cskhDays();
			var f = this.filters;
			var html = ''
				+ '<div class="mk-tre-page mk-tre-alerts-page" lang="vi">'
				+ '  <header class="mk-tre-hero">'
				+ '    <div class="mk-tre-hero__copy">'
				+ '      <p class="mk-tre-eyebrow">Tag Rule Engine · Hỗ trợ</p>'
				+ '      <h1 class="mk-tre-title">Cảnh báo</h1>'
				+ '      <p class="mk-tre-desc">Rule quá hạn (tag khớp + idle ≥ alert_days) và <strong>Cần CSKH</strong> (không tương tác ≥ ' + cskhDays + ' ngày).</p>'
				+ '    </div>'
				+ '  </header>'
				+ '  <div class="mk-tre-alert-filters" role="search">'
				+ '    <label class="mk-tre-alert-filters__search">'
				+ '      <span class="mk-tre-muted">Tìm</span>'
				+ '      <span class="mk-tre-alert-filters__search-row">'
				+ '        <input type="text" class="mk-tre-input js-tre-alert-q" placeholder="Tên, SĐT, Lead #…" value="' + esc(f.q) + '" autocomplete="off" spellcheck="false" />'
				+ '        <button type="button" class="mk-tre-btn mk-tre-btn--primary js-tre-alert-search">Tìm</button>'
				+ '      </span>'
				+ '    </label>'
				+ '    <label class="mk-tre-alert-filters__sort">'
				+ '      <span class="mk-tre-muted">Sắp xếp</span>'
				+ '      <select class="mk-tre-input js-tre-alert-sort">'
				+ '        <option value="late"' + (f.sort !== 'early' ? ' selected' : '') + '>Trễ nhất trước</option>'
				+ '        <option value="early"' + (f.sort === 'early' ? ' selected' : '') + '>Sớm nhất trước</option>'
				+ '      </select>'
				+ '    </label>'
				+ '    <label class="mk-tre-alert-filters__date">'
				+ '      <span class="mk-tre-muted">Từ ngày</span>'
				+ '      <input type="date" class="mk-tre-input js-tre-alert-from" value="' + esc(f.from) + '" />'
				+ '    </label>'
				+ '    <label class="mk-tre-alert-filters__date">'
				+ '      <span class="mk-tre-muted">Đến ngày</span>'
				+ '      <input type="date" class="mk-tre-input js-tre-alert-to" value="' + esc(f.to) + '" />'
				+ '    </label>'
				+ '    <button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-alert-clear" hidden>Xoá lọc</button>'
				+ '  </div>'
				+ '  <div class="mk-tre-stats mk-tre-stats--3 js-tre-alert-stats"></div>'
				+ '  <div class="mk-tre-alert-list js-tre-alert-list"></div>'
				+ '</div>';
			this.$root.html(html);
			this.shellReady = true;
			this.renderResults();
		},

		/**
		 * @param {boolean} [forceShell] rebuild toàn trang (init / clear reset)
		 */
		render: function (forceShell) {
			if (forceShell || !this.shellReady || !this.$root.find('.js-tre-alert-list').length) {
				this.renderShell();
				return;
			}
			this.renderResults();
		},

		applySearch: function () {
			this.readFiltersFromDom();
			this.renderResults();
		},

		bindEvents: function () {
			var self = this;
			this.$root.on('compositionstart', '.js-tre-alert-q', function () {
				self.composing = true;
				if (self.searchTimer) {
					clearTimeout(self.searchTimer);
					self.searchTimer = null;
				}
			});
			this.$root.on('compositionend', '.js-tre-alert-q', function () {
				self.composing = false;
			});
			// Không lọc theo từng phím — chỉ khi bấm Tìm / Enter (ổn IME + chuột).
			this.$root.on('keydown', '.js-tre-alert-q', function (e) {
				if (e.key === 'Enter' || e.keyCode === 13) {
					e.preventDefault();
					if (self.composing) return;
					self.applySearch();
				}
			});
			this.$root.on('click', '.js-tre-alert-search', function () {
				if (self.composing) return;
				self.applySearch();
			});
			this.$root.on('change', '.js-tre-alert-sort, .js-tre-alert-from, .js-tre-alert-to', function () {
				self.readFiltersFromDom();
				self.renderResults();
			});
			this.$root.on('click', '.js-tre-alert-clear', function () {
				if (self.searchTimer) {
					clearTimeout(self.searchTimer);
					self.searchTimer = null;
				}
				self.filters = { q: '', sort: 'late', from: '', to: '' };
				self.$root.find('.js-tre-alert-q').val('');
				self.$root.find('.js-tre-alert-sort').val('late');
				self.$root.find('.js-tre-alert-from').val('');
				self.$root.find('.js-tre-alert-to').val('');
				self.renderResults();
			});
			this.$root.on('click', '.js-tre-alert-done', function () {
				var lid = $(this).data('lid');
				var rid = $(this).data('rid');
				try {
					self.readFiltersFromDom();
					self.alerts = store.dismissAlert(lid, rid, null);
					self.renderResults();
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
					self.readFiltersFromDom();
					self.alerts = store.dismissAlert(lid, rid, days);
					self.renderResults();
					toast('Đã hoãn ' + days + ' ngày');
				} catch (e) {
					window.alert(e.message || 'Lỗi');
				}
			});
		},
	};

	global.MkTagRuleAlerts = MkTagRuleAlerts;
}(window.jQuery, window));
