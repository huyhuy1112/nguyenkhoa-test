/**
 * Tag Rule Engine — DB-backed store (HelpDesk TagRulesApi).
 * Bootstrap: window.MK_TAG_RULE_STATE hoặc AJAX mode=bootstrap.
 */
(function (global) {
	'use strict';

	var state = {
		tags: [],
		groups: [],
		rules: [],
		scenarios: [],
		affiliate_tiers: [],
		sheet_scoring: null,
		alerts: [],
		channel_options: [],
		assignee_options: [],
		create_tag_groups: [],
	};
	var ready = false;
	var listeners = [];

	function clone(obj) {
		return JSON.parse(JSON.stringify(obj));
	}

	function applyState(next) {
		if (!next || typeof next !== 'object') return;
		if (Array.isArray(next.tags)) state.tags = next.tags;
		if (Array.isArray(next.groups)) state.groups = next.groups;
		if (Array.isArray(next.rules)) state.rules = next.rules;
		if (Array.isArray(next.scenarios)) state.scenarios = next.scenarios;
		if (Array.isArray(next.affiliate_tiers)) state.affiliate_tiers = next.affiliate_tiers;
		if (next.sheet_scoring && typeof next.sheet_scoring === 'object') state.sheet_scoring = next.sheet_scoring;
		if (Array.isArray(next.alerts)) state.alerts = next.alerts;
		if (Array.isArray(next.channel_options)) state.channel_options = next.channel_options;
		if (Array.isArray(next.assignee_options)) state.assignee_options = next.assignee_options;
		if (Array.isArray(next.create_tag_groups)) state.create_tag_groups = next.create_tag_groups;
		ready = true;
		listeners.forEach(function (cb) {
			try { cb(); } catch (e) { /* ignore */ }
		});
	}

	function unwrap(res) {
		if (!res || typeof res !== 'object') return res;
		if (res.result && typeof res.result === 'object') {
			return Object.assign({ success: true }, res.result);
		}
		return res;
	}

	function apiPost(data) {
		var def = $.Deferred();
		var reqData = Object.assign({ module: 'HelpDesk', action: 'TagRulesApi' }, data);
		var onOk = function (res) {
			var body = unwrap(res);
			if (!body || body.success === false) {
				var msg = (body && body.error) ? body.error : 'Yêu cầu thất bại';
				if (msg && typeof msg === 'object' && msg.message) msg = msg.message;
				def.reject({ message: String(msg || 'Yêu cầu thất bại') });
				return;
			}
			if (body.state) applyState(body.state);
			if (body.alerts) {
				state.alerts = body.alerts;
				ready = true;
			}
			def.resolve(body);
		};
		var onErr = function (err) {
			var msg = err;
			if (msg && typeof msg === 'object') {
				msg = msg.message || msg.statusText || msg.responseText;
			}
			def.reject({ message: String(msg || 'Không kết nối được máy chủ.') });
		};

		if (typeof window !== 'undefined' && window.app && app.request && app.request.post) {
			app.request.post({ data: reqData }).then(function (err, res) {
				if (err) { onErr(err); return; }
				onOk(res);
			});
			return def.promise();
		}

		$.ajax({
			url: 'index.php',
			method: 'POST',
			dataType: 'json',
			data: reqData,
			async: false,
		}).done(onOk).fail(onErr);
		return def.promise();
	}

	function apiSync(data) {
		var result = null;
		var error = null;
		var reqData = Object.assign({ module: 'HelpDesk', action: 'TagRulesApi' }, data);
		$.ajax({
			url: 'index.php',
			method: 'POST',
			dataType: 'json',
			data: reqData,
			async: false,
			success: function (res) {
				var body = unwrap(res);
				if (!body || body.success === false) {
					error = (body && body.error && body.error.message) ? body.error.message : 'Yêu cầu thất bại';
					return;
				}
				if (body.state) applyState(body.state);
				if (body.alerts) state.alerts = body.alerts;
				result = body;
			},
			error: function (xhr) {
				error = (xhr && xhr.responseText) ? xhr.responseText : 'Không kết nối được máy chủ.';
			},
		});
		if (error) {
			throw new Error(String(error));
		}
		return result;
	}

	function ensureBootstrapped() {
		// List.tpl inject MK_TAG_RULE_STATE SAU khi Store.js parse — luôn ưu tiên inject nếu có.
		if (global.MK_TAG_RULE_STATE && typeof global.MK_TAG_RULE_STATE === 'object') {
			applyState(global.MK_TAG_RULE_STATE);
		}
		if (ready && ((state.rules && state.rules.length) || (state.alerts && state.alerts.length) || (state.tags && state.tags.length))) {
			return;
		}
		try {
			apiSync({ mode: 'bootstrap' });
		} catch (e) {
			/* leave empty */
		}
	}

	// Không bootstrap sớm khi parse (STATE chưa inject). Gọi lazy khi get*/init.

	function getTags() {
		ensureBootstrapped();
		return clone(state.tags || []);
	}

	function getGroups() {
		ensureBootstrapped();
		return clone(state.groups || []).sort(function (a, b) {
			return (a.sort_order || 0) - (b.sort_order || 0);
		});
	}

	function getCreateTagGroups() {
		ensureBootstrapped();
		return clone(state.create_tag_groups || []);
	}

	function getGroupById(id) {
		var groups = getGroups();
		for (var i = 0; i < groups.length; i++) {
			if (groups[i].id === id) return clone(groups[i]);
		}
		return null;
	}

	function getRules() {
		ensureBootstrapped();
		return clone(state.rules || []).sort(function (a, b) {
			return (a.priority || 0) - (b.priority || 0);
		});
	}

	function getScenarios() {
		ensureBootstrapped();
		return clone(state.scenarios || []);
	}

	function getAffiliateTiers() {
		ensureBootstrapped();
		return clone(state.affiliate_tiers || []).sort(function (a, b) {
			return String(a.prefix || '').localeCompare(String(b.prefix || ''));
		});
	}

	function getAffiliateTierById(id) {
		if (!id) return null;
		var list = getAffiliateTiers();
		for (var i = 0; i < list.length; i++) {
			if (list[i].id === id) return clone(list[i]);
		}
		return null;
	}

	function getSheetScoring() {
		ensureBootstrapped();
		return clone(state.sheet_scoring || {});
	}

	function getChannelOptions() {
		ensureBootstrapped();
		return clone(state.channel_options || []);
	}

	function getAssigneeOptions() {
		ensureBootstrapped();
		return clone(state.assignee_options || []);
	}

	function getAlertsCached() {
		ensureBootstrapped();
		return clone(state.alerts || []);
	}

	function getTagById(id) {
		var tags = getTags();
		for (var i = 0; i < tags.length; i++) {
			if (tags[i].id === id) return clone(tags[i]);
		}
		return null;
	}

	function getRuleById(id) {
		var rules = getRules();
		for (var i = 0; i < rules.length; i++) {
			if (rules[i].id === id) return clone(rules[i]);
		}
		return null;
	}

	function getScenarioById(id) {
		if (!id) return null;
		var list = getScenarios();
		for (var i = 0; i < list.length; i++) {
			if (list[i].id === id) return clone(list[i]);
		}
		return null;
	}

	function getTagUsageCount(tagId) {
		var n = 0;
		getRules().forEach(function (r) {
			if ((r.tag_ids || []).indexOf(tagId) >= 0) n++;
		});
		return n;
	}

	function matchRules(tagIds, rules) {
		var set = {};
		(tagIds || []).forEach(function (id) { set[id] = true; });
		var matches = [];
		(rules || getRules()).forEach(function (rule) {
			if (!rule.is_active) return;
			var need = rule.tag_ids || [];
			if (!need.length) return;
			var ok = true;
			for (var i = 0; i < need.length; i++) {
				if (!set[need[i]]) { ok = false; break; }
			}
			if (ok) matches.push({ rule: clone(rule) });
		});
		matches.sort(function (a, b) {
			return (a.rule.priority || 0) - (b.rule.priority || 0);
		});
		return { matches: matches };
	}

	function normalizeRulePayload(payload) {
		var alertDays = payload.alert_days;
		if (alertDays === '' || alertDays === undefined || alertDays === null) {
			alertDays = null;
		} else {
			alertDays = parseInt(alertDays, 10);
			if (isNaN(alertDays)) alertDays = null;
		}
		var scenarioId = payload.scenario_id || null;
		if (scenarioId === '') scenarioId = null;
		return {
			status_label: payload.status_label || '',
			name: payload.name || '',
			tag_ids: payload.tag_ids || [],
			priority: parseInt(payload.priority, 10) || 0,
			is_active: payload.is_active !== false,
			alert_days: alertDays,
			next_action: payload.next_action || null,
			require_note: !!payload.require_note,
			scenario_id: scenarioId,
		};
	}

	function createTag(payload) {
		var body = apiSync({ mode: 'save_tag', payload: JSON.stringify(payload || {}) });
		return body && body.tag ? clone(body.tag) : null;
	}

	function updateTag(id, payload) {
		var data = Object.assign({}, payload || {}, { id: id });
		var body = apiSync({ mode: 'save_tag', payload: JSON.stringify(data) });
		return body && body.tag ? clone(body.tag) : null;
	}

	function deleteTag(id) {
		apiSync({ mode: 'delete_tag', id: id });
	}

	function createGroup(payload) {
		var body = apiSync({ mode: 'save_group', payload: JSON.stringify(payload || {}) });
		return body && body.group ? clone(body.group) : null;
	}

	function updateGroup(id, payload) {
		var data = Object.assign({}, payload || {}, { id: id });
		var body = apiSync({ mode: 'save_group', payload: JSON.stringify(data) });
		return body && body.group ? clone(body.group) : null;
	}

	function deleteGroup(id) {
		apiSync({ mode: 'delete_group', id: id });
	}

	function createRule(payload) {
		var body = apiSync({
			mode: 'save_rule',
			payload: JSON.stringify(normalizeRulePayload(payload)),
		});
		return body && body.rule ? clone(body.rule) : null;
	}

	function updateRule(id, payload) {
		var data = Object.assign({}, normalizeRulePayload(payload), { id: id });
		var body = apiSync({ mode: 'save_rule', payload: JSON.stringify(data) });
		return body && body.rule ? clone(body.rule) : null;
	}

	function setRuleActive(id, isActive) {
		var body = apiSync({
			mode: 'set_rule_active',
			id: id,
			is_active: isActive ? 1 : 0,
		});
		return body && body.rule ? clone(body.rule) : null;
	}

	function deleteRule(id) {
		apiSync({ mode: 'delete_rule', id: id });
	}

	function createScenario(payload) {
		var body = apiSync({ mode: 'save_scenario', payload: JSON.stringify(payload || {}) });
		return body && body.scenario ? clone(body.scenario) : null;
	}

	function updateScenario(id, payload) {
		var data = Object.assign({}, payload || {}, { id: id });
		var body = apiSync({ mode: 'save_scenario', payload: JSON.stringify(data) });
		return body && body.scenario ? clone(body.scenario) : null;
	}

	function deleteScenario(id) {
		apiSync({ mode: 'delete_scenario', id: id });
	}

	function createAffiliateTier(payload) {
		var body = apiSync({ mode: 'save_affiliate_tier', payload: JSON.stringify(payload || {}) });
		return body && body.tier ? clone(body.tier) : null;
	}

	function updateAffiliateTier(id, payload) {
		var data = Object.assign({}, payload || {}, { id: id });
		var body = apiSync({ mode: 'save_affiliate_tier', payload: JSON.stringify(data) });
		return body && body.tier ? clone(body.tier) : null;
	}

	function setAffiliateTierActive(id, isActive) {
		var body = apiSync({
			mode: 'set_affiliate_tier_active',
			id: id,
			is_active: isActive ? 1 : 0,
		});
		return body && body.tier ? clone(body.tier) : null;
	}

	function deleteAffiliateTier(id) {
		apiSync({ mode: 'delete_affiliate_tier', id: id });
	}

	function saveSheetScoring(payload) {
		var body = apiSync({ mode: 'save_sheet_scoring', payload: JSON.stringify(payload || {}) });
		if (body && body.sheet_scoring) {
			state.sheet_scoring = body.sheet_scoring;
		}
		return body && body.sheet_scoring ? clone(body.sheet_scoring) : getSheetScoring();
	}

	function resetSheetScoring() {
		var body = apiSync({ mode: 'reset_sheet_scoring' });
		if (body && body.sheet_scoring) {
			state.sheet_scoring = body.sheet_scoring;
		}
		return body && body.sheet_scoring ? clone(body.sheet_scoring) : getSheetScoring();
	}

	function resolveAffiliateReward(code) {
		var body = apiSync({ mode: 'resolve_affiliate', code: code || '' });
		return body && body.tier ? clone(body.tier) : null;
	}

	function reset() {
		apiSync({ mode: 'reseed' });
	}

	function loadAlerts() {
		var body = apiSync({ mode: 'alerts' });
		return body && body.alerts ? clone(body.alerts) : getAlertsCached();
	}

	function dismissAlert(leadId, ruleId, days) {
		var body = apiSync({
			mode: 'dismiss',
			lead_id: leadId,
			rule_id: ruleId,
			days: days == null ? 'done' : days,
		});
		return body && body.alerts ? clone(body.alerts) : getAlertsCached();
	}

	// Compat stubs (cũ localStorage demo customers) — Alerts dùng loadAlerts()
	function getCustomers() { return []; }
	function getCustomerTags() { return []; }
	function getDismissals() { return []; }
	function upsertDismissal(customerId, ruleId, days) {
		return dismissAlert(customerId, ruleId, days);
	}

	global.MkTagRuleEngineStore = {
		ready: function () { return ready; },
		onChange: function (cb) { listeners.push(cb); },
		refresh: function () { return apiSync({ mode: 'bootstrap' }); },
		reset: reset,
		getTags: getTags,
		getGroups: getGroups,
		getCreateTagGroups: getCreateTagGroups,
		getGroupById: getGroupById,
		getRules: getRules,
		getScenarios: getScenarios,
		getAffiliateTiers: getAffiliateTiers,
		getAffiliateTierById: getAffiliateTierById,
		getSheetScoring: getSheetScoring,
		getChannelOptions: getChannelOptions,
		getAssigneeOptions: getAssigneeOptions,
		getCustomers: getCustomers,
		getCustomerTags: getCustomerTags,
		getDismissals: getDismissals,
		getTagById: getTagById,
		getRuleById: getRuleById,
		getScenarioById: getScenarioById,
		getTagUsageCount: getTagUsageCount,
		matchRules: matchRules,
		createTag: createTag,
		updateTag: updateTag,
		deleteTag: deleteTag,
		createGroup: createGroup,
		updateGroup: updateGroup,
		deleteGroup: deleteGroup,
		createRule: createRule,
		updateRule: updateRule,
		setRuleActive: setRuleActive,
		deleteRule: deleteRule,
		createScenario: createScenario,
		updateScenario: updateScenario,
		deleteScenario: deleteScenario,
		createAffiliateTier: createAffiliateTier,
		updateAffiliateTier: updateAffiliateTier,
		setAffiliateTierActive: setAffiliateTierActive,
		deleteAffiliateTier: deleteAffiliateTier,
		saveSheetScoring: saveSheetScoring,
		resetSheetScoring: resetSheetScoring,
		resolveAffiliateReward: resolveAffiliateReward,
		upsertDismissal: upsertDismissal,
		loadAlerts: loadAlerts,
		dismissAlert: dismissAlert,
		getAlerts: getAlertsCached,
		apiPost: apiPost,
	};
}(window));
