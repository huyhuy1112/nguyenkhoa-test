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
			this.nlAlerts = (global.MK_TAG_RULE_STATE && Array.isArray(global.MK_TAG_RULE_STATE.nl_alerts))
				? global.MK_TAG_RULE_STATE.nl_alerts : [];
			this.nlFilters = { q: '', sort: 'new', status: '', code: '' };
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
			var nf = this.nlFilters || { q: '', sort: 'new', status: '', code: '' };
			var html = ''
				+ '<div class="mk-tre-page mk-tre-alerts-page" lang="vi">'
				+ '  <header class="mk-tre-hero">'
				+ '    <div class="mk-tre-hero__copy">'
				+ '      <p class="mk-tre-eyebrow">Tag Rule Engine · Hỗ trợ</p>'
				+ '      <h1 class="mk-tre-title">Cảnh báo</h1>'
				+ '      <p class="mk-tre-desc">Việc nguyên liệu đang mở, rule quá hạn (tag khớp + idle ≥ alert_days) và <strong>Cần CSKH</strong> (không tương tác ≥ ' + cskhDays + ' ngày).</p>'
				+ '    </div>'
				+ '  </header>'
				+ '  <section class="mk-nl-open" aria-label="Việc nguyên liệu đang mở">'
				+ '    <div class="mk-nl-open__head"><h2>Việc nguyên liệu đang mở</h2><span class="js-nl-open-count"></span></div>'
				+ '    <div class="mk-tre-alert-filters mk-nl-open__filters" role="search">'
				+ '      <label class="mk-tre-alert-filters__search">'
				+ '        <span class="mk-tre-muted">Tìm khách</span>'
				+ '        <span class="mk-tre-alert-filters__search-row">'
				+ '          <input type="text" class="mk-tre-input js-nl-q" placeholder="Tên khách…" value="' + esc(nf.q) + '" autocomplete="off" spellcheck="false" />'
				+ '          <button type="button" class="mk-tre-btn mk-tre-btn--primary js-nl-search">Tìm</button>'
				+ '        </span>'
				+ '      </label>'
				+ '      <label class="mk-tre-alert-filters__sort">'
				+ '        <span class="mk-tre-muted">Sắp xếp</span>'
				+ '        <select class="mk-tre-input js-nl-sort">'
				+ '          <option value="new"' + (nf.sort !== 'old' ? ' selected' : '') + '>Mới nhất trước</option>'
				+ '          <option value="old"' + (nf.sort === 'old' ? ' selected' : '') + '>Cũ nhất trước</option>'
				+ '        </select>'
				+ '      </label>'
				+ '      <label class="mk-tre-alert-filters__sort">'
				+ '        <span class="mk-tre-muted">Trạng thái</span>'
				+ '        <select class="mk-tre-input js-nl-status">' + this.nlStatusOptions(nf.status) + '</select>'
				+ '      </label>'
				+ '      <label class="mk-tre-alert-filters__sort">'
				+ '        <span class="mk-tre-muted">Loại việc</span>'
				+ '        <select class="mk-tre-input js-nl-code">' + this.nlCodeOptions(nf.code) + '</select>'
				+ '      </label>'
				+ '      <button type="button" class="mk-tre-btn mk-tre-btn--ghost js-nl-clear"' + (this.nlFilterActive() ? '' : ' hidden') + '>Xoá lọc</button>'
				+ '    </div>'
				+ '    <div class="js-nl-open-list mk-nl-open__list"></div>'
				+ '  </section>'
				+ '  <section class="mk-nl-metrics" aria-label="Chỉ số một khách">'
				+ '    <div class="mk-nl-open__head"><h2>Chỉ số một khách</h2></div>'
				+ '    <p class="mk-nl-metrics__hint">Nhập mã khách hàng nguyên liệu để xem tổng đã mua, số đơn và kỳ mua lại.</p>'
				+ '    <form class="mk-nl-metrics__form js-nl-metrics">'
				+ '      <input type="number" class="mk-tre-input js-nl-contact" min="1" placeholder="Mã khách hàng" />'
				+ '      <button type="submit" class="mk-tre-btn mk-tre-btn--primary">Xem chỉ số</button>'
				+ '    </form>'
				+ '    <div class="js-nl-metrics-board"></div>'
				+ '  </section>'
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
			this.renderNlOpen();
			this.renderResults();
		},

		nlCodes: function () {
			return {
				NL01: 'Khách mới chưa gọi',
				NL02: 'Đã học chưa mua',
				NL03: 'Nhiệm vụ quá hạn',
				NL04: 'Cơ hội chưa có bước tiếp',
				NL05: 'Báo giá sắp hoặc đã quá hạn',
				NL06: 'Đơn quá hạn thanh toán',
				NL07: 'Chưa mua lần hai',
				NL08: 'Sắp đến kỳ mua lại',
				NL09: 'Quá kỳ mua lại',
				NL10: 'Doanh thu giảm',
				NL11: 'Một mặt hàng mua ít đi',
				NL12: 'Khách lớn đang rủi ro',
				NL16: 'Đơn giao trễ',
				NL17: 'Sự cố hàng hoặc giao hàng',
				NL18: 'Sự cố giao lặp lại',
				NL19: 'Khiếu nại quá hạn'
			};
		},

		nlStatusLabel: function (status) {
			var map = {
				new: 'Mới',
				accepted: 'Đã nhận',
				working: 'Đang xử lý',
				done: 'Hoàn thành',
				snoozed: 'Tạm hoãn',
				na: 'Không áp dụng'
			};
			return map[status] || status || 'Mới';
		},

		nlStatusOptions: function (current) {
			var options = [
				['', 'Tất cả trạng thái'],
				['new', 'Mới'],
				['accepted', 'Đã nhận'],
				['working', 'Đang xử lý'],
				['snoozed', 'Tạm hoãn']
			];
			return options.map(function (pair) {
				return '<option value="' + pair[0] + '"' + (current === pair[0] ? ' selected' : '') + '>' + pair[1] + '</option>';
			}).join('');
		},

		nlCodeOptions: function (current) {
			var codes = this.nlCodes();
			var html = '<option value=""' + (current ? '' : ' selected') + '>Tất cả loại việc</option>';
			Object.keys(codes).forEach(function (code) {
				html += '<option value="' + code + '"' + (current === code ? ' selected' : '') + '>' + code + ' · ' + codes[code] + '</option>';
			});
			return html;
		},

		nlFilterActive: function () {
			var f = this.nlFilters || {};
			return !!(String(f.q || '').trim() || f.status || f.code || f.sort === 'old');
		},

		readNlFilters: function () {
			if (!this.$root) return;
			if (!this.nlFilters) this.nlFilters = { q: '', sort: 'new', status: '', code: '' };
			var $q = this.$root.find('.js-nl-q');
			var $sort = this.$root.find('.js-nl-sort');
			var $status = this.$root.find('.js-nl-status');
			var $code = this.$root.find('.js-nl-code');
			if ($q.length) this.nlFilters.q = String($q.val() || '');
			if ($sort.length) this.nlFilters.sort = String($sort.val() || 'new');
			if ($status.length) this.nlFilters.status = String($status.val() || '');
			if ($code.length) this.nlFilters.code = String($code.val() || '');
			var $clear = this.$root.find('.js-nl-clear');
			if (this.nlFilterActive()) $clear.removeAttr('hidden');
			else $clear.attr('hidden', 'hidden');
		},

		filteredNl: function () {
			var f = this.nlFilters || {};
			var q = fold(f.q).trim();
			var rows = (this.nlAlerts || []).filter(function (a) {
				if (f.status && a.status !== f.status) return false;
				if (f.code && a.code !== f.code) return false;
				if (!q) return true;
				return fold([a.name, a.title, a.detail, a.code, a.contact_id].join(' ')).indexOf(q) !== -1;
			});
			rows.sort(function (a, b) {
				var av = String(a.opened_at || '');
				var bv = String(b.opened_at || '');
				return f.sort === 'old' ? av.localeCompare(bv) : bv.localeCompare(av);
			});
			return rows;
		},

		nlOpenedLabel: function (raw) {
			var text = String(raw || '').trim();
			if (text.length < 10) return '';
			var day = text.slice(0, 10).split('-').reverse().join('/');
			var time = text.length >= 16 ? text.slice(11, 16) : '';
			return 'Mở ' + day + (time ? (' ' + time) : '');
		},

		renderNlOpen: function () {
			if (!this.$root || !this.$root.length) return;
			var box = this.$root.find('.js-nl-open-list');
			if (!box.length) return;
			var all = this.nlAlerts || [];
			var rows = this.filteredNl();
			var count = rows.length + (all.length && rows.length !== all.length ? (' / ' + all.length) : '') + ' việc';
			this.$root.find('.js-nl-open-count').text(all.length ? count : '');
			if (!rows.length) {
				box.html('<div class="mk-tre-alert-empty"><p><strong>'
					+ (all.length ? 'Không khớp bộ lọc.' : 'Chưa có việc nguyên liệu.')
					+ '</strong></p><p class="mk-tre-muted">'
					+ (all.length
						? 'Thử xoá lọc hoặc chọn loại việc khác.'
						: 'Việc hiện khi báo giá hoặc đơn quá hạn, hoặc khi ngưỡng đã nhập khớp khách.')
					+ '</p></div>');
				return;
			}
			var self = this;
			var codes = this.nlCodes();
			box.html(rows.map(function (a) {
				var href = a.contact_id
					? ('index.php?module=Contacts&view=Detail&record=' + encodeURIComponent(a.contact_id) + '&app=SALES')
					: '';
				var name = href
					? ('<a class="mk-tre-alert-card__name-link" href="' + esc(href) + '">' + esc(a.name) + '</a>')
					: esc(a.name);
				var tone = a.status === 'working' ? ' mk-tre-alert-badge--work' : (a.status === 'snoozed' ? ' mk-tre-alert-badge--snooze' : (a.status === 'accepted' ? ' mk-tre-alert-badge--ok' : ''));
				var opened = self.nlOpenedLabel(a.opened_at);
				return ''
					+ '<article class="mk-tre-alert-card mk-nl-open__card" data-id="' + esc(a.id) + '">'
					+ '  <div class="mk-tre-alert-card__top">'
					+ '    <div class="mk-tre-alert-card__who">'
					+ '      <div class="mk-tre-alert-card__name-row">'
					+ '        <strong class="mk-tre-alert-card__name">' + name + '</strong>'
					+ '        <span class="mk-tre-chip mk-tre-chip--primary">' + esc(a.code) + '</span>'
					+ '      </div>'
					+ '      <div class="mk-tre-muted mk-tre-alert-card__meta">'
					+ esc(codes[a.code] || a.code)
					+ (opened ? (' · ' + esc(opened)) : '')
					+ (a.contact_id ? (' · Khách #' + esc(a.contact_id)) : '')
					+ '      </div>'
					+ '    </div>'
					+ '    <span class="mk-tre-alert-badge' + tone + '">' + esc(self.nlStatusLabel(a.status)) + '</span>'
					+ '  </div>'
					+ '  <div class="mk-tre-alert-card__body">'
					+ '    <div class="mk-tre-alert-card__status">' + esc(a.title) + '</div>'
					+ (a.detail ? '<div class="mk-tre-alert-card__action">' + esc(a.detail) + '</div>' : '')
					+ '  </div>'
					+ '  <div class="mk-tre-alert-card__actions">'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--ghost" data-next="accepted">Đã nhận</button>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--primary" data-next="working">Đang xử lý</button>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--primary" data-next="done">Hoàn thành</button>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--ghost" data-next="snoozed">Tạm hoãn</button>'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--ghost" data-next="na">Không áp dụng</button>'
					+ '  </div>'
					+ '  <div class="mk-nl-open__close" hidden>'
					+ '    <input type="text" data-field="result_note" placeholder="Kết quả / lý do" />'
					+ '    <input type="text" data-field="evidence_ref" placeholder="Bằng chứng: đơn, cuộc gọi, chứng từ" />'
					+ '    <input type="text" data-field="next_task" placeholder="Việc tiếp theo" />'
					+ '    <input type="text" data-field="next_owner" placeholder="Người phụ trách" />'
					+ '    <input type="date" data-field="next_due" title="Hạn việc tiếp theo" />'
					+ '    <input type="date" data-field="snooze_until" title="Ngày kiểm tra lại nếu tạm hoãn" />'
					+ '    <button type="button" class="mk-tre-btn mk-tre-btn--primary" data-send="1">Ghi</button>'
					+ '  </div>'
					+ '</article>';
			}).join(''));
		},

		renderNlMetrics: function (m) {
			var board = this.$root.find('.js-nl-metrics-board');
			if (!board.length || !m) return;
			function tile(code, label, value) {
				var empty = value === 'chưa đủ dữ liệu' ? ' is-empty' : '';
				return '<article class="mk-nl-tile' + empty + '"><small>' + code + '</small><span>' + label + '</span><strong>' + esc(value) + '</strong></article>';
			}
			board.html(''
				+ '<div class="mk-nl-board">'
				+ '  <div class="mk-nl-board__status">'
				+ '    <div><em>Vòng đời</em><strong>' + esc(m.ct09_life) + '</strong></div>'
				+ '    <div><em>Hạng</em><strong>' + esc(m.ct09_tier) + '</strong></div>'
				+ '  </div>'
				+ '  <div class="mk-nl-board__grid">'
				+ tile('CT01', 'Tổng đã mua', m.ct01)
				+ tile('CT02', 'Mua 90 ngày', m.ct02)
				+ tile('CT03', 'Số đơn', m.ct03)
				+ tile('CT04', 'Đơn trung bình', m.ct04)
				+ tile('CT05', 'Mua gần nhất', m.ct05)
				+ tile('CT07', 'Khoảng cách mua', m.ct07)
				+ tile('CT08', 'Chờ giao', m.ct08)
				+ '  </div>'
				+ '</div>');
		},

		lookupNlMetrics: function () {
			var self = this;
			var id = parseInt(this.$root.find('.js-nl-contact').val(), 10);
			if (!id) {
				window.alert('Nhập mã khách hàng.');
				return;
			}
			app.request.post({
				data: { module: 'HelpDesk', action: 'MaterialAlertsApi', mode: 'metrics', contact_id: id }
			}).then(function (err, res) {
				var data = res && res.metrics ? res : (res && res.result ? res.result : null);
				if (err || !data || !data.metrics) {
					window.alert((err && (err.message || err)) || 'Không lấy được chỉ số khách.');
					return;
				}
				self.renderNlMetrics(data.metrics);
			});
		},

		reloadNlOpen: function () {
			var self = this;
			if (typeof app === 'undefined' || !app.request) return;
			app.request.post({
				data: { module: 'HelpDesk', action: 'MaterialAlertsApi', mode: 'list' }
			}).then(function (err, res) {
				var data = res && res.alerts ? res : (res && res.result ? res.result : null);
				if (err || !data || !data.alerts) {
					window.alert((err && err.message) || 'Không tải lại được việc nguyên liệu.');
					return;
				}
				self.nlAlerts = data.alerts;
				self.renderNlOpen();
			});
		},

		transitionNl: function (card, next) {
			var self = this;
			var data = {
				module: 'HelpDesk',
				action: 'MaterialAlertsApi',
				mode: 'transition',
				id: card.getAttribute('data-id'),
				next: next
			};
			card.querySelectorAll('[data-field]').forEach(function (input) {
				data[input.getAttribute('data-field')] = input.value;
			});
			app.request.post({ data: data }).then(function (err, res) {
				var failed = err || (res && res.error);
				if (failed) {
					window.alert((err && (err.message || err)) || (res && res.error && res.error.message) || 'Chưa đủ điều kiện để tắt việc.');
					return;
				}
				toast('Đã cập nhật việc nguyên liệu');
				self.reloadNlOpen();
			});
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
			this.$root.on('keydown', '.js-nl-q', function (e) {
				if (e.key === 'Enter' || e.keyCode === 13) {
					e.preventDefault();
					if (self.composing) return;
					self.readNlFilters();
					self.renderNlOpen();
				}
			});
			this.$root.on('click', '.js-nl-search', function () {
				self.readNlFilters();
				self.renderNlOpen();
			});
			this.$root.on('change', '.js-nl-sort, .js-nl-status, .js-nl-code', function () {
				self.readNlFilters();
				self.renderNlOpen();
			});
			this.$root.on('click', '.js-nl-clear', function () {
				self.nlFilters = { q: '', sort: 'new', status: '', code: '' };
				self.$root.find('.js-nl-q').val('');
				self.$root.find('.js-nl-sort').val('new');
				self.$root.find('.js-nl-status').val('');
				self.$root.find('.js-nl-code').val('');
				self.$root.find('.js-nl-clear').attr('hidden', 'hidden');
				self.renderNlOpen();
			});
			this.$root.on('submit', '.js-nl-metrics', function (e) {
				e.preventDefault();
				self.lookupNlMetrics();
			});
			this.$root.on('click', '.mk-nl-open__card [data-next]', function () {
				var next = $(this).attr('data-next');
				var card = this.closest('.mk-nl-open__card');
				if (!card) return;
				if (next === 'accepted' || next === 'working') {
					self.transitionNl(card, next);
					return;
				}
				var close = card.querySelector('.mk-nl-open__close');
				if (close) close.hidden = false;
				card.setAttribute('data-next', next);
			});
			this.$root.on('click', '.mk-nl-open__card [data-send]', function () {
				var card = this.closest('.mk-nl-open__card');
				if (!card) return;
				var next = card.getAttribute('data-next') || '';
				if (!next) {
					window.alert('Chọn Hoàn thành, Tạm hoãn hoặc Không áp dụng trước khi ghi.');
					return;
				}
				self.transitionNl(card, next);
			});
		},
	};

	global.MkTagRuleAlerts = MkTagRuleAlerts;
}(window.jQuery, window));
