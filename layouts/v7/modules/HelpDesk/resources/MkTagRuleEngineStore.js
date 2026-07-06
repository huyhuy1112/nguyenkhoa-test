/**
 * Tag Rule Engine — in-memory store (resets on page reload).
 * Seed data matches https://nguyenkhoa-tst.lovable.app/manage
 */
(function (global) {
	'use strict';

	function uid(prefix) {
		return prefix + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 7);
	}

	function clone(obj) {
		return JSON.parse(JSON.stringify(obj));
	}

	var SEED_TAGS = [
		{ id: 'tag-1', name: 'Ngừng Tương Tác', category: 'Mức độ tương tác', description: 'Không phản hồi > 7 ngày' },
		{ id: 'tag-2', name: 'Quan Tâm Cao', category: 'Mức độ tương tác', description: 'Chủ động hỏi thông tin nhiều lần' },
		{ id: 'tag-3', name: 'Mới', category: 'Phân loại KH', description: 'Khách hàng mới trong 30 ngày' },
		{ id: 'tag-4', name: 'VIP', category: 'Phân loại KH', description: 'Khách hàng ưu tiên cao' },
		{ id: 'tag-5', name: 'Học Xong Module 1', category: 'Trạng thái học', description: 'Hoàn thành module 1' },
		{ id: 'tag-6', name: 'PCTH', category: 'Trạng thái học', description: 'Đã tham gia phiên chia sẻ thử' },
		{ id: 'tag-7', name: 'Chưa Mua Hàng', category: 'Trạng thái mua hàng', description: 'Chưa hoàn tất đơn hàng' },
		{ id: 'tag-8', name: 'Đã Thanh Toán', category: 'Trạng thái mua hàng', description: 'Đã thanh toán thành công' },
		{ id: 'tag-9', name: 'Đã Tư Vấn', category: 'Trạng thái mua hàng', description: 'Đã được sale tư vấn 1-1' },
		{ id: 'tag-10', name: 'Refund', category: 'Trạng thái mua hàng', description: 'Đã yêu cầu hoàn tiền' },
	];

	var SEED_RULES = [
		{ id: 'rule-1', status_label: 'Refund - cần xử lý gấp', name: 'Yêu cầu hoàn tiền', tag_ids: ['tag-10'], priority: 1, is_active: true },
		{ id: 'rule-2', status_label: 'Cần kích hoạt lại', name: 'Ngừng tương tác lâu', tag_ids: ['tag-1'], priority: 5, is_active: true },
		{ id: 'rule-3', status_label: 'Đã học thử - Chưa mua hàng', name: 'PCTH chưa mua', tag_ids: ['tag-6', 'tag-7'], priority: 10, is_active: true },
		{ id: 'rule-4', status_label: 'Học viên tích cực', name: 'Học viên đang học', tag_ids: ['tag-8', 'tag-5'], priority: 20, is_active: true },
	];

	var SEED_SCENARIOS = [
		{
			id: 'sc-1',
			title: 'Chăm sóc học viên đã thanh toán',
			description: 'Giữ nhiệt sau mua',
			channel: 'Zalo',
			owner: 'CSKH',
			content: 'Chào {Tên}, em rất vui khi thấy anh/chị đã tham gia. Em gửi anh/chị lộ trình học 30 ngày tới và link nhóm hỗ trợ nhé.',
		},
		{
			id: 'sc-2',
			title: 'Kích hoạt khách ngừng tương tác',
			description: 'Reach lại KH lâu không phản hồi',
			channel: 'Điện thoại',
			owner: 'Sale',
			content: 'Chào {Tên}, lâu rồi bên em không nhận được phản hồi từ anh/chị. Không biết anh/chị còn quan tâm chương trình không ạ? Em sẵn sàng hỗ trợ nếu có vướng mắc.',
		},
		{
			id: 'sc-3',
			title: 'Nhắc học viên PCTH mua khoá học',
			description: 'Cho khách đã tham gia thi thử nhưng chưa mua',
			channel: 'Zalo',
			owner: 'Sale',
			content: 'Xin chào {Tên}, cảm ơn anh/chị đã tham gia phiên chia sẻ thử. Bên em còn ưu đãi đặc biệt cho anh/chị trong 48h tới — anh/chị có muốn em gửi thêm thông tin chi tiết không ạ?',
		},
		{
			id: 'sc-4',
			title: 'Xử lý yêu cầu refund',
			description: 'Quy trình khi khách xin hoàn tiền',
			channel: 'Điện thoại',
			owner: 'CSKH',
			content: 'Cảm ơn anh/chị đã phản hồi. Em ghi nhận yêu cầu và sẽ chuyển bộ phận xử lý trong 24h. Em xin phép hỏi thêm lý do để cải thiện dịch vụ được không ạ?',
		},
	];

	var state = {
		tags: clone(SEED_TAGS),
		rules: clone(SEED_RULES),
		scenarios: clone(SEED_SCENARIOS),
	};

	function reset() {
		state.tags = clone(SEED_TAGS);
		state.rules = clone(SEED_RULES);
		state.scenarios = clone(SEED_SCENARIOS);
	}

	function getTags() {
		return clone(state.tags);
	}

	function getRules() {
		return clone(state.rules).sort(function (a, b) {
			return (a.priority || 0) - (b.priority || 0);
		});
	}

	function getScenarios() {
		return clone(state.scenarios);
	}

	function getTagById(id) {
		for (var i = 0; i < state.tags.length; i++) {
			if (state.tags[i].id === id) return clone(state.tags[i]);
		}
		return null;
	}

	function getRuleById(id) {
		for (var i = 0; i < state.rules.length; i++) {
			if (state.rules[i].id === id) return clone(state.rules[i]);
		}
		return null;
	}

	function getTagUsageCount(tagId) {
		var n = 0;
		state.rules.forEach(function (r) {
			if ((r.tag_ids || []).indexOf(tagId) >= 0) n++;
		});
		return n;
	}

	function createTag(payload) {
		var tag = {
			id: uid('tag'),
			name: payload.name || '',
			category: payload.category || null,
			description: payload.description || null,
		};
		state.tags.push(tag);
		return clone(tag);
	}

	function updateTag(id, payload) {
		for (var i = 0; i < state.tags.length; i++) {
			if (state.tags[i].id === id) {
				state.tags[i] = Object.assign({}, state.tags[i], {
					name: payload.name,
					category: payload.category || null,
					description: payload.description || null,
				});
				return clone(state.tags[i]);
			}
		}
		return null;
	}

	function deleteTag(id) {
		state.tags = state.tags.filter(function (t) { return t.id !== id; });
		state.rules.forEach(function (r) {
			r.tag_ids = (r.tag_ids || []).filter(function (tid) { return tid !== id; });
		});
	}

	function createRule(payload) {
		var rule = {
			id: uid('rule'),
			status_label: payload.status_label || '',
			name: payload.name || '',
			tag_ids: payload.tag_ids || [],
			priority: parseInt(payload.priority, 10) || 0,
			is_active: payload.is_active !== false,
		};
		state.rules.push(rule);
		return clone(rule);
	}

	function updateRule(id, payload) {
		for (var i = 0; i < state.rules.length; i++) {
			if (state.rules[i].id === id) {
				state.rules[i] = Object.assign({}, state.rules[i], {
					status_label: payload.status_label,
					name: payload.name,
					tag_ids: payload.tag_ids || [],
					priority: parseInt(payload.priority, 10) || 0,
					is_active: payload.is_active !== false,
				});
				return clone(state.rules[i]);
			}
		}
		return null;
	}

	function setRuleActive(id, isActive) {
		for (var i = 0; i < state.rules.length; i++) {
			if (state.rules[i].id === id) {
				state.rules[i].is_active = !!isActive;
				return clone(state.rules[i]);
			}
		}
		return null;
	}

	function deleteRule(id) {
		state.rules = state.rules.filter(function (r) { return r.id !== id; });
	}

	function createScenario(payload) {
		var sc = {
			id: uid('sc'),
			title: payload.title || '',
			description: payload.description || null,
			content: payload.content || '',
			channel: payload.channel || null,
			owner: payload.owner || null,
		};
		state.scenarios.push(sc);
		return clone(sc);
	}

	function updateScenario(id, payload) {
		for (var i = 0; i < state.scenarios.length; i++) {
			if (state.scenarios[i].id === id) {
				state.scenarios[i] = Object.assign({}, state.scenarios[i], {
					title: payload.title,
					description: payload.description || null,
					content: payload.content,
					channel: payload.channel || null,
					owner: payload.owner || null,
				});
				return clone(state.scenarios[i]);
			}
		}
		return null;
	}

	function deleteScenario(id) {
		state.scenarios = state.scenarios.filter(function (s) { return s.id !== id; });
	}

	global.MkTagRuleEngineStore = {
		reset: reset,
		getTags: getTags,
		getRules: getRules,
		getScenarios: getScenarios,
		getTagById: getTagById,
		getRuleById: getRuleById,
		getTagUsageCount: getTagUsageCount,
		createTag: createTag,
		updateTag: updateTag,
		deleteTag: deleteTag,
		createRule: createRule,
		updateRule: updateRule,
		setRuleActive: setRuleActive,
		deleteRule: deleteRule,
		createScenario: createScenario,
		updateScenario: updateScenario,
		deleteScenario: deleteScenario,
	};
}(window));
