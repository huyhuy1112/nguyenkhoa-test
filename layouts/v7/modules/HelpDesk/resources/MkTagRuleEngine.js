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

	var MkTagRuleEngine = {
		$root: null,
		activeTab: 'rules',
		tagSearch: '',

		init: function () {
			this.$root = $('#mk-tag-rule-engine');
			if (!this.$root.length) return;
			store.reset();
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
				+ '      <p class="mk-tre-desc">Tất cả rule, tag và kịch bản trong một chỗ — cấu hình nhanh, theo dõi trực quan.</p>'
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

		renderRulesTab: function () {
			var rules = store.getRules();
			var tags = store.getTags();
			var tagMap = {};
			tags.forEach(function (t) { tagMap[t.id] = t; });

			var rows = rules.map(function (r) {
				var tagHtml = (r.tag_ids || []).map(function (tid) {
					return tagChip(tagMap[tid] ? tagMap[tid].name : '?');
				}).join('');
				if (!tagHtml) tagHtml = '<span class="mk-tre-muted">(chưa có tag)</span>';

				return ''
					+ '<tr>'
					+ '  <td><div class="mk-tre-rule-name">' + esc(r.status_label) + '</div><div class="mk-tre-rule-sub">' + esc(r.name) + '</div></td>'
					+ '  <td><div class="mk-tre-chips">' + tagHtml + '</div></td>'
					+ '  <td><span class="mk-tre-priority">' + esc(r.priority) + '</span></td>'
					+ '  <td><label class="mk-tre-switch"><input type="checkbox" class="js-tre-rule-toggle" data-id="' + esc(r.id) + '"' + (r.is_active ? ' checked' : '') + ' /><span class="mk-tre-switch__track"></span></label></td>'
					+ '  <td class="mk-tre-actions">'
					+ '    <button type="button" class="mk-tre-icon-btn js-tre-rule-edit" data-id="' + esc(r.id) + '" title="Sửa">' + ICONS.pencil + '</button>'
					+ '    <button type="button" class="mk-tre-icon-btn mk-tre-icon-btn--danger js-tre-rule-del" data-id="' + esc(r.id) + '" data-name="' + esc(r.name) + '" title="Xoá">' + ICONS.trash + '</button>'
					+ '  </td>'
					+ '</tr>';
			}).join('');

			return this.renderSection(
				'Danh sách rule',
				'Ưu tiên thấp hơn = xử lý trước. Điều kiện tag kết hợp theo AND.',
				'<button type="button" class="mk-tre-btn mk-tre-btn--primary mk-tre-btn--lg js-tre-rule-create">' + ICONS.plus + ' Tạo rule</button>',
				'<div class="mk-tre-table-wrap"><table class="mk-tre-table">'
				+ '<colgroup><col class="mk-tre-col-name" /><col class="mk-tre-col-tags" /><col class="mk-tre-col-pri" /><col class="mk-tre-col-active" /><col class="mk-tre-col-act" /></colgroup>'
				+ '<thead><tr>'
				+ '<th>Rule / Trạng thái</th><th>Điều kiện tag (AND)</th><th>Priority</th><th>Active</th><th class="mk-tre-th-actions">Thao tác</th>'
				+ '</tr></thead>'
				+ '<tbody>' + (rows || '<tr><td colspan="5" class="mk-tre-empty">Chưa có rule nào</td></tr>') + '</tbody>'
				+ '</table></div>'
			);
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

		openModal: function (title, bodyHtml, footHtml) {
			var $modal = $('#mk-tre-modal');
			$('#mk-tre-modal-title').text(title);
			$('#mk-tre-modal-body').html(bodyHtml);
			$('#mk-tre-modal-foot').html(footHtml || '');
			$modal.prop('hidden', false);
			$modal.find('.mk-tre-modal').css('animation', 'none');
			window.requestAnimationFrame(function () {
				$modal.find('.mk-tre-modal').css('animation', '');
			});
		},

		closeModal: function () {
			$('#mk-tre-modal').prop('hidden', true);
		},

		openRuleForm: function (ruleId) {
			var isEdit = !!ruleId;
			var rule = isEdit ? store.getRuleById(ruleId) : { status_label: '', name: '', tag_ids: [], priority: 10, is_active: true };
			var tags = store.getTags();
			var tagChecks = tags.map(function (t) {
				var checked = (rule.tag_ids || []).indexOf(t.id) >= 0 ? ' checked' : '';
				return '<label class="mk-tre-check"><input type="checkbox" name="tag_ids" value="' + esc(t.id) + '"' + checked + ' /> ' + esc(t.name) + '</label>';
			}).join('');

			var body = ''
				+ '<div class="mk-tre-form">'
				+ '  <label class="mk-tre-field"><span>Trạng thái / nhãn</span><input class="mk-tre-input" name="status_label" value="' + esc(rule.status_label) + '" /></label>'
				+ '  <label class="mk-tre-field"><span>Tên rule (mô tả ngắn)</span><input class="mk-tre-input" name="name" value="' + esc(rule.name) + '" /></label>'
				+ '  <label class="mk-tre-field"><span>Priority</span><input class="mk-tre-input" type="number" name="priority" value="' + esc(rule.priority) + '" /></label>'
				+ '  <fieldset class="mk-tre-field"><legend>Điều kiện tag (AND)</legend><div class="mk-tre-checks">' + (tagChecks || '<span class="mk-tre-muted">Chưa có tag — tạo tag trước.</span>') + '</div></fieldset>'
				+ '  <label class="mk-tre-check mk-tre-check--inline"><input type="checkbox" name="is_active"' + (rule.is_active !== false ? ' checked' : '') + ' /> Active</label>'
				+ '</div>';

			var foot = ''
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-modal-cancel">Huỷ</button>'
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--primary js-tre-rule-save" data-id="' + esc(ruleId || '') + '">Lưu</button>';

			this.openModal(isEdit ? 'Sửa rule' : 'Tạo rule mới', body, foot);
		},

		openTagForm: function (tagId) {
			var isEdit = !!tagId;
			var tag = isEdit ? store.getTagById(tagId) : { name: '', category: '', description: '' };
			var body = ''
				+ '<div class="mk-tre-form">'
				+ '  <label class="mk-tre-field"><span>Tên tag</span><input class="mk-tre-input" name="name" value="' + esc(tag.name) + '" /></label>'
				+ '  <label class="mk-tre-field"><span>Nhóm</span><input class="mk-tre-input" name="category" value="' + esc(tag.category || '') + '" placeholder="VD: Trạng thái học" /></label>'
				+ '  <label class="mk-tre-field"><span>Mô tả</span><input class="mk-tre-input" name="description" value="' + esc(tag.description || '') + '" /></label>'
				+ '</div>';
			var foot = ''
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-modal-cancel">Huỷ</button>'
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--primary js-tre-tag-save" data-id="' + esc(tagId || '') + '">Lưu</button>';
			this.openModal(isEdit ? 'Sửa tag' : 'Tạo tag mới', body, foot);
		},

		openScenarioForm: function (scId) {
			var isEdit = !!scId;
			var sc = isEdit ? store.getScenarios().filter(function (s) { return s.id === scId; })[0] : { title: '', description: '', channel: '', owner: '', content: '' };
			if (!sc) sc = { title: '', description: '', channel: '', owner: '', content: '' };
			var body = ''
				+ '<div class="mk-tre-form">'
				+ '  <label class="mk-tre-field"><span>Tiêu đề</span><input class="mk-tre-input" name="title" value="' + esc(sc.title) + '" /></label>'
				+ '  <label class="mk-tre-field"><span>Mô tả ngắn</span><input class="mk-tre-input" name="description" value="' + esc(sc.description || '') + '" /></label>'
				+ '  <div class="mk-tre-form-row">'
				+ '    <label class="mk-tre-field"><span>Kênh</span><input class="mk-tre-input" name="channel" value="' + esc(sc.channel || '') + '" placeholder="Zalo / Điện thoại" /></label>'
				+ '    <label class="mk-tre-field"><span>Người phụ trách</span><input class="mk-tre-input" name="owner" value="' + esc(sc.owner || '') + '" placeholder="Sale / CSKH" /></label>'
				+ '  </div>'
				+ '  <label class="mk-tre-field"><span>Nội dung / mẫu tin nhắn</span><textarea class="mk-tre-textarea" name="content" rows="10" lang="vi">' + esc(sc.content) + '</textarea></label>'
				+ '</div>';
			var foot = ''
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-modal-cancel">Huỷ</button>'
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--primary js-tre-sc-save" data-id="' + esc(scId || '') + '">Lưu</button>';
			this.openModal(isEdit ? 'Sửa kịch bản' : 'Tạo kịch bản mới', body, foot);
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

			this.$root.on('change', '.js-tre-rule-toggle', function () {
				store.setRuleActive($(this).data('id'), $(this).prop('checked'));
			});

			this.$root.on('click', '.js-tre-rule-create', function () { self.openRuleForm(null); });
			this.$root.on('click', '.js-tre-rule-edit', function () { self.openRuleForm($(this).data('id')); });
			this.$root.on('click', '.js-tre-rule-del', function () {
				var name = $(this).data('name');
				if (window.confirm('Xoá rule "' + name + '"?')) {
					store.deleteRule($(this).data('id'));
					self.refreshAfterDataChange();
					toast('Đã xoá rule');
				}
			});

			this.$root.on('click', '.js-tre-tag-create', function () { self.openTagForm(null); });
			this.$root.on('click', '.js-tre-tag-edit', function () { self.openTagForm($(this).data('id')); });
			this.$root.on('click', '.js-tre-tag-del', function () {
				var used = parseInt($(this).data('used'), 10) || 0;
				var name = $(this).data('name');
				if (used > 0 && !window.confirm('Tag đang dùng trong ' + used + ' rule. Vẫn xoá?')) return;
				if (!window.confirm('Xoá tag "' + name + '"?')) return;
				store.deleteTag($(this).data('id'));
				self.refreshAfterDataChange();
				toast('Đã xoá tag');
			});

			this.$root.on('click', '.js-tre-sc-create', function () { self.openScenarioForm(null); });
			this.$root.on('click', '.js-tre-sc-edit', function () { self.openScenarioForm($(this).data('id')); });
			this.$root.on('click', '.js-tre-sc-del', function () {
				if (window.confirm('Xoá "' + $(this).data('title') + '"?')) {
					store.deleteScenario($(this).data('id'));
					self.refreshAfterDataChange();
					toast('Đã xoá');
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

			$(document).on('click.mkTagRuleEngine', '.js-tre-rule-save', function () {
				var id = $(this).data('id');
				var data = self.readForm();
				if (!data.status_label || !data.name) {
					window.alert('Vui lòng nhập trạng thái và tên rule.');
					return;
				}
				if (id) store.updateRule(id, data);
				else store.createRule(data);
				self.closeModal();
				self.refreshAfterDataChange();
				toast('Đã lưu');
			});

			$(document).on('click.mkTagRuleEngine', '.js-tre-tag-save', function () {
				var id = $(this).data('id');
				var data = self.readForm();
				if (!data.name) {
					window.alert('Vui lòng nhập tên tag.');
					return;
				}
				if (id) store.updateTag(id, data);
				else store.createTag(data);
				self.closeModal();
				self.refreshAfterDataChange();
				toast('Đã lưu');
			});

			$(document).on('click.mkTagRuleEngine', '.js-tre-sc-save', function () {
				var id = $(this).data('id');
				var data = self.readForm();
				if (!data.title || !data.content) {
					window.alert('Vui lòng nhập tiêu đề và nội dung.');
					return;
				}
				if (id) store.updateScenario(id, data);
				else store.createScenario(data);
				self.closeModal();
				self.refreshAfterDataChange();
				toast('Đã lưu');
			});
		},
	};

	global.MkTagRuleEngine = MkTagRuleEngine;
}(jQuery, window));
