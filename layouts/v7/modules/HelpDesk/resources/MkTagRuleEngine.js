/**
 * Tag Rule Engine — Quản lý (Rule / Tag / Kịch bản / Mã giới thiệu)
 * UI matches nguyenkhoa-tst.lovable.app/manage — DB-backed via MkTagRuleEngineStore.
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
		chevron: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
		clock: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
		note: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M15.5 3H5a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V8.5L15.5 3Z"/><path d="M15 3v6h6"/><path d="M10 13h4"/><path d="M10 17h4"/></svg>',
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

	function formatMoneyVnd(n) {
		var num = Math.round(Number(n) || 0);
		return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' ₫';
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

	var CORE_GROUP_IDS = {
		nguyen_lieu: true,
		nhuong_quyen_group: true,
		lop_hoc: true,
	};

	function isProtectedGroup(groupId) {
		return !!CORE_GROUP_IDS[groupId];
	}

	function getGroupChildren(groupId) {
		var group = store.getGroupById ? store.getGroupById(groupId) : null;
		var groupName = group ? String(group.name || '').trim().toLowerCase() : '';
		return store.getTags().filter(function (t) {
			if (t.group_id === groupId) return true;
			if (groupName && String(t.category || '').trim().toLowerCase() === groupName) {
				return !t.group_id;
			}
			return false;
		}).sort(function (a, b) {
			return String(a.name || '').localeCompare(String(b.name || ''), 'vi');
		});
	}

	function getGroupChildCount(groupId) {
		return getGroupChildren(groupId).length;
	}

	function getTagCategories() {
		// Dropdown “Tag cha”: chỉ 3 nhóm chính trên form tạo Lead (+ nhóm đang chọn nếu edit).
		var preferredIds = {
			nguyen_lieu: true,
			nhuong_quyen_group: true,
			lop_hoc: true,
		};
		var preferredOrder = ['Nguyên liệu', 'Nhượng quyền', 'Lớp học'];
		var byName = {};
		(store.getGroups ? store.getGroups() : []).forEach(function (g) {
			if (!g || !g.name) return;
			if (g.show_on_create || preferredIds[g.id]) {
				byName[g.name] = true;
			}
		});
		var list = preferredOrder.filter(function (n) { return byName[n]; });
		Object.keys(byName).forEach(function (n) {
			if (list.indexOf(n) < 0) list.push(n);
		});
		return list;
	}

	function getGroupOptions() {
		var preferredIds = {
			nguyen_lieu: true,
			nhuong_quyen_group: true,
			lop_hoc: true,
		};
		return (store.getGroups ? store.getGroups() : [])
			.filter(function (g) {
				return g.show_on_create || preferredIds[g.id];
			})
			.map(function (g) {
				return { id: g.id, name: g.name, show_on_create: g.show_on_create };
			});
	}

	function resolveGroupLabel(tag) {
		if (tag.group_id && store.getGroupById) {
			var g = store.getGroupById(tag.group_id);
			if (g) return g.name;
		}
		return tag.category || '—';
	}

	function scopeBadges(tag) {
		var parts = [];
		if (tag.scope_lead !== 0) parts.push('Lead');
		if (tag.scope_opp !== 0) parts.push('Opp');
		if (tag.scope_contact !== 0) parts.push('Contact');
		if (!parts.length) return '—';
		return parts.map(function (p) {
			return '<span class="mk-tre-scope-pill">' + esc(p) + '</span>';
		}).join('');
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

	var TAB_IDS = { rules: true, tags: true, scenarios: true, affiliate: true };
	var TAB_STORAGE_KEY = 'mk_tre_active_tab';

	function readPersistedTab() {
		var fromHash = '';
		try {
			var hash = String(window.location.hash || '').replace(/^#/, '');
			if (hash.indexOf('tab=') === 0) {
				fromHash = hash.slice(4);
			} else if (TAB_IDS[hash]) {
				fromHash = hash;
			}
		} catch (e0) { /* ignore */ }
		if (TAB_IDS[fromHash]) return fromHash;

		try {
			var params = new URLSearchParams(window.location.search || '');
			var fromQuery = params.get('tab') || '';
			if (TAB_IDS[fromQuery]) return fromQuery;
		} catch (e1) { /* ignore */ }

		try {
			var fromStore = window.sessionStorage ? sessionStorage.getItem(TAB_STORAGE_KEY) : '';
			if (TAB_IDS[fromStore]) return fromStore;
		} catch (e2) { /* ignore */ }

		return 'rules';
	}

	function persistTab(tab) {
		if (!TAB_IDS[tab]) tab = 'rules';
		try {
			if (window.sessionStorage) sessionStorage.setItem(TAB_STORAGE_KEY, tab);
		} catch (e0) { /* ignore */ }
		try {
			var nextHash = 'tab=' + tab;
			if (String(window.location.hash || '').replace(/^#/, '') !== nextHash) {
				if (window.history && history.replaceState) {
					var url = window.location.pathname + window.location.search + '#' + nextHash;
					history.replaceState(null, '', url);
				} else {
					window.location.hash = nextHash;
				}
			}
		} catch (e1) { /* ignore */ }
	}

	var TAGS_SUBTAB_KEY = 'mk_tre_tags_subtab';
	var TAGS_SUBTAB_IDS = { parent: 1, children: 1 };

	function readPersistedTagsSubTab() {
		try {
			var stored = String(localStorage.getItem(TAGS_SUBTAB_KEY) || '').trim();
			if (TAGS_SUBTAB_IDS[stored]) return stored;
		} catch (e) { /* ignore */ }
		return 'parent';
	}

	function persistTagsSubTab(sub) {
		try {
			localStorage.setItem(TAGS_SUBTAB_KEY, sub);
		} catch (e) { /* ignore */ }
	}

	var MkTagRuleEngine = {
		$root: null,
		activeTab: 'rules',
		activeTagsSubTab: 'parent',
		expandedGroupIds: {},
		ruleSearch: '',
		tagSearch: '',
		affiliateSearch: '',

		init: function () {
			this.$root = $('#mk-tag-rule-engine');
			if (!this.$root.length) return;
			this.activeTab = readPersistedTab();
			this.activeTagsSubTab = readPersistedTagsSubTab();
			persistTab(this.activeTab);
			persistTagsSubTab(this.activeTagsSubTab);
			this.render();
			this.bindEvents();
		},

		setActiveTab: function (tab) {
			if (!TAB_IDS[tab]) tab = 'rules';
			this.activeTab = tab;
			persistTab(tab);
			if (this.$root && this.$root.length) {
				this.$root.find('.mk-tre-tabs > .mk-tre-tab').removeClass('is-active');
				this.$root.find('.mk-tre-tabs > .mk-tre-tab[data-tab="' + tab + '"]').addClass('is-active');
				this.renderStats();
				this.renderPanel();
			}
		},

		setActiveTagsSubTab: function (sub) {
			if (!TAGS_SUBTAB_IDS[sub]) sub = 'parent';
			this.activeTagsSubTab = sub;
			persistTagsSubTab(sub);
			if (this.activeTab !== 'tags') {
				this.setActiveTab('tags');
				return;
			}
			if (this.$root && this.$root.length) {
				this.$root.find('.mk-tre-subtab').removeClass('is-active');
				this.$root.find('.mk-tre-subtab[data-subtab="' + sub + '"]').addClass('is-active');
				this.renderPanel();
			}
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
				+ '      <button type="button" class="mk-tre-tab' + (this.activeTab === 'affiliate' ? ' is-active' : '') + '" data-tab="affiliate">Mã giới thiệu</button>'
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
			if (this.activeTab === 'affiliate') {
				var tiers = store.getAffiliateTiers ? store.getAffiliateTiers() : [];
				var activeTiers = tiers.filter(function (t) {
					return t.is_active !== false && t.status !== 'inactive';
				}).length;
				var htmlAff = ''
					+ '<article class="mk-tre-stat-card"><span class="mk-tre-stat-card__label">Tổng mã</span><strong class="mk-tre-stat-card__value">' + tiers.length + '</strong><span class="mk-tre-stat-card__hint">Prefix cấu hình</span></article>'
					+ '<article class="mk-tre-stat-card mk-tre-stat-card--accent"><span class="mk-tre-stat-card__label">Đang Active</span><strong class="mk-tre-stat-card__value">' + activeTiers + '</strong><span class="mk-tre-stat-card__hint">Áp dụng khi nhập mã</span></article>'
					+ '<article class="mk-tre-stat-card"><span class="mk-tre-stat-card__label">Prefix</span><strong class="mk-tre-stat-card__value">A–Z</strong><span class="mk-tre-stat-card__hint">Ký tự đầu mã GT</span></article>'
					+ '<article class="mk-tre-stat-card"><span class="mk-tre-stat-card__label">Bảo lưu</span><strong class="mk-tre-stat-card__value">ngày</strong><span class="mk-tre-stat-card__hint">Theo từng hạng</span></article>';
				$('#mk-tre-stats').html(htmlAff);
				return;
			}
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
			else if (this.activeTab === 'affiliate') $panel.html(this.renderAffiliateTab());
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

				var thenMeta = '';
				if (r.require_note) {
					thenMeta +=
						'<span class="mk-tre-chip mk-tre-chip--warn">' +
						ICONS.note +
						' Bắt buộc ghi chú</span>';
				}
				if (r.alert_days != null && r.alert_days !== '') {
					thenMeta +=
						'<span class="mk-tre-chip mk-tre-chip--alert">' +
						ICONS.clock +
						' Cảnh báo ' +
						esc(r.alert_days) +
						' ngày</span>';
				}

				return ''
					+ '<tr>'
					+ '  <td><div class="mk-tre-rule-name">' + esc(r.status_label) + '</div><div class="mk-tre-rule-sub">' + esc(r.name) + '</div></td>'
					+ '  <td><div class="mk-tre-if"><span class="mk-tre-if__label">Nếu</span><div class="mk-tre-chips">' + tagHtml + '</div></div></td>'
					+ '  <td><div class="mk-tre-then"><span class="mk-tre-then__label">Thì</span>'
					+ '    <div class="mk-tre-then__text">' + esc(r.next_action || '—') + '</div>'
					+ (thenMeta ? '<div class="mk-tre-then__meta mk-tre-chips">' + thenMeta + '</div>' : '')
					+ '  </div></td>'
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

		renderGroupChildrenDetail: function (groupId) {
			var children = getGroupChildren(groupId);
			if (!children.length) {
				return '<div class="mk-tre-group-detail__empty">Chưa có tag con trong nhóm này</div>';
			}
			var rows = children.map(function (t) {
				var used = store.getTagUsageCount(t.id);
				return ''
					+ '<div class="mk-tre-group-child">'
					+ '  <div class="mk-tre-group-child__main">'
					+ '    <span class="mk-tre-group-child__name">' + esc(t.name) + '</span>'
					+ '    <span class="mk-tre-scope">' + scopeBadges(t) + '</span>'
					+ '  </div>'
					+ '  <div class="mk-tre-group-child__meta">'
					+ '    <span class="mk-tre-muted">' + esc(t.description || 'Không có mô tả') + '</span>'
					+ '    <span class="mk-tre-group-child__used">Rule: <strong>' + used + '</strong></span>'
					+ '  </div>'
					+ '  <div class="mk-tre-group-child__actions">'
					+ '    <button type="button" class="mk-tre-icon-btn js-tre-tag-edit" data-id="' + esc(t.id) + '" title="Sửa tag con">' + ICONS.pencil + '</button>'
					+ '  </div>'
					+ '</div>';
			}).join('');
			return ''
				+ '<div class="mk-tre-group-detail">'
				+ '  <div class="mk-tre-group-detail__head">Danh sách tag con (' + children.length + ')</div>'
				+ '  <div class="mk-tre-group-detail__list">' + rows + '</div>'
				+ '</div>';
		},

		renderGroupsTableBody: function () {
			var self = this;
			var groups = store.getGroups ? store.getGroups() : [];
			if (!groups.length) {
				return '<tr><td colspan="4" class="mk-tre-empty">Chưa có tag cha</td></tr>';
			}
			return groups.map(function (g) {
				var children = getGroupChildren(g.id);
				var childCount = children.length;
				var isOpen = !!self.expandedGroupIds[g.id];
				var onLead = g.show_on_create
					? '<span class="mk-tre-chip mk-tre-chip--primary">Có</span>'
					: '<span class="mk-tre-muted">Không</span>';
				var protectedGroup = isProtectedGroup(g.id);
				var delBtn = protectedGroup
					? ''
					: '    <button type="button" class="mk-tre-icon-btn mk-tre-icon-btn--danger js-tre-group-del" data-id="' + esc(g.id) + '" data-name="' + esc(g.name) + '" data-children="' + childCount + '" title="Xoá tag cha">' + ICONS.trash + '</button>';
				return ''
					+ '<tr class="mk-tre-group-row' + (isOpen ? ' is-open' : '') + '" data-group-id="' + esc(g.id) + '">'
					+ '  <td class="mk-tre-strong">'
					+ '    <button type="button" class="mk-tre-group-toggle js-tre-group-expand" data-id="' + esc(g.id) + '" aria-expanded="' + (isOpen ? 'true' : 'false') + '" title="' + (isOpen ? 'Thu gọn' : 'Xem tag con') + '">'
					+ '      <span class="mk-tre-group-toggle__chevron" aria-hidden="true">' + ICONS.chevron + '</span>'
					+ '      <span class="mk-tre-group-toggle__name">' + esc(g.name) + '</span>'
					+ (protectedGroup ? ' <span class="mk-tre-chip mk-tre-chip--muted">Hệ thống</span>' : '')
					+ '    </button>'
					+ '  </td>'
					+ '  <td>' + onLead + '</td>'
					+ '  <td>'
					+ '    <button type="button" class="mk-tre-group-count js-tre-group-expand" data-id="' + esc(g.id) + '" aria-expanded="' + (isOpen ? 'true' : 'false') + '" title="' + (isOpen ? 'Thu gọn' : 'Xem danh sách tag con') + '">'
					+ '      <strong>' + childCount + '</strong>'
					+ '    </button>'
					+ '  </td>'
					+ '  <td class="mk-tre-actions">'
					+ '    <button type="button" class="mk-tre-icon-btn js-tre-group-edit" data-id="' + esc(g.id) + '" title="Sửa tag cha">' + ICONS.pencil + '</button>'
					+ delBtn
					+ '  </td>'
					+ '</tr>'
					+ '<tr class="mk-tre-group-detail-row' + (isOpen ? ' is-open' : '') + '" data-group-id="' + esc(g.id) + '"' + (isOpen ? '' : ' hidden') + '>'
					+ '  <td colspan="4">' + self.renderGroupChildrenDetail(g.id) + '</td>'
					+ '</tr>';
			}).join('');
		},

		renderGroupsSection: function () {
			return ''
				+ '<section class="mk-tre-section mk-tre-section--groups">'
				+ '  <div class="mk-tre-section__head">'
				+ '    <div class="mk-tre-section__titles">'
				+ '      <h2 class="mk-tre-section__title">Tag cha (nhóm)</h2>'
				+ '      <p class="mk-tre-section__sub">Tag cha quyết định card trên form tạo Lead. Xoá tag con không tự xoá tag cha — dùng nút xoá ở đây để gỡ card khỏi Lead.</p>'
				+ '    </div>'
				+ '    <div class="mk-tre-section__actions">'
				+ '      <button type="button" class="mk-tre-btn mk-tre-btn--primary mk-tre-btn--lg js-tre-group-create">' + ICONS.plus + ' Tạo tag cha</button>'
				+ '    </div>'
				+ '  </div>'
				+ '  <div class="mk-tre-section__body">'
				+ '    <div class="mk-tre-table-wrap"><table class="mk-tre-table">'
				+ '<colgroup><col class="mk-tre-col-name" /><col class="mk-tre-col-lead" /><col class="mk-tre-col-used" /><col class="mk-tre-col-act" /></colgroup>'
				+ '<thead><tr><th>Tên tag cha</th><th>Form tạo Lead</th><th>Tag con</th><th class="mk-tre-th-actions">Thao tác</th></tr></thead>'
				+ '<tbody class="js-tre-groups-tbody">' + this.renderGroupsTableBody() + '</tbody>'
				+ '</table></div>'
				+ '  </div>'
				+ '</section>';
		},

		renderTagsTableBody: function () {
			var self = this;
			var q = String(self.tagSearch || '').toLowerCase();
			var tags = store.getTags().filter(function (t) {
				if (!q) return true;
				var hay = (t.name + ' ' + (t.category || '') + ' ' + resolveGroupLabel(t)).toLowerCase();
				return hay.indexOf(q) >= 0;
			});
			if (!tags.length) {
				return '<tr><td colspan="6" class="mk-tre-empty">Không có tag phù hợp</td></tr>';
			}
			return tags.map(function (t) {
				var used = store.getTagUsageCount(t.id);
				return ''
					+ '<tr>'
					+ '  <td class="mk-tre-strong">' + esc(t.name) + '</td>'
					+ '  <td class="mk-tre-muted">' + esc(resolveGroupLabel(t)) + '</td>'
					+ '  <td class="mk-tre-td-scope"><span class="mk-tre-scope">' + scopeBadges(t) + '</span></td>'
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

		updateGroupsTable: function () {
			$('#mk-tre-panel .js-tre-groups-tbody').html(this.renderGroupsTableBody());
		},

		renderChildrenSection: function () {
			return ''
				+ '<section class="mk-tre-section mk-tre-section--children">'
				+ '  <div class="mk-tre-section__head">'
				+ '    <div class="mk-tre-section__titles">'
				+ '      <h2 class="mk-tre-section__title">Danh sách tag con</h2>'
				+ '      <p class="mk-tre-section__sub">Tag con thuộc tag cha (nhóm). Scope quyết định hiện trên Lead / Opp / Contact.</p>'
				+ '    </div>'
				+ '    <div class="mk-tre-section__actions">'
				+ '      <button type="button" class="mk-tre-btn mk-tre-btn--primary mk-tre-btn--lg js-tre-tag-create">' + ICONS.plus + ' Tạo tag</button>'
				+ '    </div>'
				+ '  </div>'
				+ '  <div class="mk-tre-section__filterbar">'
				+ '    <label class="mk-tre-search-field mk-tre-search-field--bar">'
				+ '      <span class="mk-tre-search-field__icon" aria-hidden="true">' + ICONS.search + '</span>'
				+ '      <input type="search" class="mk-tre-input mk-tre-input--search js-tre-tag-search" placeholder="Tìm tag theo tên hoặc nhóm..." value="' + esc(this.tagSearch) + '" autocomplete="off" />'
				+ '    </label>'
				+ '  </div>'
				+ '  <div class="mk-tre-section__body">'
				+ '    <div class="mk-tre-table-wrap"><table class="mk-tre-table">'
				+ '<colgroup><col class="mk-tre-col-name" /><col class="mk-tre-col-group" /><col class="mk-tre-col-scope" /><col class="mk-tre-col-desc" /><col class="mk-tre-col-used" /><col class="mk-tre-col-act" /></colgroup>'
				+ '<thead><tr><th>Tên</th><th>Tag cha</th><th>Scope</th><th>Mô tả</th><th>Dùng ở rule</th><th class="mk-tre-th-actions">Thao tác</th></tr></thead>'
				+ '<tbody class="js-tre-tags-tbody">' + this.renderTagsTableBody() + '</tbody>'
				+ '</table></div>'
				+ '  </div>'
				+ '</section>';
		},

		renderTagsTab: function () {
			var sub = TAGS_SUBTAB_IDS[this.activeTagsSubTab] ? this.activeTagsSubTab : 'parent';
			this.activeTagsSubTab = sub;
			var body = sub === 'children'
				? this.renderChildrenSection()
				: this.renderGroupsSection();
			return ''
				+ '<div class="mk-tre-subtabs" role="tablist" aria-label="Loại tag">'
				+ '  <button type="button" class="mk-tre-subtab' + (sub === 'parent' ? ' is-active' : '') + '" data-subtab="parent" role="tab" aria-selected="' + (sub === 'parent' ? 'true' : 'false') + '">Tag cha</button>'
				+ '  <button type="button" class="mk-tre-subtab' + (sub === 'children' ? ' is-active' : '') + '" data-subtab="children" role="tab" aria-selected="' + (sub === 'children' ? 'true' : 'false') + '">Tag con</button>'
				+ '</div>'
				+ '<div class="mk-tre-subpanel" data-active-subtab="' + esc(sub) + '">'
				+ body
				+ '</div>';
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

		renderAffiliateTableBody: function () {
			var self = this;
			var tiers = store.getAffiliateTiers ? store.getAffiliateTiers() : [];
			var q = String(self.affiliateSearch || '').trim().toLowerCase();
			var filtered = tiers.filter(function (t) {
				if (!q) return true;
				var hay = [t.prefix, t.tier_name, String(t.reward_amount || ''), String(t.retention_days || '')]
					.join(' ')
					.toLowerCase();
				return hay.indexOf(q) >= 0;
			});
			if (!filtered.length) {
				return '<tr><td colspan="7" class="mk-tre-empty">' + (q ? 'Không tìm thấy mã phù hợp' : 'Chưa có mã giới thiệu nào') + '</td></tr>';
			}
			return filtered.map(function (t) {
				var active = t.is_active !== false && t.status !== 'inactive';
				return ''
					+ '<tr>'
					+ '  <td><strong class="mk-tre-aff-prefix">' + esc(t.prefix) + '</strong></td>'
					+ '  <td>' + esc(t.tier_name) + '</td>'
					+ '  <td>' + esc(formatMoneyVnd(t.reward_amount)) + '</td>'
					+ '  <td>' + esc(t.retention_days) + ' ngày</td>'
					+ '  <td>' + esc(t.effective_from || '—') + '</td>'
					+ '  <td><label class="mk-tre-switch mk-tre-switch--inline" title="Active">'
					+ '    <input type="checkbox" class="js-tre-aff-toggle" data-id="' + esc(t.id) + '"' + (active ? ' checked' : '') + ' />'
					+ '    <span class="mk-tre-switch__track"></span>'
					+ '  </label></td>'
					+ '  <td class="mk-tre-td-actions">'
					+ '    <button type="button" class="mk-tre-icon-btn js-tre-aff-edit" data-id="' + esc(t.id) + '" title="Sửa">' + ICONS.pencil + '</button>'
					+ '    <button type="button" class="mk-tre-icon-btn mk-tre-icon-btn--danger js-tre-aff-del" data-id="' + esc(t.id) + '" data-prefix="' + esc(t.prefix) + '" data-name="' + esc(t.tier_name) + '" title="Xoá">' + ICONS.trash + '</button>'
					+ '  </td>'
					+ '</tr>';
			}).join('');
		},

		updateAffiliateTable: function () {
			$('#mk-tre-panel .js-tre-aff-tbody').html(this.renderAffiliateTableBody());
		},

		renderAffiliateTab: function () {
			return ''
				+ '<section class="mk-tre-section">'
				+ '  <div class="mk-tre-section__head">'
				+ '    <div class="mk-tre-section__titles">'
				+ '      <h2 class="mk-tre-section__title">Chính sách mã giới thiệu</h2>'
				+ '      <p class="mk-tre-section__sub">Prefix (ký tự đầu) → hạng → tiền thưởng. Đổi số tiền hoặc thêm hạng mới chỉ sửa cấu hình.</p>'
				+ '    </div>'
				+ '    <div class="mk-tre-section__actions">'
				+ '      <button type="button" class="mk-tre-btn mk-tre-btn--primary mk-tre-btn--lg js-tre-aff-create">' + ICONS.plus + ' Thêm mã</button>'
				+ '    </div>'
				+ '  </div>'
				+ '  <div class="mk-tre-section__filterbar">'
				+ '    <label class="mk-tre-search-field mk-tre-search-field--bar">'
				+ '      <span class="mk-tre-search-field__icon" aria-hidden="true">' + ICONS.search + '</span>'
				+ '      <input type="search" class="mk-tre-input mk-tre-input--search js-tre-aff-search" placeholder="Tìm theo prefix, hạng, tiền thưởng…" value="' + esc(this.affiliateSearch) + '" autocomplete="off" />'
				+ '    </label>'
				+ '  </div>'
				+ '  <div class="mk-tre-section__body">'
				+ '    <div class="mk-tre-table-wrap"><table class="mk-tre-table">'
				+ '<thead><tr>'
				+ '<th>Prefix</th><th>Tên hạng</th><th>Tiền thưởng</th><th>Bảo lưu</th><th>Ngày áp dụng</th><th>Trạng thái</th><th class="mk-tre-th-actions">Thao tác</th>'
				+ '</tr></thead>'
				+ '<tbody class="js-tre-aff-tbody">' + this.renderAffiliateTableBody() + '</tbody>'
				+ '</table></div>'
				+ '  </div>'
				+ '</section>';
		},

		openAffiliateForm: function (tierId) {
			var isEdit = !!tierId;
			var tier = isEdit && store.getAffiliateTierById
				? store.getAffiliateTierById(tierId)
				: {
					prefix: '',
					tier_name: '',
					reward_amount: '',
					retention_days: 180,
					effective_from: '',
					is_active: true,
					status: 'active',
				};
			if (!tier) tier = { prefix: '', tier_name: '', reward_amount: '', retention_days: 180, effective_from: '', is_active: true };
			var active = tier.is_active !== false && tier.status !== 'inactive';
			var body = ''
				+ '<div class="mk-tre-form">'
				+ '  <div class="mk-tre-form-row">'
				+ '    <label class="mk-tre-field"><span>Prefix (1 chữ A–Z)</span>'
				+ '      <input class="mk-tre-input" name="prefix" maxlength="1" value="' + esc(tier.prefix || '') + '" placeholder="A" ' + (isEdit ? 'readonly' : '') + ' autocomplete="off" style="text-transform:uppercase" />'
				+ '    </label>'
				+ '    <label class="mk-tre-field"><span>Tên hạng</span>'
				+ '      <input class="mk-tre-input" name="tier_name" value="' + esc(tier.tier_name || '') + '" placeholder="Diamond" autocomplete="off" />'
				+ '    </label>'
				+ '  </div>'
				+ '  <div class="mk-tre-form-row">'
				+ '    <label class="mk-tre-field"><span>Tiền thưởng (VNĐ)</span>'
				+ '      <input class="mk-tre-input" type="number" name="reward_amount" min="0" step="1000" value="' + esc(tier.reward_amount == null ? '' : tier.reward_amount) + '" placeholder="30000000" />'
				+ '    </label>'
				+ '    <label class="mk-tre-field"><span>Bảo lưu (ngày)</span>'
				+ '      <input class="mk-tre-input" type="number" name="retention_days" min="1" value="' + esc(tier.retention_days || 180) + '" />'
				+ '    </label>'
				+ '  </div>'
				+ '  <label class="mk-tre-field"><span>Ngày áp dụng</span>'
				+ '    <input class="mk-tre-input" type="date" name="effective_from" value="' + esc(tier.effective_from || '') + '" />'
				+ '  </label>'
				+ '  <label class="mk-tre-opt">'
				+ '    <span class="mk-tre-opt__text"><strong>Active</strong><small>Dùng khi resolve mã giới thiệu</small></span>'
				+ '    <span class="mk-tre-switch"><input type="checkbox" name="is_active"' + (active ? ' checked' : '') + ' /><span class="mk-tre-switch__track"></span></span>'
				+ '  </label>'
				+ '</div>';
			var foot = ''
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-modal-cancel">Huỷ</button>'
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--primary mk-tre-btn--lg js-tre-aff-save" data-id="' + esc(tierId || '') + '">'
				+ (isEdit ? 'Cập nhật' : 'Thêm mã')
				+ '</button>';
			this.openModal(isEdit ? 'Sửa mã giới thiệu' : 'Thêm mã giới thiệu', body, foot, { variant: 'form' });
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
			var groups = getGroupOptions();
			var byGroup = {};
			var groupOrder = [];
			groups.forEach(function (g) {
				byGroup[g.id] = [];
				groupOrder.push({ key: g.id, label: g.name });
			});
			var ungrouped = [];
			tags.forEach(function (t) {
				if (t.group_id && byGroup[t.group_id]) {
					byGroup[t.group_id].push(t);
				} else {
					var cat = (t.category && String(t.category).trim()) || 'Khác';
					var found = null;
					for (var i = 0; i < groupOrder.length; i++) {
						if (groupOrder[i].label === cat) { found = groupOrder[i].key; break; }
					}
					if (found && byGroup[found]) {
						byGroup[found].push(t);
					} else {
						ungrouped.push(t);
					}
				}
			});
			if (ungrouped.length) {
				byGroup.__other__ = ungrouped;
				groupOrder.push({ key: '__other__', label: 'Khác' });
			}
			var tagGroups = groupOrder.map(function (g) {
				var list = byGroup[g.key] || [];
				if (!list.length) return '';
				var pills = list.map(function (t) {
					var checked = selected.indexOf(t.id) >= 0 ? ' checked' : '';
					return ''
						+ '<label class="mk-tre-tag-pill" data-tag-name="' + esc((t.name || '').toLowerCase()) + '" data-tag-id="' + esc((t.id || '').toLowerCase()) + '">'
						+ '  <input type="checkbox" name="tag_ids" value="' + esc(t.id) + '"' + checked + ' />'
						+ '  <span class="mk-tre-tag-pill__face">' + esc(t.name) + '</span>'
						+ '</label>';
				}).join('');
				return ''
					+ '<div class="mk-tre-tag-group">'
					+ '  <div class="mk-tre-tag-group__label">' + esc(g.label) + '</div>'
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
			var tag = isEdit ? store.getTagById(tagId) : {
				name: '', category: '', description: '',
				scope_lead: 1, scope_opp: 1, scope_contact: 1, group_id: ''
			};
			if (!tag) {
				tag = { name: '', category: '', description: '', scope_lead: 1, scope_opp: 1, scope_contact: 1 };
			}
			var groupLabel = resolveGroupLabel(tag);
			if (groupLabel === '—') groupLabel = tag.category || '';
			var catOptions = getTagCategories();
			if (groupLabel && catOptions.indexOf(groupLabel) < 0) {
				catOptions = catOptions.concat([groupLabel]);
			}
			var categoryField = buildPickOrNewSelect('category', catOptions, groupLabel, '— Chọn tag cha —');
			var body = ''
				+ '<div class="mk-tre-form mk-tre-form--modal">'
				+ '  <label class="mk-tre-field"><span>Tên tag</span><input class="mk-tre-input" name="name" value="' + esc(tag.name) + '" autocomplete="off" /></label>'
				+ '  <div class="mk-tre-field"><span>Tag cha (nhóm)</span>' + categoryField + '</div>'
				+ '  <label class="mk-tre-field"><span>Mô tả</span><input class="mk-tre-input" name="description" value="' + esc(tag.description || '') + '" autocomplete="off" /></label>'
				+ '  <div class="mk-tre-field"><span>Hiện trên module</span>'
				+ '    <div class="mk-tre-form-row" style="margin-top:6px">'
				+ '      <label class="mk-tre-opt" style="flex:1"><span class="mk-tre-opt__text"><strong>Lead</strong></span>'
				+ '        <span class="mk-tre-switch"><input type="checkbox" name="scope_lead"' + (tag.scope_lead !== 0 ? ' checked' : '') + ' /><span class="mk-tre-switch__track"></span></span></label>'
				+ '      <label class="mk-tre-opt" style="flex:1"><span class="mk-tre-opt__text"><strong>Opp</strong></span>'
				+ '        <span class="mk-tre-switch"><input type="checkbox" name="scope_opp"' + (tag.scope_opp !== 0 ? ' checked' : '') + ' /><span class="mk-tre-switch__track"></span></span></label>'
				+ '      <label class="mk-tre-opt" style="flex:1"><span class="mk-tre-opt__text"><strong>Contact</strong></span>'
				+ '        <span class="mk-tre-switch"><input type="checkbox" name="scope_contact"' + (tag.scope_contact !== 0 ? ' checked' : '') + ' /><span class="mk-tre-switch__track"></span></span></label>'
				+ '    </div>'
				+ '  </div>'
				+ '  <label class="mk-tre-opt">'
				+ '    <span class="mk-tre-opt__text"><strong>Hiện trên form tạo Lead</strong><small>Khi tạo tag cha mới từ “＋ Thêm mới…”</small></span>'
				+ '    <span class="mk-tre-switch"><input type="checkbox" name="show_on_create" checked /><span class="mk-tre-switch__track"></span></span>'
				+ '  </label>'
				+ '</div>';
			var foot = ''
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-modal-cancel">Huỷ</button>'
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--primary js-tre-tag-save" data-id="' + esc(tagId || '') + '">Lưu</button>';
			this.openModal(isEdit ? 'Sửa tag' : 'Tạo tag mới', body, foot, { variant: 'form' });
		},

		openGroupForm: function (groupId) {
			var isEdit = !!groupId;
			var g = isEdit && store.getGroupById ? store.getGroupById(groupId) : null;
			if (isEdit && !g) {
				return;
			}
			var protectedGroup = isEdit && isProtectedGroup(groupId);
			var body = ''
				+ '<div class="mk-tre-form mk-tre-form--modal">'
				+ '  <label class="mk-tre-field"><span>Tên tag cha</span><input class="mk-tre-input" name="name" value="' + esc(isEdit ? g.name : '') + '" placeholder="VD: Marketing" autocomplete="off"' + (protectedGroup ? ' readonly' : '') + ' /></label>'
				+ '  <label class="mk-tre-opt">'
				+ '    <span class="mk-tre-opt__text"><strong>Hiện trên form tạo Lead</strong><small>Thêm card chọn tag con khi tạo Lead</small></span>'
				+ '    <span class="mk-tre-switch"><input type="checkbox" name="show_on_create"' + ((!isEdit || g.show_on_create) ? ' checked' : '') + (protectedGroup ? ' disabled' : '') + ' /><span class="mk-tre-switch__track"></span></span>'
				+ '  </label>'
				+ (protectedGroup ? '  <p class="mk-tre-muted" style="margin:0">Tag cha hệ thống không thể xoá hoặc tắt trên form Lead.</p>' : '')
				+ '</div>';
			var foot = ''
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--ghost js-tre-modal-cancel">Huỷ</button>'
				+ '<button type="button" class="mk-tre-btn mk-tre-btn--primary js-tre-group-save" data-id="' + esc(groupId || '') + '">' + (isEdit ? 'Cập nhật tag cha' : 'Lưu tag cha') + '</button>';
			this.openModal(isEdit ? 'Sửa tag cha' : 'Tạo tag cha', body, foot, { variant: 'form' });
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
				if (data.category_select === '__new__' && data.category) {
					data.create_group = 1;
					data.new_group = 1;
				}
				delete data.category_select;
				delete data.category_new;
			}
			['scope_lead', 'scope_opp', 'scope_contact', 'show_on_create'].forEach(function (k) {
				if (data[k] === undefined) data[k] = false;
			});
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

			this.$root.on('click', '.mk-tre-tabs > .mk-tre-tab', function () {
				self.setActiveTab($(this).data('tab'));
			});

			this.$root.on('click', '.mk-tre-subtab', function () {
				self.setActiveTagsSubTab($(this).data('subtab'));
			});

			this.$root.on('click', '.js-tre-group-expand', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var id = String($(this).data('id') || '');
				if (!id) return;
				if (self.expandedGroupIds[id]) {
					delete self.expandedGroupIds[id];
				} else {
					self.expandedGroupIds[id] = true;
				}
				self.updateGroupsTable();
			});

			this.$root.on('input', '.js-tre-tag-search', function () {
				self.tagSearch = $(this).val();
				if (self.activeTab === 'tags' && self.activeTagsSubTab === 'children') {
					self.updateTagsTable();
				}
			});

			this.$root.on('input', '.js-tre-rule-search', function () {
				self.ruleSearch = $(this).val();
				if (self.activeTab === 'rules') {
					self.updateRulesTable();
				}
			});

			this.$root.on('input', '.js-tre-aff-search', function () {
				self.affiliateSearch = $(this).val();
				if (self.activeTab === 'affiliate') {
					self.updateAffiliateTable();
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
			this.$root.on('click', '.js-tre-group-create', function () { self.openGroupForm(null); });
			this.$root.on('click', '.js-tre-group-edit', function () { self.openGroupForm($(this).data('id')); });
			this.$root.on('click', '.js-tre-group-del', function () {
				var $btn = $(this);
				var name = $btn.data('name');
				var children = parseInt($btn.data('children'), 10) || 0;
				var msg = 'Xoá tag cha "' + name + '"?';
				if (children > 0) {
					msg += '\n\nCòn ' + children + ' tag con — chúng sẽ bị gỡ khỏi nhóm này.';
				}
				msg += '\n\nCard tương ứng sẽ biến mất khỏi form tạo Lead.';
				if (!window.confirm(msg)) return;
				try {
					if (!store.deleteGroup) throw new Error('Store chưa hỗ trợ xoá tag cha');
					store.deleteGroup($btn.data('id'));
					self.refreshAfterDataChange();
					toast('Đã xoá tag cha');
				} catch (e) {
					window.alert(e.message || 'Không xoá được tag cha');
				}
			});
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

			this.$root.on('click', '.js-tre-aff-create', function () { self.openAffiliateForm(null); });
			this.$root.on('click', '.js-tre-aff-edit', function () { self.openAffiliateForm($(this).data('id')); });
			this.$root.on('click', '.js-tre-aff-del', function () {
				var prefix = $(this).data('prefix');
				var name = $(this).data('name');
				if (!window.confirm('Xoá mã "' + prefix + '" (' + name + ')?')) return;
				try {
					store.deleteAffiliateTier($(this).data('id'));
					self.refreshAfterDataChange();
					toast('Đã xoá mã giới thiệu');
				} catch (e) {
					window.alert(e.message || 'Không xoá được');
				}
			});
			this.$root.on('change', '.js-tre-aff-toggle', function () {
				try {
					store.setAffiliateTierActive($(this).data('id'), $(this).prop('checked'));
					self.renderStats();
				} catch (e) {
					window.alert(e.message || 'Không cập nhật được trạng thái');
					$(this).prop('checked', !$(this).prop('checked'));
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
				if (data.category) {
					var groups = getGroupOptions();
					for (var i = 0; i < groups.length; i++) {
						if (groups[i].name === data.category) {
							data.group_id = groups[i].id;
							break;
						}
					}
					if (!data.group_id) {
						data.create_group = 1;
						data.new_group = 1;
					}
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

			$(document).on('click.mkTagRuleEngine', '.js-tre-group-save', function () {
				var id = $(this).data('id');
				var data = self.readForm();
				if (!data.name) {
					window.alert('Vui lòng nhập tên tag cha.');
					return;
				}
				try {
					if (id) {
						if (!store.updateGroup) throw new Error('Store chưa hỗ trợ sửa tag cha');
						var payload = {
							name: data.name,
							show_on_create: !!data.show_on_create,
						};
						if (isProtectedGroup(id)) {
							payload.show_on_create = true;
						}
						store.updateGroup(id, payload);
						toast('Đã cập nhật tag cha');
					} else {
						if (!store.createGroup) throw new Error('Store chưa hỗ trợ tạo tag cha');
						store.createGroup({
							name: data.name,
							show_on_create: !!data.show_on_create,
							sort_order: 200,
						});
						toast('Đã tạo tag cha');
					}
					self.closeModal();
					self.refreshAfterDataChange();
				} catch (e) {
					window.alert(e.message || 'Không lưu được tag cha');
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

			$(document).on('click.mkTagRuleEngine', '.js-tre-aff-save', function () {
				var id = $(this).data('id');
				var data = self.readForm();
				var prefix = String(data.prefix || '').trim().toUpperCase();
				if (!/^[A-Z]$/.test(prefix)) {
					window.alert('Prefix phải là 1 chữ cái A–Z.');
					return;
				}
				if (!data.tier_name) {
					window.alert('Vui lòng nhập tên hạng.');
					return;
				}
				var payload = {
					prefix: prefix,
					tier_name: data.tier_name,
					reward_amount: parseFloat(data.reward_amount) || 0,
					retention_days: parseInt(data.retention_days, 10) || 180,
					effective_from: data.effective_from || '',
					is_active: !!data.is_active,
				};
				try {
					if (id) store.updateAffiliateTier(id, payload);
					else store.createAffiliateTier(payload);
					self.closeModal();
					self.refreshAfterDataChange();
					toast('Đã lưu mã giới thiệu');
				} catch (e) {
					window.alert(e.message || 'Không lưu được');
				}
			});
		},
	};

	global.MkTagRuleEngine = MkTagRuleEngine;
}(jQuery, window));
