/**
 * Tag Rule Engine — Quản lý (Rule / Tag / Kịch bản)
 * UI matches nguyenkhoa-tst.lovable.app/manage — in-memory only.
 */
(function ($, global) {
	'use strict';

	var store = global.MkTagRuleEngineStore;
	if (!store) return;

	var ICONS = {
		pencil: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>',
		trash: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>',
		copy: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>',
		plus: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>',
		search: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
	};

	function esc(s) {
		if (s == null) return '';
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function tagChip(name, tone) {
		tone = tone || 'muted';
		return '<span class="mk-tre-chip mk-tre-chip--' + tone + '">' + esc(name) + '</span>';
	}

	function toast(msg) {
		if (typeof app !== 'undefined' && app.helper && app.helper.showSuccessNotification) {
			app.helper.showSuccessNotification({ message: msg });
		} else {
			window.alert(msg);
		}
	}

	function countActiveRules(rules) {
		var n = 0;
		for (var i = 0; i < rules.length; i++) {
			if (rules[i].is_active) n++;
		}
		return n;
	}

	function uniqSorted(list) {
		var set = {};
		(list || []).forEach(function (v) {
			v = String(v || '').trim();
			if (v) set[v] = true;
		});
		return Object.keys(set).sort(function (a, b) {
			return a.localeCompare(b, 'vi');
		});
	}

	function getTagCategories() {
		return uniqSorted(store.getTags().map(function (t) {
			return t.category;
		}));
	}

	function getScenarioChannels() {
		var fromApi = store.getChannelOptions ? store.getChannelOptions() : [];
		if (fromApi && fromApi.length) {
			return uniqSorted(fromApi.concat(
				store.getScenarios().map(function (s) { return s.channel; })
			));
		}
		var fromTags = store.getTags().filter(function (t) {
			var cat = String(t.category || '').trim();
			var catLower = cat.toLowerCase();
			return cat === 'Nguồn' || cat === 'Kênh' || catLower === 'nguon' || catLower === 'kenh';
		}).map(function (t) { return t.name; });
		return uniqSorted(fromTags.concat(
			store.getScenarios().map(function (s) { return s.channel; })
		));
	}

	function getScenarioOwners() {
		var fromApi = store.getAssigneeOptions ? store.getAssigneeOptions() : [];
		if (fromApi && fromApi.length) {
			var labels = fromApi.map(function (o) { return o.value || o.label; });
			return uniqSorted(labels.concat(
				store.getScenarios().map(function (s) { return s.owner; })
			));
		}
		return uniqSorted(store.getScenarios().map(function (s) { return s.owner; }));
	}

	function buildPickOrNewSelect(baseName, options, current, placeholder) {
		var cur = String(current || '').trim();
		var inList = options.indexOf(cur) >= 0;
		var isNew = cur && !inList;
		var ph = placeholder || '— Chọn —';
		var triggerLabel = isNew ? '＋ Thêm mới…' : (cur || ph);

		var opts = '<option value="">' + esc(ph) + '</option>';
		options.forEach(function (v) {
			opts += '<option value="' + esc(v) + '"' + (v === cur && !isNew ? ' selected' : '') + '>' + esc(v) + '</option>';
		});
		opts += '<option value="__new__"' + (isNew ? ' selected' : '') + '>＋ Thêm mới…</option>';

		var listHtml = ''
			+ '<button type="button" class="mk-tre-pick-dd__option' + (!cur && !isNew ? ' is-selected' : '') + '" data-value="">' + esc(ph) + '</button>';
		options.forEach(function (v) {
			listHtml += '<button type="button" class="mk-tre-pick-dd__option' + (v === cur && !isNew ? ' is-selected' : '') + '" data-value="' + esc(v) + '">' + esc(v) + '</button>';
		});
		listHtml += '<button type="button" class="mk-tre-pick-dd__option mk-tre-pick-dd__option--new' + (isNew ? ' is-selected' : '') + '" data-value="__new__">＋ Thêm mới…</button>';

		return ''
			+ '<div class="mk-tre-pick-or-new mk-tre-pick-dd" data-field="' + esc(baseName) + '">'
			+ '  <select class="mk-tre-pick-dd__native js-tre-pick-select" name="' + esc(baseName) + '_select" hidden tabindex="-1" aria-hidden="true">' + opts + '</select>'
			+ '  <button type="button" class="mk-tre-input mk-tre-pick-dd__trigger js-tre-pick-trigger" aria-haspopup="listbox" aria-expanded="false">' + esc(triggerLabel) + '</button>'
			+ '  <div class="mk-tre-pick-dd__panel" hidden role="listbox">'
			+ '    <input type="search" class="mk-tre-pick-dd__search js-tre-pick-search" placeholder="Tìm kiếm…" autocomplete="off" />'
			+ '    <div class="mk-tre-pick-dd__list">' + listHtml + '</div>'
			+ '    <p class="mk-tre-pick-dd__empty" hidden>Không tìm thấy</p>'
			+ '  </div>'
			+ '  <label class="mk-tre-field mk-tre-pick-or-new__new js-tre-pick-new-wrap"' + (isNew ? '' : ' hidden') + '>'
			+ '    <span>Nhập mới</span>'
			+ '    <input class="mk-tre-input js-tre-pick-new" name="' + esc(baseName) + '_new" value="' + esc(isNew ? cur : '') + '" autocomplete="off" />'
			+ '  </label>'
			+ '</div>';
	}

	function closeAllPickDropdowns(exceptEl) {
		$('#mk-tre-modal .mk-tre-pick-dd.is-open').each(function () {
			if (exceptEl && this === exceptEl) return;
			var $w = $(this);
			$w.removeClass('is-open');
			$w.find('.mk-tre-pick-dd__panel').prop('hidden', true);
			$w.find('.js-tre-pick-trigger').attr('aria-expanded', 'false');
		});
	}

	function resolvePickOrNew(data, baseName) {
		var sel = data[baseName + '_select'];
		if (sel === '__new__') {
			return String(data[baseName + '_new'] || '').trim();
		}
		return String(sel || '').trim();
	}

	function ruleMatchesSearch(rule, q, tagMap) {
		if (!q) return true;
		var hay = [
			rule.status_label,
			rule.name,
			rule.next_action,
		].join(' ').toLowerCase();
		(rule.tag_ids || []).forEach(function (tid) {
			if (tagMap[tid]) hay += ' ' + String(tagMap[tid].name || '').toLowerCase();
		});
		return hay.indexOf(q) >= 0;
	}

	var MkTagRuleEngine = {
		$root: null,
		activeTab: 'rules',
		ruleSearch: '',
		tagSearch: '',

		init: function () {
			this.$root = $('#mk-tag-rule-engine');
			if (!this.$root.length) return;
			this.render();
			this.bindEvents();
		},

		render: function () {
			var html = ''
				+ '<div class="mk-tre-page" lang="vi">'
				+ '  <header class="mk-tre-hero">'
				+ '    <div class="mk-tre-hero__copy">'
				+ '      <p class="mk-tre-eyebrow">Tag Rule Engine · Hỗ trợ</p>'
				+ '      <h1 class="mk-tre-title">Quản lý</h1>'
				+ '    </div>'
				+ '  </header>'
				+ '  <div class="mk-tre-stats" id="mk-tre-stats"></div>'
				+ '  <div class="mk-tre-toolbar-bar">'
				+ '    <div class="mk-tre-tabs" role="tablist">'
				+ '      <button type="button" class="mk-tre-tab' + (this.activeTab === 'rules' ? ' is-active' : '') + '" data-tab="rules">Rule</button>'
				+ '      <button type="button" class="mk-tre-tab' + (this.activeTab === 'tags' ? ' is-active' : '') + '" data-tab="tags">Tag</button>'
				+ '      <button type="button" class="mk-tre-tab' + (this.activeTab === 'scenarios' ? ' is-active' : '') + '" data-tab="scenarios">Kịch bản</button>'
				+ '    </div>'
				+ '  </div>'
				+ '  <div class="mk-tre-panel" id="mk-tre-panel"></div>'
				+ '</div>'
				+ '<div class="mk-tre-modal-backdrop" id="mk-tre-modal" hidden>'
				+ '  <div class="mk-tre-modal" role="dialog" aria-modal="true">'
				+ '    <div class="mk-tre-modal__head"><h2 id="mk-tre-modal-title"></h2><button type="button" class="mk-tre-modal__close" aria-label="Đóng">&times;</button></div>'
				+ '    <div class="mk-tre-modal__body" id="mk-tre-modal-body"></div>'
				+ '    <div class="mk-tre-modal__foot" id="mk-tre-modal-foot"></div>'
				+ '  </div>'
				+ '</div>';

			this.$root.html(html);
			this.$root.find('.mk-tre-page').addClass('mk-tre-page--init');
			this.renderStats();
			this.renderPanel();
			window.setTimeout(function () {
				$('#mk-tag-rule-engine .mk-tre-page').removeClass('mk-tre-page--init');
			}, 500);
		},

		renderStats: function () {
			var rules = store.getRules();
			var active = countActiveRules(rules);
			var tags = store.getTags().length;
			var scenarios = store.getScenarios().length;
			var html = ''
				+ '<article class="mk-tre-stat-card"><span class="mk-tre-stat-card__label">Tổng rule</span><strong class="mk-tre-stat-card__value">' + rules.length + '</strong><span class="mk-tre-stat-card__hint">Cấu hình trạng thái KH</span></article>'
				+ '<article class="mk-tre-stat-card mk-tre-stat-card--accent"><span class="mk-tre-stat-card__label">Đang bật</span><strong class="mk-tre-stat-card__value">' + active + '</strong><span class="mk-tre-stat-card__hint">Rule active</span></article>'
				+ '<article class="mk-tre-stat-card"><span class="mk-tre-stat-card__label">Tag</span><strong class="mk-tre-stat-card__value">' + tags + '</strong><span class="mk-tre-stat-card__hint">Nhãn phân loại</span></article>'
				+ '<article class="mk-tre-stat-card"><span class="mk-tre-stat-card__label">Kịch bản</span><strong class="mk-tre-stat-card__value">' + scenarios + '</strong><span class="mk-tre-stat-card__hint">Mẫu tin nhắn</span></article>';
			$('#mk-tre-stats').html(html);
		},

		renderSection: function (title, subtitle, actionHtml, bodyHtml) {
			return ''
				+ '<section class="mk-tre-section">'
				+ '  <div class="mk-tre-section__head">'
				+ '    <div class="mk-tre-section__titles">'
				+ '      <h2 class="mk-tre-section__title">' + title + '</h2>'
				+ (subtitle ? '<p class="mk-tre-section__sub">' + subtitle + '</p>' : '')
				+ '    </div>'
				+ (actionHtml ? '<div class="mk-tre-section__actions">' + actionHtml + '</div>' : '')
				+ '  </div>'
				+ '  <div class="mk-tre-section__body">' + bodyHtml + '</div>'
				+ '</section>';
		},

		renderPanel: function () {
			var $panel = $('#mk-tre-panel');
			if (this.activeTab === 'rules') $panel.html(this.renderRulesTab());
			else if (this.activeTab === 'tags') $panel.html(this.renderTagsTab());
			else $panel.html(this.renderScenariosTab());
		},

		refreshAfterDataChange: function () {
			this.renderStats();
			this.renderPanel();
		},

		renderRulesTableBody: function () {
			var self = this;
			var rules = store.getRules();
			var tags = store.getTags();
			var tagMap = {};
			tags.forEach(function (t) { tagMap[t.id] = t; });
			var q = String(self.ruleSearch || '').trim().toLowerCase();
			var filtered = rules.filter(function (r) {
				return ruleMatchesSearch(r, q, tagMap);
			});

			if (!filtered.length) {
				return '<tr><td colspan="6" class="mk-tre-empty">' + (q ? 'Không tìm thấy rule phù hợp' : 'Chưa có rule nào') + '</td></tr>';
			}

			return filtered.map(function (r) {
				var tagHtml = (r.tag_ids || []).map(function (tid) {
					return tagChip(tagMap[tid] ? tagMap[tid].name : '?');
				}).join('');
				if (!tagHtml) tagHtml = '<span class="mk-tre-muted">(chưa có tag)</span>';
				var thenHtml = esc(r.next_action || '—');
				if (r.require_note) {
					thenHtml += ' <span class="mk-tre-chip mk-tre-chip--warn">Bắt buộc ghi chú</span>';
				}
				if (r.alert_days != null) {
					thenHtml += ' <span class="mk-tre-chip">Cảnh báo ' + esc(r.alert_days) + ' ngày</span>';
				}

				return ''
					+ '<tr>'
					+ '  <td><div class="mk-tre-rule-name">' + esc(r.status_label) + '</div><div class="mk-tre-rule-sub">' + esc(r.name) + '</div></td>'
					+ '  <td><div class="mk-tre-if"><span class="mk-tre-if__label">Nếu</span><div class="mk-tre-chips">' + tagHtml + '</div></div></td>'
					+ '  <td><div class="mk-tre-then"><span class="mk-tre-then__label">Thì</span><div class="mk-tre-then__text">' + thenHtml + '</div></div></td>'
					+ '  <td><span class="mk-tre-priority">' + esc(r.priority) + '</span></td>'
					+ '  <td><label class="mk-tre-switch"><input type="checkbox" class="js-tre-rule-toggle" data-id="' + esc(r.id) + '"' + (r.is_active ? ' checked' : '') + ' /><span class="mk-tre-switch__track"></span></label></td>'
					+ '  <td class="mk-tre-actions">'
					+ '    <button type="button" class="mk-tre-icon-btn js-tre-rule-edit" data-id="' + esc(r.id) + '" title="Sửa">' + ICONS.pencil + '</button>'
					+ '    <button type="button" class="mk-tre-icon-btn mk-tre-icon-btn--danger js-tre-rule-del" data-id="' + esc(r.id) + '" data-name="' + esc(r.name) + '" title="Xoá">' + ICONS.trash + '</button>'
					+ '  </td>'
					+ '</tr>';
			}).join('');
		},

		updateRulesTable: function () {
			$('#mk-tre-panel .js-tre-rules-tbody').html(this.renderRulesTableBody());
		},

		renderRulesTab: function () {
			return ''
				+ '<section class="mk-tre-section">'
				+ '  <div class="mk-tre-section__head">'
				+ '    <div class="mk-tre-section__titles">'
				+ '      <h2 class="mk-tre-section__title">Danh sách rule</h2>'
				+ '    </div>'
				+ '    <div class="mk-tre-section__actions">'
				+ '      <button type="button" class="mk-tre-btn mk-tre-btn--primary mk-tre-btn--lg js-tre-rule-create">' + ICONS.plus + ' Tạo rule</button>'
				+ '    </div>'
				+ '  </div>'
				+ '  <div class="mk-tre-section__filterbar">'
				+ '    <label class="mk-tre-search-field mk-tre-search-field--bar">'
				+ '      <span class="mk-tre-search-field__icon" aria-hidden="true">' + ICONS.search + '</span>'
				+ '      <input type="search" class="mk-tre-input mk-tre-input--search js-tre-rule-search" placeholder="Tìm rule theo tên, trạng thái, tag, hành động…" value="' + esc(this.ruleSearch) + '" autocomplete="off" />'
				+ '    </label>'
				+ '  </div>'
				+ '  <div class="mk-tre-section__body">'
				+ '    <div class="mk-tre-table-wrap"><table class="mk-tre-table mk-tre-table--rules">'
				+ '<thead><tr>'
				+ '<th>Trạng thái</th><th>Nếu (tag AND)</th><th>Thì (hành động)</th><th>Priority</th><th>Active</th><th class="mk-tre-th-actions">Thao tác</th>'
				+ '</tr></thead>'
				+ '<tbody class="js-tre-rules-tbody">' + this.renderRulesTableBody() + '</tbody>'
				+ '</table></div>'
				+ '  </div>'
				+ '</section>';
		},

		renderTagsTableBody: function () {
			var self = this;
			var tags = store.getTags().filter(function (t) {
				return !self.tagSearch || t.name.toLowerCase().indexOf(self.tagSearch.toLowerCase()) >= 0;
			});
			if (!tags.length) {
				return '<tr><td colspan="5" class="mk-tre-empty">Không có tag phù hợp</td></tr>';
			}
			return tags.map(function (t) {
				var used = store.getTagUsageCount(t.id);
				return ''
					+ '<tr>'
					+ '  <td class="mk-tre-strong">' + esc(t.name) + '</td>'
					+ '  <td class="mk-tre-muted">' + esc(t.category || '—') + '</td>'
					+ '  <td class="mk-tre-muted">' + esc(t.description || '—') + '</td>'
					+ '  <td>' + used + '</td>'
					+ '  <td class="mk-tre-actions">'
					+ '    <button type="button" class="mk-tre-icon-btn js-tre-tag-edit" data-id="' + esc(t.id) + '" title="Sửa">' + ICONS.pencil + '</button>'
					+ '    <button type="button" class="mk-tre-icon-btn mk-tre-icon-btn--danger js-tre-tag-del" data-id="' + esc(t.id) + '" data-name="' + esc(t.name) + '" data-used="' + used + '" title="Xoá">' + ICONS.trash + '</button>'
					+ '  </td>'
					+ '</tr>';
			}).join('');
		},

		updateTagsTable: function () {
			$('#mk-tre-panel .js-tre-tags-tbody').html(this.renderTagsTableBody());
		},

		renderTagsTab: function () {
			return ''
				+ '<section class="mk-tre-section">'
				+ '  <div class="mk-tre-section__head">'
				+ '    <div class="mk-tre-section__titles">'
				+ '      <h2 class="mk-tre-section__title">Danh sách tag</h2>'
				+ '      <p class="mk-tre-section__sub">Tag dùng làm điều kiện trong rule. Cột “Dùng ở rule” cho biết số rule đang tham chiếu.</p>'
				+ '    </div>'
				+ '    <div class="mk-tre-section__actions">'
				+ '      <button type="button" class="mk-tre-btn mk-tre-btn--primary mk-tre-btn--lg js-tre-tag-create">' + ICONS.plus + ' Tạo tag</button>'
				+ '    </div>'
				+ '  </div>'
				+ '  <div class="mk-tre-section__filterbar">'
				+ '    <label class="mk-tre-search-field mk-tre-search-field--bar">'
				+ '      <span class="mk-tre-search-field__icon" aria-hidden="true">' + ICONS.search + '</span>'
				+ '      <input type="search" class="mk-tre-input mk-tre-input--search js-tre-tag-search" placeholder="Tìm tag theo tên..." value="' + esc(this.tagSearch) + '" autocomplete="off" />'
				+ '    </label>'
				+ '  </div>'
				+ '  <div class="mk-tre-section__body">'
				+ '    <div class="mk-tre-table-wrap"><table class="mk-tre-table">'
				+ '<colgroup><col class="mk-tre-col-name" /><col class="mk-tre-col-group" /><col class="mk-tre-col-desc" /><col class="mk-tre-col-used" /><col class="mk-tre-col-act" /></colgroup>'
				+ '<thead><tr><th>Tên</th><th>Nhóm</th><th>Mô tả</th><th>Dùng ở rule</th><th class="mk-tre-th-actions">Thao tác</th></tr></thead>'
				+ '<tbody class="js-tre-tags-tbody">' + this.renderTagsTableBody() + '</tbody>'
				+ '</table></div>'
				+ '  </div>'
				+ '</section>';
		},

		renderScenariosTab: function () {
			var scenarios = store.getScenarios();
			var cards = scenarios.map(function (s) {
				var badges = '';
				if (s.channel) badges += tagChip(s.channel, 'primary');
				if (s.owner) badges += tagChip(s.owner, 'muted');
				return ''
					+ '<div class="mk-tre-scenario-card">'
					+ '  <div class="mk-tre-scenario-head">'
					+ '    <div><div class="mk-tre-scenario-title">' + esc(s.title) + '</div><div class="mk-tre-chips">' + badges + '</div></div>'
					+ '    <div class="mk-tre-actions">'
					+ '      <button type="button" class="mk-tre-icon-btn js-tre-sc-copy" data-id="' + esc(s.id) + '" title="Copy">' + ICONS.copy + '</button>'
					+ '      <button type="button" class="mk-tre-icon-btn js-tre-sc-edit" data-id="' + esc(s.id) + '" title="Sửa">' + ICONS.pencil + '</button>'
					+ '      <button type="button" class="mk-tre-icon-btn mk-tre-icon-btn--danger js-tre-sc-del" data-id="' + esc(s.id) + '" data-title="' + esc(s.title) + '" title="Xoá">' + ICONS.trash + '</button>'
					+ '    </div>'
					+ '  </div>'
					+ (s.description ? '<p class="mk-tre-scenario-desc">' + esc(s.description) + '</p>' : '')
					+ '  <div class="mk-tre-scenario-body" lang="vi">' + esc(s.content) + '</div>'
					+ '</div>';
			}).join('');

			if (!cards) {
				cards = '<div class="mk-tre-scenario-empty">Chưa có kịch bản nào</div>';
			}

			return this.renderSection(
				'Thư viện kịch bản',
				'Mẫu tin nhắn theo kênh và người phụ trách — copy nhanh khi chăm sóc khách.',
				'<button type="button" class="mk-tre-btn mk-tre-btn--primary mk-tre-btn--lg js-tre-sc-create">' + ICONS.plus + ' Tạo kịch bản</button>',
				'<div class="mk-tre-scenario-grid">' + cards + '</div>'
			);
		},

		openModal: function (title, bodyHtml, footHtml, opts) {
			var $modal = $('#mk-tre-modal');
			var $dialog = $modal.find('.mk-tre-modal');
			opts = opts || {};
			$dialog.removeClass('mk-tre-modal--rule mk-tre-modal--wide mk-tre-modal--form');
			if (opts.variant) {
				$dialog.addClass('mk-tre-modal--' + opts.variant);
			}
			$('#mk-tre-modal-title').text(title);
			$('#mk-tre-modal-body').html(bodyHtml);
			$('#mk-tre-modal-foot').html(footHtml || '');
			$modal.prop('hidden', false);
			$dialog.css('animation', 'none');
			window.requestAnimationFrame(function () {
				$dialog.css('animation', '');
			});
		},

		closeModal: function () {
			var $modal = $('#mk-tre-modal');
			$modal.prop('hidden', true);
			$modal.find('.mk-tre-modal').removeClass('mk-tre-modal--rule mk-tre-modal--wide mk-tre-modal--form');
		},

		openRuleForm: function (ruleId) {
			var isEdit = !!ruleId;
			var rule = isEdit ? store.getRuleById(ruleId) : {
				status_label: '', name: '', tag_ids: [], priority: 50, is_active: true,
				alert_days: 3, next_action: '', require_note: false, scenario_id: ''
			};
			var tags = store.getTags();
			var scenarios = store.getScenarios();
			var selected = rule.tag_ids || [];
			var byCat = {};
			var catOrder = [];
			tags.forEach(function (t) {
				var cat = (t.category && String(t.category).trim()) || 'Khác';
				if (!byCat[cat]) {
					byCat[cat] = [];
					catOrder.push(cat);
				}
				byCat[cat].push(t);
			});
			var tagGroups = catOrder.map(function (cat) {
				var pills = byCat[cat].map(function (t) {
					var checked = selected.indexOf(t.id) >= 0 ? ' checked' : '';
					return ''
						+ '<label class="mk-tre-tag-pill" data-tag-name="' + esc((t.name || '').toLowerCase()) + '" data-tag-id="' + esc((t.id || '').toLowerCase()) + '">'
						+ '  <input type="checkbox" name="tag_ids" value="' + esc(t.id) + '"' + checked + ' />'
						+ '  <span class="mk-tre-tag-pill__face">' + esc(t.name) + '</span>'
						+ '</label>';
				}).join('');
				return ''
					+ '<div class="mk-tre-tag-group">'
					+ '  <div class="mk-tre-tag-group__label">' + esc(cat) + '</div>'
					+ '  <div class="mk-tre-tag-group__pills">' + pills + '</div>'
					+ '</div>';
			}).join('');

			var scOpts = '<option value="">— Không gắn kịch bản —</option>' + scenarios.map(function (s) {
				var sel = rule.scenario_id === s.id ? ' selected' : '';
				return '<option value="' + esc(s.id) + '"' + sel + '>' + esc(s.title) + '</option>';
			}).join('');

			var selectedCount = selected.length;
			var body = ''
				+ '<div class="mk-tre-form mk-tre-form--rule">'
				+ '  <div class="mk-tre-flow" aria-hidden="true">'
				+ '    <span class="mk-tre-flow__badge mk-tre-flow__badge--if">Nếu</span>'
				+ '    <span class="mk-tre-flow__line"></span>'
				+ '    <span class="mk-tre-flow__badge mk-tre-flow__badge--then">Thì</span>'
				+ '    <p class="mk-tre-flow__text">Đủ tag (AND) → trạng thái + hành động / kịch bản</p>'
				+ '  </div>'

				+ '  <section class="mk-tre-form-block">'
				+ '    <header class="mk-tre-form-block__head">'
				+ '      <span class="mk-tre-form-block__step">01</span>'
				+ '      <div><h3 class="mk-tre-form-block__title">Kết quả khi khớp</h3>'
				+ '      <p class="mk-tre-form-block__sub">Nhãn trạng thái, hành động và kịch bản áp dụng cho lead</p></div>'
				+ '    </header>'
				+ '    <div class="mk-tre-form-block__body">'
				+ '      <div class="mk-tre-form-row">'
				+ '        <label class="mk-tre-field"><span>Mã / tên rule</span><input class="mk-tre-input" name="name" value="' + esc(rule.name) + '" placeholder="VD: R03 Miss call" autocomplete="off" /></label>'
				+ '        <label class="mk-tre-field"><span>Trạng thái khi khớp</span><input class="mk-tre-input" name="status_label" value="' + esc(rule.status_label) + '" placeholder="VD: Không nghe máy" autocomplete="off" /></label>'
				+ '      </div>'
				+ '      <label class="mk-tre-field"><span>Hành động tiếp theo</span><input class="mk-tre-input" name="next_action" value="' + esc(rule.next_action || '') + '" placeholder="VD: Nhắn Zalo + gọi lại trong 24h" autocomplete="off" /></label>'
				+ '      <label class="mk-tre-field"><span>Gắn kịch bản</span><select class="mk-tre-input mk-tre-input--select" name="scenario_id">' + scOpts + '</select></label>'
				+ '      <div class="mk-tre-form-row">'
				+ '        <label class="mk-tre-field"><span>Priority</span><input class="mk-tre-input" type="number" name="priority" value="' + esc(rule.priority) + '" min="1" /></label>'
				+ '        <label class="mk-tre-field"><span>Cảnh báo sau (ngày)</span><input class="mk-tre-input" type="number" name="alert_days" value="' + esc(rule.alert_days == null ? '' : rule.alert_days) + '" min="0" placeholder="Trống = không cảnh báo" /></label>'
				+ '      </div>'
				+ '    </div>'
				+ '  </section>'

				+ '  <section class="mk-tre-form-block mk-tre-form-block--tags">'
				+ '    <header class="mk-tre-form-block__head">'
				+ '      <span class="mk-tre-form-block__step">02</span>'
				+ '      <div><h3 class="mk-tre-form-block__title">Điều kiện tag</h3>'
				+ '      <p class="mk-tre-form-block__sub">Lead phải có <strong>đủ</strong> các tag đã chọn (AND)</p></div>'
				+ '      <span class="mk-tre-tag-count js-tre-tag-count">' + selectedCount + ' đã chọn</span>'
				+ '    </header>'
				+ '    <div class="mk-tre-form-block__body">'
				+ '      <div class="mk-tre-tag-search">'
				+ '        <svg class="mk-tre-tag-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
				+ '        <input type="search" class="mk-tre-input mk-tre-input--search-inline js-tre-tag-filter" placeholder="Tìm tag theo tên…" autocomplete="off" />'
				+ '      </div>'
				+ '      <div class="mk-tre-tag-picker">' + (tagGroups || '<span class="mk-tre-muted">Chưa có tag trong catalogue.</span>') + '</div>'
				+ '      <p class="mk-tre-tag-empty js-tre-tag-empty" hidden>Không tìm thấy tag phù hợp.</p>'
				+ '    </div>'
				+ '  </section>'

				+ '  <section class="mk-tre-form-block mk-tre-form-block--opts">'
				+ '    <header class="mk-tre-form-block__head">'
				+ '      <span class="mk-tre-form-block__step">03</span>'
				+ '      <div><h3 class="mk-tre-form-block__title">Tuỳ chọn</h3>'
				+ '      <p class="mk-tre-form-block__sub">Bật/tắt rule và yêu cầu ghi chú</p></div>'
				+ '    </header>'
				+ '    <div class="mk-tre-form-block__body mk-tre-form-block__body--opts">'
				+ '      <label class="mk-tre-opt">'
				+ '        <span class="mk-tre-opt__text"><strong>Active</strong><small>Rule được dùng khi khớp lead</small></span>'
				+ '        <span class="mk-tre-switch"><input type="checkbox" name="is_active"' + (rule.is_active !== false ? ' checked' : '') + ' /><span class="mk-tre-switch__track"></span></span>'
				+ '      </label>'
				+ '      <label class="mk-tre-opt">'
				+ '        <span class="mk-tre-opt__text"><strong>Bắt buộc ghi chú</strong><small>Nhánh xấu — yêu cầu lý do khi áp dụng</small></span>'
				+ '        <span class="mk-tre-switch"><input type="checkbox" name="require_note"' + (rule.require_note ? ' checked' : '') + ' /><span class="mk-tre-switch__track"></span></span>'
				+ '      </label>'
				+ '    </div>'
				+ '  </section>'
				+ '</div>';

			var foot = ''
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-modal-cancel">Huỷ</button>'
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--primary mk-tre-btn--lg js-tre-rule-save" data-id="' + esc(ruleId || '') + '">'
				+ (isEdit ? 'Cập nhật rule' : 'Tạo rule')
				+ '</button>';

			this.openModal(isEdit ? 'Sửa rule' : 'Tạo rule mới', body, foot, { variant: 'rule' });
		},

		openTagForm: function (tagId) {
			var isEdit = !!tagId;
			var tag = isEdit ? store.getTagById(tagId) : { name: '', category: '', description: '' };
			var categoryField = buildPickOrNewSelect('category', getTagCategories(), tag.category || '', '— Chọn nhóm —');
			var body = ''
				+ '<div class="mk-tre-form mk-tre-form--modal">'
				+ '  <label class="mk-tre-field"><span>Tên tag</span><input class="mk-tre-input" name="name" value="' + esc(tag.name) + '" autocomplete="off" /></label>'
				+ '  <div class="mk-tre-field"><span>Nhóm</span>' + categoryField + '</div>'
				+ '  <label class="mk-tre-field"><span>Mô tả</span><input class="mk-tre-input" name="description" value="' + esc(tag.description || '') + '" autocomplete="off" /></label>'
				+ '</div>';
			var foot = ''
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-modal-cancel">Huỷ</button>'
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--primary js-tre-tag-save" data-id="' + esc(tagId || '') + '">Lưu</button>';
			this.openModal(isEdit ? 'Sửa tag' : 'Tạo tag mới', body, foot, { variant: 'form' });
		},

		openScenarioForm: function (scId) {
			var isEdit = !!scId;
			var sc = isEdit ? store.getScenarios().filter(function (s) { return s.id === scId; })[0] : { title: '', description: '', channel: '', owner: '', content: '' };
			if (!sc) sc = { title: '', description: '', channel: '', owner: '', content: '' };
			var body = ''
				+ '<div class="mk-tre-form mk-tre-form--modal">'
				+ '  <label class="mk-tre-field"><span>Tiêu đề</span><input class="mk-tre-input" name="title" value="' + esc(sc.title) + '" autocomplete="off" /></label>'
				+ '  <label class="mk-tre-field"><span>Mô tả ngắn</span><input class="mk-tre-input" name="description" value="' + esc(sc.description || '') + '" autocomplete="off" /></label>'
				+ '  <div class="mk-tre-form-row">'
				+ '    <div class="mk-tre-field"><span>Kênh</span>' + buildPickOrNewSelect('channel', getScenarioChannels(), sc.channel || '', '— Chọn kênh —') + '</div>'
				+ '    <div class="mk-tre-field"><span>Người phụ trách</span>' + buildPickOrNewSelect('owner', getScenarioOwners(), sc.owner || '', '— Chọn người phụ trách —') + '</div>'
				+ '  </div>'
				+ '  <label class="mk-tre-field"><span>Nội dung / mẫu tin nhắn</span><textarea class="mk-tre-textarea" name="content" rows="10" lang="vi">' + esc(sc.content) + '</textarea></label>'
				+ '</div>';
			var foot = ''
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-modal-cancel">Huỷ</button>'
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--primary js-tre-sc-save" data-id="' + esc(scId || '') + '">Lưu</button>';
			this.openModal(isEdit ? 'Sửa kịch bản' : 'Tạo kịch bản mới', body, foot, { variant: 'form' });
		},

		readForm: function () {
			var data = {};
			$('#mk-tre-modal-body').find('input, textarea, select').each(function () {
				var $el = $(this);
				var name = $el.attr('name');
				if (!name) return;
				if ($el.attr('type') === 'checkbox' && name === 'tag_ids') {
					if (!data.tag_ids) data.tag_ids = [];
					if ($el.prop('checked')) data.tag_ids.push($el.val());
					return;
				}
				if ($el.attr('type') === 'checkbox') {
					data[name] = $el.prop('checked');
					return;
				}
				data[name] = $el.val();
			});
			if (data.category_select !== undefined) {
				data.category = resolvePickOrNew(data, 'category');
				delete data.category_select;
				delete data.category_new;
			}
			if (data.channel_select !== undefined) {
				data.channel = resolvePickOrNew(data, 'channel');
				delete data.channel_select;
				delete data.channel_new;
			}
			if (data.owner_select !== undefined) {
				data.owner = resolvePickOrNew(data, 'owner');
				delete data.owner_select;
				delete data.owner_new;
			}
			return data;
		},

		bindEvents: function () {
			var self = this;

			this.$root.on('click', '.mk-tre-tab', function () {
				self.activeTab = $(this).data('tab');
				self.$root.find('.mk-tre-tab').removeClass('is-active');
				$(this).addClass('is-active');
				self.renderPanel();
			});

			this.$root.on('input', '.js-tre-tag-search', function () {
				self.tagSearch = $(this).val();
				if (self.activeTab === 'tags') {
					self.updateTagsTable();
				}
			});

			this.$root.on('input', '.js-tre-rule-search', function () {
				self.ruleSearch = $(this).val();
				if (self.activeTab === 'rules') {
					self.updateRulesTable();
				}
			});

			this.$root.on('change', '.js-tre-rule-toggle', function () {
				try {
					store.setRuleActive($(this).data('id'), $(this).prop('checked'));
				} catch (e) {
					window.alert(e.message || 'Không cập nhật được trạng thái rule');
					$(this).prop('checked', !$(this).prop('checked'));
				}
			});

			this.$root.on('click', '.js-tre-rule-create', function () { self.openRuleForm(null); });
			this.$root.on('click', '.js-tre-rule-edit', function () { self.openRuleForm($(this).data('id')); });
			this.$root.on('click', '.js-tre-rule-del', function () {
				var name = $(this).data('name');
				if (!window.confirm('Xoá rule "' + name + '"?')) return;
				try {
					store.deleteRule($(this).data('id'));
					self.refreshAfterDataChange();
					toast('Đã xoá rule');
				} catch (e) {
					window.alert(e.message || 'Không xoá được rule');
				}
			});

			this.$root.on('click', '.js-tre-tag-create', function () { self.openTagForm(null); });
			this.$root.on('click', '.js-tre-tag-edit', function () { self.openTagForm($(this).data('id')); });
			this.$root.on('click', '.js-tre-tag-del', function () {
				var used = parseInt($(this).data('used'), 10) || 0;
				var name = $(this).data('name');
				if (used > 0 && !window.confirm('Tag đang dùng trong ' + used + ' rule. Vẫn xoá?')) return;
				if (!window.confirm('Xoá tag "' + name + '"?')) return;
				try {
					store.deleteTag($(this).data('id'));
					self.refreshAfterDataChange();
					toast('Đã xoá tag');
				} catch (e) {
					window.alert(e.message || 'Không xoá được tag');
				}
			});

			this.$root.on('click', '.js-tre-sc-create', function () { self.openScenarioForm(null); });
			this.$root.on('click', '.js-tre-sc-edit', function () { self.openScenarioForm($(this).data('id')); });
			this.$root.on('click', '.js-tre-sc-del', function () {
				if (!window.confirm('Xoá "' + $(this).data('title') + '"?')) return;
				try {
					store.deleteScenario($(this).data('id'));
					self.refreshAfterDataChange();
					toast('Đã xoá');
				} catch (e) {
					window.alert(e.message || 'Không xoá được kịch bản');
				}
			});
			this.$root.on('click', '.js-tre-sc-copy', function () {
				var id = $(this).data('id');
				var sc = store.getScenarios().filter(function (s) { return s.id === id; })[0];
				var text = sc ? sc.content : '';
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function () { toast('Đã copy'); });
				} else {
					window.prompt('Copy:', text);
				}
			});

			$(document).on('click.mkTagRuleEngine', '#mk-tre-modal .js-tre-modal-cancel, #mk-tre-modal .mk-tre-modal__close', function () {
				self.closeModal();
			});
			$(document).on('click.mkTagRuleEngine', '#mk-tre-modal', function (e) {
				if (e.target === this) self.closeModal();
			});

			function refreshTagPickerUI() {
				var $body = $('#mk-tre-modal-body');
				var q = String($body.find('.js-tre-tag-filter').val() || '').trim().toLowerCase();
				var visible = 0;
				$body.find('.mk-tre-tag-pill').each(function () {
					var $pill = $(this);
					var name = String($pill.data('tag-name') || '');
					var id = String($pill.data('tag-id') || '');
					var show = !q || name.indexOf(q) >= 0 || id.indexOf(q) >= 0;
					$pill.toggle(show);
					if (show) visible += 1;
				});
				$body.find('.mk-tre-tag-group').each(function () {
					var $g = $(this);
					$g.toggle($g.find('.mk-tre-tag-pill:visible').length > 0);
				});
				$body.find('.js-tre-tag-empty').prop('hidden', visible > 0 || !q);
				var n = $body.find('input[name="tag_ids"]:checked').length;
				$body.find('.js-tre-tag-count').text(n + ' đã chọn');
			}

			$(document).on('input.mkTagRuleEngine', '#mk-tre-modal .js-tre-tag-filter', refreshTagPickerUI);
			$(document).on('change.mkTagRuleEngine', '#mk-tre-modal input[name="tag_ids"]', refreshTagPickerUI);

			$(document).on('click.mkTagRuleEngine', '#mk-tre-modal .js-tre-pick-trigger', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $wrap = $(this).closest('.mk-tre-pick-dd');
				var open = !$wrap.hasClass('is-open');
				closeAllPickDropdowns(open ? $wrap[0] : null);
				$wrap.toggleClass('is-open', open);
				$wrap.find('.mk-tre-pick-dd__panel').prop('hidden', !open);
				$(this).attr('aria-expanded', open ? 'true' : 'false');
				if (open) {
					var $search = $wrap.find('.js-tre-pick-search');
					$search.val('');
					$search.trigger('input');
					window.setTimeout(function () { $search.focus(); }, 0);
				}
			});

			$(document).on('input.mkTagRuleEngine', '#mk-tre-modal .js-tre-pick-search', function () {
				var q = String($(this).val() || '').trim().toLowerCase();
				var $wrap = $(this).closest('.mk-tre-pick-dd');
				var visible = 0;
				$wrap.find('.mk-tre-pick-dd__option').each(function () {
					var text = String($(this).text() || '').toLowerCase();
					var show = !q || text.indexOf(q) >= 0;
					$(this).toggle(show);
					if (show) visible += 1;
				});
				$wrap.find('.mk-tre-pick-dd__empty').prop('hidden', visible > 0 || !q);
			});

			$(document).on('click.mkTagRuleEngine', '#mk-tre-modal .mk-tre-pick-dd__option', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var val = $(this).attr('data-value');
				if (val === undefined) val = '';
				var $wrap = $(this).closest('.mk-tre-pick-dd');
				var $sel = $wrap.find('.js-tre-pick-select');
				$sel.val(val);
				var label = val === '__new__' ? '＋ Thêm mới…' : (val ? val : $(this).text());
				$wrap.find('.js-tre-pick-trigger').text(label);
				$wrap.find('.mk-tre-pick-dd__option').removeClass('is-selected');
				$(this).addClass('is-selected');
				var isNew = val === '__new__';
				$wrap.find('.js-tre-pick-new-wrap').prop('hidden', !isNew);
				if (!isNew) {
					$wrap.find('.js-tre-pick-new').val('');
				}
				$wrap.removeClass('is-open');
				$wrap.find('.mk-tre-pick-dd__panel').prop('hidden', true);
				$wrap.find('.js-tre-pick-trigger').attr('aria-expanded', 'false');
			});

			$(document).on('click.mkTagRuleEngine', function (e) {
				if (!$(e.target).closest('#mk-tre-modal .mk-tre-pick-dd').length) {
					closeAllPickDropdowns(null);
				}
			});

			$(document).on('keydown.mkTagRuleEngine', '#mk-tre-modal .js-tre-pick-search', function (e) {
				if (e.key === 'Escape') {
					closeAllPickDropdowns(null);
				}
			});

			$(document).on('click.mkTagRuleEngine', '.js-tre-rule-save', function () {
				var id = $(this).data('id');
				var data = self.readForm();
				if (!data.status_label || !data.name) {
					window.alert('Vui lòng nhập trạng thái và tên rule.');
					return;
				}
				try {
					if (id) store.updateRule(id, data);
					else store.createRule(data);
					self.closeModal();
					self.refreshAfterDataChange();
					toast('Đã lưu');
				} catch (e) {
					window.alert(e.message || 'Không lưu được rule');
				}
			});

			$(document).on('click.mkTagRuleEngine', '.js-tre-tag-save', function () {
				var id = $(this).data('id');
				var data = self.readForm();
				if (!data.name) {
					window.alert('Vui lòng nhập tên tag.');
					return;
				}
				try {
					if (id) store.updateTag(id, data);
					else store.createTag(data);
					self.closeModal();
					self.refreshAfterDataChange();
					toast('Đã lưu');
				} catch (e) {
					window.alert(e.message || 'Không lưu được tag');
				}
			});

			$(document).on('click.mkTagRuleEngine', '.js-tre-sc-save', function () {
				var id = $(this).data('id');
				var data = self.readForm();
				if (!data.title || !data.content) {
					window.alert('Vui lòng nhập tiêu đề và nội dung.');
					return;
				}
				try {
					if (id) store.updateScenario(id, data);
					else store.createScenario(data);
					self.closeModal();
					self.refreshAfterDataChange();
					toast('Đã lưu');
				} catch (e) {
					window.alert(e.message || 'Không lưu được kịch bản');
				}
			});
		},
	};

	global.MkTagRuleEngine = MkTagRuleEngine;
}(jQuery, window));
