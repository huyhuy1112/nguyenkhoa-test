/**
 * Leads Detail (SALES) — full Opp-style UI demo (no CRM record).
 * Includes: related icon tabs, log activity, activity log, purchase history, widgets.
 */
(function () {
	'use strict';

	var $jq = typeof jQuery !== 'undefined' ? jQuery : null;

	function byId(id) {
		return document.getElementById(id);
	}

	function leadLabel(key, fallback) {
		if (typeof app !== 'undefined' && app.vtranslate) {
			var t = app.vtranslate(key, 'Leads');
			if (t && t !== key) {
				return t;
			}
		}
		return fallback || key;
	}

	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}

	function leadCrmId(lead) {
		if (!lead) return null;
		if (lead.crmid != null && String(lead.crmid) !== '') return String(lead.crmid);
		if (/^\d+$/.test(String(lead.id || ''))) return String(lead.id);
		return null;
	}

	function activityDetailUrl(activityId) {
		return 'index.php?module=Calendar&view=Detail&record=' + encodeURIComponent(activityId) + '&app=SALES';
	}

	function calendarListUrl() {
		return 'index.php?module=Calendar&view=Calendar&app=SALES';
	}

	function potentialDetailUrl(potentialId) {
		return 'index.php?module=Potentials&view=Detail&record=' + encodeURIComponent(potentialId) + '&app=SALES';
	}

	function formatVnd(n) {
		try {
			return new Intl.NumberFormat('vi-VN').format(n) + ' đ';
		} catch (e) {
			return String(n) + ' đ';
		}
	}

	function nowLabel() {
		var d = new Date();
		var h = String(d.getHours()).padStart(2, '0');
		var m = String(d.getMinutes()).padStart(2, '0');
		var day = String(d.getDate()).padStart(2, '0');
		var mon = String(d.getMonth() + 1).padStart(2, '0');
		return h + ':' + m + ' ' + day + '/' + mon + '/' + d.getFullYear();
	}

	function stageKey(raw) {
		return String(raw || '')
			.toLowerCase()
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-|-$/g, '');
	}

	function cloneLeadData(lead) {
		return {
			id: lead.id,
			name: lead.name,
			company: lead.company,
			phone: lead.phone,
			email: lead.email,
			leadsource: lead.leadsource,
			leadstatus: lead.leadstatus,
			owner: lead.owner,
			value: lead.value,
			closeDate: lead.closeDate,
			tags: (lead.tags || []).slice(),
			comments: (lead.comments || []).slice(),
			modUpdates: (lead.modUpdates || []).slice(),
			activities: (lead.activities || []).slice(),
			activityLog: (lead.activityLog || []).slice(),
			purchases: (lead.purchases || []).slice(),
			calendarTasks: (lead.calendarTasks || []).slice(),
			badges: Object.assign(
				{
					contacts: 1,
					comments: 0,
					'activity-log': 0,
					purchases: 0,
					calendar: 0,
					tasks: 0,
					documents: 0,
					emails: 0,
				},
				lead.badges || {}
			),
		};
	}

	function defaultLead(id) {
		return cloneLeadData({
			id: id || 'L004',
			name: 'Lead ' + (id || 'L004'),
			company: 'Công ty demo',
			phone: '0900 000 000',
			email: 'lead@example.com',
			leadsource: 'Website',
			leadstatus: 'New Purchase',
			owner: 'Cao Thanh Nam',
			value: 25000000,
			closeDate: '01-06-2026',
			tags: ['demo'],
			comments: [],
			modUpdates: [],
			activities: [],
			activityLog: [],
			purchases: [],
			badges: { contacts: 1, comments: 0, 'activity-log': 0, purchases: 0, calendar: 0 },
		});
	}

	function storeLeadToDemo(raw) {
		var logic = window.LeadsLeadsLogic;
		var ref = window.LeadsLovableRef;
		var tags = raw.tags || [];
		var d = logic && logic.derive ? logic.derive(raw) : {};
		var sourceTag = null;
		var i;
		for (i = 0; i < tags.length; i++) {
			if (['facebook', 'tiktok', 'website', 'zalo', 'other'].indexOf(tags[i]) >= 0) {
				sourceTag = tags[i];
				break;
			}
		}
		var sourceLabel = sourceTag && ref && ref.tagMeta ? ref.tagMeta(sourceTag).label : 'Website';
		return {
			id: raw.id,
			crmid: raw.crmid,
			potentialId: raw.potentialId,
			name: raw.name,
			company: raw.companyName || '',
			phone: raw.phone,
			email: raw.email || '',
			leadsource: sourceLabel,
			leadstatus: d.stage || 'New Purchase',
			owner: raw.owner,
			value: raw.value || 0,
			closeDate: '',
			tags: tags.slice(),
			comments: (raw.comments || []).map(function (c) {
				return {
					id: c.id,
					text: c.text || '',
					html: c.html || '',
					author: c.author || '',
					when: c.timeLabel || c.time || '',
				};
			}),
			modUpdates: (raw.modUpdates || []).slice(),
			activities: (function () {
				var open = logic && logic.openCalendarTasks ? logic.openCalendarTasks(raw) : [];
				if (open.length) {
					return open.map(function (a) {
						return { id: a.id, subject: a.subject, when: a.when };
					});
				}
				var tasks = (raw.calendarTasks || []).filter(function (t) {
					var status = String((t && t.status) || 'open').toLowerCase();
					return status !== 'done' && status !== 'completed' && status !== 'closed';
				});
				return tasks.map(function (t) {
					return {
						id: t.id,
						subject: t.subject,
						when: t.dueLabel || (logic && logic.touchLabel ? logic.touchLabel(0) : 'Today'),
					};
				});
			})(),
			activityLog: (raw.activities || []).map(function (a) {
				return {
					type: a.type || 'note',
					label: a.label || 'NOTE',
					time: a.time || '',
					text: a.text || '',
				};
			}),
			cccd: raw.cccd || '',
			segment: raw.segment || '',
			district: raw.district || '',
			address: raw.address || '',
			area: raw.area || '',
			purchases: (raw.purchases || []).slice(),
			calendarTasks: (raw.calendarTasks || []).slice(),
			badges: {
				contacts: 1,
				comments: (raw.comments || []).length,
				'activity-log': (raw.activities || []).length,
				purchases: (raw.purchases || []).length,
				calendar: (function () {
					var logic = window.LeadsLeadsLogic;
					if (logic && logic.openCalendarTasks) {
						return logic.openCalendarTasks(raw).length;
					}
					return (raw.calendarTasks || []).length;
				})(),
				tasks: 0,
				documents: 0,
				emails: 0,
			},
		};
	}

	function resolveLead() {
		var root = document.getElementById('mk-leads-detail-root');
		var param = root && root.getAttribute('data-record-id');
		var qs = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
		var mkId = qs ? qs.get('mkLeadId') : null;
		var id = mkId || param;
		var store = window.LeadsLocalStore;
		if (store && id && typeof store.getLead === 'function') {
			var cached = store.getLead(id);
			if (cached) {
				return cloneLeadData(storeLeadToDemo(cached));
			}
		}
		var demo = typeof window !== 'undefined' ? window.MK_LEADS_DEMO : null;
		if (demo && typeof demo.resolveLead === 'function') {
			var found = demo.resolveLead(id || param);
			if (found) {
				return cloneLeadData(found);
			}
		}
		if (id || param) {
			return defaultLead(String(id || param));
		}
		return defaultLead('L-1001');
	}

	function kvRow(label, valueHtml) {
		return (
			'<tr class="summaryViewEntries">' +
			'<td class="fieldLabel"><label>' + esc(label) + '</label></td>' +
			'<td class="fieldValue"><span class="value">' + valueHtml + '</span></td>' +
			'</tr>'
		);
	}

	function detailField(label, valueHtml, full) {
		return (
			'<div class="mk-lead-detail-field' +
			(full ? ' mk-lead-detail-field--full' : '') +
			'"><dt class="mk-lead-detail-field__label">' +
			esc(label) +
			'</dt><dd class="mk-lead-detail-field__value">' +
			valueHtml +
			'</dd></div>'
		);
	}

	function detailSection(title, fieldsHtml) {
		if (!fieldsHtml) return '';
		return (
			'<section class="mk-lead-detail-details-section">' +
			'<h3 class="mk-lead-detail-details-section__title">' +
			esc(title) +
			'</h3>' +
			'<dl class="mk-lead-detail-details-section__grid">' +
			fieldsHtml +
			'</dl></section>'
		);
	}

	function renderDetailFields(lead) {
		var host = byId('mk-ld-ui-detail-fields');
		if (!host) return;

		var segLabel = lead.segment;
		if (segLabel && window.LeadsLeadsLogic && window.LeadsLeadsLogic.SEGMENT_LABELS) {
			segLabel = window.LeadsLeadsLogic.SEGMENT_LABELS[lead.segment] || lead.segment;
		}

		var tags = lead.tags || [];
		var tagsHtml =
			tags.length > 0
				? tags
						.map(function (t) {
							return '<span class="mk-lead-detail-tag-chip">' + esc(t) + '</span>';
						})
						.join('')
				: '<span class="mk-lead-detail-field__muted">—</span>';

		var contactFields =
			detailField('Tên', esc(lead.name)) +
			detailField(
				'Điện thoại',
				lead.phone
					? '<a href="tel:' + esc(lead.phone) + '">' + esc(lead.phone) + '</a>'
					: '<span class="mk-lead-detail-field__muted">—</span>'
			) +
			detailField(
				'Email',
				lead.email
					? '<a href="mailto:' + esc(lead.email) + '">' + esc(lead.email) + '</a>'
					: '<span class="mk-lead-detail-field__muted">—</span>'
			) +
			(lead.cccd ? detailField('CCCD', esc(lead.cccd)) : '') +
			(lead.company ? detailField('Công ty', esc(lead.company)) : '');

		var classifyFields =
			detailField('Lead ID', esc(lead.id || lead.crmid || '—')) +
			detailField('Trạng thái', esc(lead.leadstatus || '—')) +
			detailField('Nguồn', esc(lead.leadsource || '—')) +
			(segLabel ? detailField('Phân khúc', esc(segLabel)) : '') +
			detailField('Tags', tagsHtml, true);

		var addressFields = '';
		if (lead.district || lead.address || lead.area) {
			if (lead.district) addressFields += detailField('Quận / Huyện', esc(lead.district));
			if (lead.address) addressFields += detailField('Địa chỉ', esc(lead.address));
			else if (lead.area) addressFields += detailField('Khu vực', esc(lead.area));
		}

		var manageFields =
			detailField('Phụ trách', esc(lead.owner || '—')) +
			detailField('Giá trị', esc(formatVnd(lead.value))) +
			detailField('Ngày dự kiến', esc(lead.closeDate || '—')) +
			detailField('Chạm cuối', esc(lead.last_touch || '—')) +
			(lead.next_action ? detailField('Hành động tiếp', esc(lead.next_action), true) : '') +
			(lead.notes ? detailField('Ghi chú', esc(lead.notes), true) : '');

		host.innerHTML =
			detailSection('Thông tin liên hệ', contactFields) +
			detailSection('Phân loại', classifyFields) +
			(addressFields ? detailSection('Địa chỉ', addressFields) : '') +
			detailSection('Quản lý', manageFields);
	}

	function renderKeyFields(lead) {
		var host = byId('mk-ld-ui-key-fields');
		if (!host) return;
		var rows = '';
		rows += kvRow('Tên', esc(lead.name));
		if (lead.company) rows += kvRow('Công ty', esc(lead.company));
		rows += kvRow('Điện thoại', '<a href="tel:' + esc(lead.phone) + '">' + esc(lead.phone) + '</a>');
		if (lead.cccd) rows += kvRow('CCCD', esc(lead.cccd));
		if (lead.email) rows += kvRow('Email', '<a href="mailto:' + esc(lead.email) + '">' + esc(lead.email) + '</a>');
		if (lead.segment) {
			var segLabel =
				window.LeadsLeadsLogic && window.LeadsLeadsLogic.SEGMENT_LABELS
					? window.LeadsLeadsLogic.SEGMENT_LABELS[lead.segment] || lead.segment
					: lead.segment;
			rows += kvRow('Trạng thái khách', esc(segLabel));
		}
		if (lead.district) rows += kvRow('Khu vực', esc(lead.district));
		if (lead.address) rows += kvRow('Địa chỉ', esc(lead.address));
		else if (lead.area && !lead.district) rows += kvRow('Khu vực / Địa chỉ', esc(lead.area));
		rows += kvRow('Nguồn', esc(lead.leadsource));
		rows += kvRow('Ngày dự kiến', esc(lead.closeDate || '—'));
		rows += kvRow('Phụ trách', '<a href="javascript:void(0)">' + esc(lead.owner) + '</a>');
		rows += kvRow('Giá trị', esc(formatVnd(lead.value)));
		host.innerHTML = '<table class="summary-table"><tbody>' + rows + '</tbody></table>';
	}

  function persistLeadCache(lead) {
    var store = window.LeadsLocalStore;
    if (!store || !lead || !lead.id) return Promise.resolve(lead);
    if (store.reloadLead) {
      return store.reloadLead(lead.id).then(function (fresh) {
        return fresh ? storeLeadToDemo(fresh) : lead;
      }).catch(function () {
        return lead;
      });
    }
    if (typeof store.update !== "function") return Promise.resolve(lead);
    return store.update(lead.id, {
      next_action: window.LeadsLeadsLogic ? window.LeadsLeadsLogic.deriveNextAction(lead) : lead.next_action,
    });
  }

	function renderHeroMeta(lead) {
		var host = byId('mk-ld-ui-meta');
		if (!host) return;
		var html = '';
		if (lead.phone) {
			html += '<span class="mk-lead-detail-hero__meta-item"><span class="mk-lead-detail-hero__meta-text">' + esc(lead.phone) + '</span></span>';
		}
		if (lead.closeDate) {
			html += '<span class="mk-lead-detail-hero__meta-item mk-lead-detail-hero__meta-item--date"><span class="mk-lead-detail-hero__meta-text">' + esc(lead.closeDate) + '</span></span>';
		}
		if (lead.leadstatus) {
			html +=
				'<span class="mk-lead-detail-hero__stage mk-lead-stage-pill mk-lead-stage-pill--' +
				stageKey(lead.leadstatus) +
				'"><span class="mk-lead-stage-pill__dot" aria-hidden="true"></span><span class="mk-lead-stage-pill__text">' +
				esc(lead.leadstatus) +
				'</span></span>';
		}
		host.innerHTML = html;
	}

	function renderTags(lead) {
		var host = byId('mk-ld-ui-tag-list');
		if (!host) return;
		host.innerHTML = (lead.tags || [])
			.map(function (t) {
				return (
					'<span class="tag">' +
					esc(t) +
					' <a href="javascript:void(0)" class="mk-ld-ui-tag-remove" data-tag="' +
					esc(t) +
					'" aria-label="Remove">×</a></span>'
				);
			})
			.join(' ');
	}

	function renderActivityLog(lead) {
		var host = byId('mk-ld-ui-activity-log');
		var countEl = byId('mk-ld-ui-activity-log-count');
		var items = lead.activityLog || [];
		if (countEl) {
			countEl.textContent =
				items.length + ' ' + leadLabel('LBL_MK_ACTIVITY_LOG_ITEMS', 'mục');
		}
		if (!host) return;
		if (!items.length) {
			host.innerHTML =
				'<p class="mk-lead-activity-log__empty">' +
				esc(leadLabel('JS_MK_ACTIVITY_LOG_EMPTY', 'Chưa có hoạt động.')) +
				'</p>';
			return;
		}
		host.innerHTML = items
			.map(function (it) {
				var short = it.label ? it.label.charAt(0) : '?';
				return (
					'<article class="mk-lead-activity-log__item mk-lead-activity-log__item--' +
					esc(it.type) +
					'">' +
					'<div class="mk-lead-activity-log__icon" aria-hidden="true">' +
					short +
					'</div>' +
					'<div class="mk-lead-activity-log__body">' +
					'<div class="mk-lead-activity-log__meta"><span class="mk-lead-activity-log__type">' +
					esc(it.label) +
					'</span>' +
					esc(it.time) +
					'</div>' +
					'<p class="mk-lead-activity-log__text">' +
					esc(it.text) +
					'</p></div></article>'
				);
			})
			.join('');
	}

	function renderActivities(lead) {
		var host = byId('mk-ld-ui-activities');
		if (!host) return;
		var items = lead.activities || [];
		if (!items.length) {
			host.innerHTML = '<div class="noContent"><p>Chưa có công việc trong tương lai</p></div>';
			return;
		}
		host.innerHTML = items
			.map(function (a) {
				var href = a.id ? activityDetailUrl(a.id) : 'javascript:void(0)';
				return (
					'<div class="activityEntries">' +
					'<div class="summaryViewEntries"><a href="' +
					href +
					'">' +
					esc(a.subject) +
					'</a></div><span><strong>' +
					esc(a.when) +
					'</strong></span></div>'
				);
			})
			.join('');
	}

	function renderComments(lead) {
		var host = byId('mk-ld-ui-comments-list');
		if (!host) return;
		var items = lead.comments || [];
		if (!items.length) {
			host.innerHTML = '<div class="noContent"><p>Chưa có bình luận</p></div>';
			return;
		}
		host.innerHTML = items
			.map(function (c) {
				var body = c.html || esc(c.text);
				var meta = esc(c.author || '');
				if (c.when) {
					meta += meta ? ' · ' : '';
					meta += esc(c.when);
				}
				return (
					'<div class="commentDetails">' +
					'<div class="mk-lead-comment__body">' +
					body +
					'</div>' +
					(meta ? '<small class="mk-lead-comment__meta">' + meta + '</small>' : '') +
					'</div>'
				);
			})
			.join('');
	}

	function renderUpdateChange(change) {
		var html =
			'<div class="mk-lead-update-change">' +
			'<span class="mk-lead-update-change__field">' +
			esc(change.field) +
			'</span>';
		if (change.action === 'removed') {
			html += ' <span class="mk-lead-update-change__action">đã xóa</span> <del>' + esc(change.from) + '</del>';
		} else if (change.action === 'added') {
			html += ' <span class="mk-lead-update-change__action">đã thêm</span> <em>' + esc(change.to) + '</em>';
		} else {
			html +=
				' <span class="mk-lead-update-change__action">đổi từ</span> <span class="mk-lead-update-change__from">' +
				esc(change.from) +
				'</span> <span class="mk-lead-update-change__action">thành</span> <em>' +
				esc(change.to) +
				'</em>';
		}
		html += '</div>';
		return html;
	}

	function renderUpdates(lead) {
		var host = byId('mk-ld-ui-updates');
		if (!host) return;
		var items = lead.modUpdates || [];
		if (!items.length) {
			host.innerHTML = '<div class="noContent"><p>Chưa có lịch sử cập nhật</p></div>';
			return;
		}
		host.innerHTML =
			'<ul class="mk-lead-updates-timeline">' +
			items
				.map(function (u) {
					var changesHtml = (u.changes || []).map(renderUpdateChange).join('');
					var relationHtml = '';
					if (u.relation && u.relation.url) {
						var relAction = u.relation.action === 'unlinked' ? 'đã gỡ liên kết' : 'đã liên kết';
						relationHtml =
							'<div class="mk-lead-update-relation">' +
							relAction +
							' <a href="' +
							esc(u.relation.url) +
							'">' +
							esc(u.relation.label || u.title) +
							'</a></div>';
					}
					return (
						'<li class="mk-lead-update-item mk-lead-update-item--' +
						esc(u.kind || 'update') +
						'">' +
						'<time class="mk-lead-update-item__time" title="' +
						esc(u.time || '') +
						'">' +
						esc(u.timeLabel || u.time || '') +
						'</time>' +
						'<div class="mk-lead-update-item__icon" aria-hidden="true"></div>' +
						'<div class="mk-lead-update-item__body">' +
						'<h5 class="mk-lead-update-item__title">' +
						esc(u.title || u.user || 'Cập nhật') +
						'</h5>' +
						changesHtml +
						relationHtml +
						'</div></li>'
					);
				})
				.join('') +
			'</ul>';
	}

	function saveCommentApi(lead, text) {
		if (!window.MK_LEADS_API_READY || !lead || !lead.id || typeof app === 'undefined' || !app.request) {
			return Promise.resolve(null);
		}
		return app.request
			.post({
				data: {
					module: 'Leads',
					action: 'ModernApi',
					mode: 'comment_save',
					id: lead.id,
					text: text,
				},
			})
			.then(function (err, res) {
				if (err || !res || res.success === false) {
					throw err || new Error((res && res.error) || 'Không lưu được bình luận');
				}
				return res;
			});
	}

	function clearIconTabActive() {
		document.querySelectorAll('#mk-ld-ui-related-tabs [data-mk-scroll]').forEach(function (node) {
			node.classList.remove('active');
		});
	}

	function setIconTabActive(scrollKey) {
		clearIconTabActive();
		var iconTab = document.querySelector('#mk-ld-ui-related-tabs [data-mk-scroll="' + scrollKey + '"]');
		if (iconTab) {
			iconTab.classList.add('active');
		}
	}

	function syncBadges(lead) {
		lead.badges.comments = (lead.comments || []).length;
		lead.badges['activity-log'] = (lead.activityLog || []).length;
		lead.badges.calendar = (lead.activities || []).length;

		document.querySelectorAll('.mk-lead-detail-related-tabs .numberCircle[data-badge]').forEach(function (node) {
			var key = node.getAttribute('data-badge');
			var n = lead.badges[key];
			if (n === undefined) n = 0;
			node.textContent = String(n);
			node.setAttribute('data-count', String(n));
			node.classList.remove('hide');
		});
	}

	function setText(id, text) {
		var node = byId(id);
		if (node) {
			node.textContent = text;
			node.title = text;
		}
	}

	function scrollToSection(key) {
		var id = 'mk-ld-ui-section-' + key;
		var section = byId(id);
		if (section) {
			section.scrollIntoView({ behavior: 'smooth', block: 'start' });
			section.classList.add('mk-ld-ui-section-flash');
			window.setTimeout(function () {
				section.classList.remove('mk-ld-ui-section-flash');
			}, 1200);
		}
	}

	function bindTabs() {
		var textTabs = document.querySelectorAll('#mk-ld-ui-related-tabs [data-mk-ui-tab]');
		var scrollTabs = document.querySelectorAll('#mk-ld-ui-related-tabs [data-mk-scroll]');
		var panels = {
			summary: byId('mk-ld-ui-panel-summary'),
			detail: byId('mk-ld-ui-panel-detail'),
			updates: byId('mk-ld-ui-panel-updates'),
		};
		var i;

		function activateTextTab(key) {
			for (i = 0; i < textTabs.length; i++) {
				textTabs[i].classList.toggle('active', textTabs[i].getAttribute('data-mk-ui-tab') === key);
			}
			clearIconTabActive();
			Object.keys(panels).forEach(function (name) {
				if (panels[name]) {
					panels[name].classList.toggle('hide', name !== key);
				}
			});
		}

		for (i = 0; i < textTabs.length; i++) {
			textTabs[i].addEventListener('click', function (e) {
				var key = e.currentTarget.getAttribute('data-mk-ui-tab');
				activateTextTab(key);
			});
		}

		for (i = 0; i < scrollTabs.length; i++) {
			scrollTabs[i].addEventListener('click', function (e) {
				var li = e.currentTarget;
				var scrollKey = li.getAttribute('data-mk-scroll');
				activateTextTab('summary');
				setIconTabActive(scrollKey);
				window.setTimeout(function () {
					scrollToSection(scrollKey);
				}, 60);
			});
		}
	}

	function addActivityLogEntry(lead, type, text) {
		var map = {
			note: { label: leadLabel('LBL_MK_ADD_NOTE', 'Ghi chú'), type: 'note' },
			call: { label: leadLabel('LBL_MK_LOG_CALL', 'Cuộc gọi'), type: 'call' },
			meeting: { label: leadLabel('LBL_MK_LOG_MEETING', 'Cuộc họp'), type: 'meeting' },
			task: { label: leadLabel('LBL_MK_CREATE_TASK', 'Công việc'), type: 'task' },
		};
		var meta = map[type] || map.note;
		lead.activityLog.unshift({
			type: meta.type,
			label: meta.label,
			time: nowLabel(),
			text: text,
		});
		renderActivityLog(lead);
		syncBadges(lead);
	}

	function defaultSubjectForKind(kind, draft) {
		if (draft) return draft;
		var map = {
			note: leadLabel('LBL_MK_ADD_NOTE', 'Thêm ghi chú'),
			call: leadLabel('LBL_MK_LOG_CALL', 'Ghi cuộc gọi'),
			meeting: leadLabel('LBL_MK_LOG_MEETING', 'Ghi cuộc họp'),
			task: leadLabel('LBL_MK_CREATE_TASK', 'Tạo công việc'),
		};
		return map[kind] || leadLabel('LBL_MK_LOG_ACTIVITY', 'Hoạt động');
	}

	function setButtonBusy(btn, busy) {
		if (!btn) return;
		if (busy) {
			btn.setAttribute('disabled', 'disabled');
			btn.classList.add('mk-ld-ui-btn--busy');
		} else {
			btn.removeAttribute('disabled');
			btn.classList.remove('mk-ld-ui-btn--busy');
		}
	}

	function showToast(msg) {
		if (typeof app !== 'undefined' && app.helper && app.helper.showSuccessNotification) {
			app.helper.showSuccessNotification({ message: msg });
			return;
		}
		window.alert(msg);
	}

	/** Load <script src> from QuickCreateAjax HTML (jQuery .html() does not execute them). */
	function loadQuickCreateScripts(html, done) {
		if (!$jq || !html) {
			if (typeof done === 'function') {
				done();
			}
			return;
		}
		var $frag = $jq('<div>').append($jq.parseHTML(html, document, true));
		var urls = [];
		$frag.find('script[src]').each(function () {
			var src = $jq(this).attr('src');
			if (!src || urls.indexOf(src) >= 0) {
				return;
			}
			var exists = false;
			$jq('script[src]').each(function () {
				if (this.src === src || this.getAttribute('src') === src) {
					exists = true;
				}
			});
			if (!exists) {
				urls.push(src);
			}
		});
		if (!urls.length) {
			if (typeof done === 'function') {
				done();
			}
			return;
		}
		var pending = urls.length;
		function tick() {
			pending -= 1;
			if (pending <= 0 && typeof done === 'function') {
				done();
			}
		}
		urls.forEach(function (src) {
			$jq.getScript(src).always(tick);
		});
	}

	/** Strip script tags so showModal does not leave inert duplicates. */
	function stripScriptTags(html) {
		if (!$jq || !html) {
			return html;
		}
		var $frag = $jq('<div>').append($jq.parseHTML(html, document, true));
		$frag.find('script').remove();
		return $frag.html();
	}

  function extractQuickCreateUiMeta(html) {
    if (!html || typeof html !== "string") return null;
    var marker = "var quickcreate_uimeta =";
    var idx = html.indexOf(marker);
    if (idx < 0) return null;
    var start = html.indexOf("{", idx);
    if (start < 0) return null;
    var depth = 0;
    for (var i = start; i < html.length; i++) {
      var ch = html.charAt(i);
      if (ch === "{") depth++;
      else if (ch === "}") {
        depth--;
        if (depth === 0) {
          var chunk = html.substring(start, i + 1);
          try {
            return JSON.parse(chunk);
          } catch (e1) {
            try {
              return new Function("return " + chunk)();
            } catch (e2) {
              return null;
            }
          }
        }
      }
    }
    return null;
  }

  function registerLeadQuickCreateSave(form) {
    if (!$jq || !form || !form.length || typeof app === "undefined" || !app.request) return;

    form.off("submit.mkLeadQc");

    var validationMeta = typeof quickcreate_uimeta !== "undefined" ? quickcreate_uimeta : {};
    var moduleName = form.find('[name="module"]').val();
    var calendarEdit =
      typeof Calendar_Edit_Js !== "undefined" && (moduleName === "Calendar" || moduleName === "Events")
        ? Calendar_Edit_Js.getInstance()
        : null;

    var params = {
      submitHandler: function (formEl) {
        var $form = $jq(formEl);
        $jq("button[name='saveButton']").attr("disabled", "disabled");
        if (calendarEdit && typeof calendarEdit.syncQuickCreateAllDayPayload === "function") {
          calendarEdit.syncQuickCreateAllDayPayload(formEl);
        }
        var formData = $form.serialize();
        app.helper.showProgress();
        app.request.post({ data: formData }).then(function (err, data) {
          app.helper.hideProgress();
          $jq("button[name='saveButton']").removeAttr("disabled");
          if (err === null) {
            $jq(".vt-notification").remove();
            app.helper.hideModal();
            app.event.trigger("post.QuickCreateForm.save", data, $form.serializeFormData());
            var message =
              typeof app.vtranslate === "function"
                ? app.vtranslate("JS_RECORD_CREATED")
                : "Đã lưu";
            app.helper.showSuccessNotification({ message: message }, { delay: 3000 });
          } else {
            app.event.trigger("post.save.failed", err);
            var msg = typeof err === "string" ? err : (err && err.message) || "Lưu thất bại";
            if (app.helper.showErrorNotification) {
              app.helper.showErrorNotification({ message: msg });
            } else {
              window.alert(msg);
            }
          }
        });
        return false;
      },
    };
    if (validationMeta && Object.keys(validationMeta).length) {
      params.validationMeta = validationMeta;
    }
    form.vtValidate(params);
  }

  function initQuickCreateModal(container, subject, lead, kind) {
    if (!$jq || !container || !container.length) {
      return;
    }
    var form = container.find('form[name="QuickCreate"]');
    if (!form.length) {
      form = $jq('form[name="QuickCreate"]').last();
    }
    if (!form.length) {
      return;
    }

    applyQuickCreateLeadContext(form, lead, kind);

    if (typeof app.event !== "undefined" && app.event.trigger) {
      app.event.trigger("post.QuickCreateForm.show", form);
    }

		var sub = form.find('[name="subject"]');
		if (sub.length && subject) {
			sub.val(subject);
		}

		if (app.helper.registerLeavePageWithoutSubmit) {
			app.helper.registerLeavePageWithoutSubmit(form);
		}
		if (app.helper.registerModalDismissWithoutSubmit) {
			app.helper.registerModalDismissWithoutSubmit(form);
		}

		if (typeof vtUtils !== 'undefined' && vtUtils.applyFieldElementsView) {
			vtUtils.applyFieldElementsView(container);
		}

		var moduleName = form.find('[name="module"]').val();
		var targetInstance = null;
		if (typeof Vtiger_Edit_Js !== 'undefined') {
			targetInstance = Vtiger_Edit_Js.getInstanceByModuleName(moduleName);
		}
		if (targetInstance && typeof targetInstance.registerBasicEvents === 'function') {
			targetInstance.registerBasicEvents(form);
		}
		registerLeadQuickCreateSave(form);

    if (moduleName === "Events") {
      form.find('input[name="activitytype"]').val("Meeting");
      form.find('select[name="activitytype"]').val("Meeting").trigger("change");
    }

    if (typeof CalendarQuickCreate !== "undefined") {
      if (CalendarQuickCreate.reset) {
        CalendarQuickCreate.reset();
      }
      if (CalendarQuickCreate.init) {
        setTimeout(function () {
          CalendarQuickCreate.init();
        }, 150);
      }
    }
  }

  function applyQuickCreateLeadContext(form, lead, kind) {
    if (!$jq || !form || !form.length) return;
    var crmId = leadCrmId(lead);
    if (!crmId) return;
    form.find('input[name="parent_id"]').val(crmId);
    form.find('select[name="parent_id"]').val(crmId).trigger("change");
    var display = lead.name || "Lead #" + crmId;
    form.find('input[name="parent_id_display"]').val(display);
    form.find('input[name="returnmodule"]').val("Leads");
    form.find('input[name="returnrecord"]').val(crmId);
    if (kind === "call") {
      form.find('input[name="activitytype"]').val("Call");
      form.find('select[name="activitytype"]').val("Call").trigger("change");
    } else if (kind === "task") {
      form.find('input[name="activitytype"]').val("Task");
      form.find('select[name="activitytype"]').val("Task").trigger("change");
    }
  }

	function bindQuickCreateSave(kind, lead, subject) {
		if (typeof app.event === 'undefined' || !app.event.one) {
			return;
		}
		app.event.one('post.QuickCreateForm.save', function (event, data) {
			var text = subject || 'Activity';
			var logType =
				kind === 'meeting' ? 'meeting' : kind === 'call' ? 'call' : kind === 'note' ? 'note' : 'task';
			addActivityLogEntry(lead, logType, text);
			var activityId = data && (data._recordId || data.id || data.record);
			if (activityId && app.helper && app.helper.showSuccessNotification) {
				app.helper.showSuccessNotification({
					message:
						'Đã lưu activity. <a href="' +
						activityDetailUrl(activityId) +
						'" target="_blank" rel="noopener">Mở Calendar</a> · <a href="' +
						calendarListUrl() +
						'" target="_blank" rel="noopener">Xem lịch</a>',
				});
			}
			persistLeadCache(lead).then(function (fresh) {
				var row = fresh ? storeLeadToDemo(fresh) : lead;
				render(cloneLeadData(row));
			});
		});
	}

	function fetchQuickCreateForm(qcModule, subject, lead, kind, triggerBtn, tryFallback) {
		var requestData = { module: qcModule, view: 'QuickCreateAjax' };
		var crmId = leadCrmId(lead);
		if (crmId) {
			requestData.parentModule = 'Leads';
			requestData.parent_id = crmId;
			requestData.returnmodule = 'Leads';
			requestData.returnrecord = crmId;
		}
		app.helper.showProgress();
		app.request.post({ data: requestData }).then(function (err, data) {
			app.helper.hideProgress();
			setButtonBusy(triggerBtn, false);
			if (err) {
				if (kind === 'meeting' && tryFallback && qcModule === 'Events') {
					fetchQuickCreateForm('Calendar', subject, lead, kind, triggerBtn, false);
					return;
				}
				var msg =
					typeof err === 'string'
						? err
						: err && err.message
							? err.message
							: 'Không mở được form Quick Create.';
				if (app.helper.showErrorNotification) {
					app.helper.showErrorNotification({ message: msg });
				} else {
					window.alert(msg);
				}
				return;
			}
			if (!data) {
				showToast('Không nhận được nội dung form.');
				return;
			}
			bindQuickCreateSave(kind, lead, subject);
			loadQuickCreateScripts(data, function () {
				var meta = extractQuickCreateUiMeta(data);
				if (meta) {
					window.quickcreate_uimeta = meta;
				}
				var modalHtml = stripScriptTags(data);
				app.helper.showModal(modalHtml, {
					backdrop: 'static',
					keyboard: false,
					cb: function (container) {
						initQuickCreateModal(container, subject, lead, kind);
						if ($jq && container && container.length) {
							var $dlg = container.find('.modal-dialog');
							if ($dlg.hasClass('mk-qc-event-modal')) {
								container.addClass('mk-qc-event-modal-host');
								var $body = container.find('.modal-body');
								if ($body.length && app.helper.showVerticalScroll) {
									app.helper.showVerticalScroll($body, {
										scrollInertia: 200,
										autoHideScrollbar: true,
									});
								}
							}
						}
					},
				});
			});
		});
	}

	/**
	 * Open Quick Create — Task/Note/Call → Calendar; Meeting → Events (Vtiger POST).
	 */
	function openQuickCreateModal(kind, lead, triggerBtn) {
		if (typeof app === 'undefined' || !app.request || !app.helper) {
			showToast('Đang tải hệ thống… Vui lòng thử lại sau vài giây.');
			return;
		}

		var subject = defaultSubjectForKind(kind, '');
		var qcModule = kind === 'meeting' ? 'Events' : 'Calendar';

		setButtonBusy(triggerBtn, true);
		fetchQuickCreateForm(qcModule, subject, lead, kind, triggerBtn, kind === 'meeting');
	}

	function bindActivityLogSplitDropdown() {
		var group = document.querySelector('.mk-lead-split-add');
		if (!group) {
			return;
		}
		var toggle = group.querySelector('.mk-lead-split-add__toggle');
		if (!toggle || toggle.getAttribute('data-mk-dropdown-bound') === '1') {
			return;
		}
		toggle.setAttribute('data-mk-dropdown-bound', '1');

		function setOpen(open) {
			if (open) {
				group.classList.add('open');
			} else {
				group.classList.remove('open');
			}
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		}

		function closeAllExcept(except) {
			document.querySelectorAll('.mk-lead-split-add.open').forEach(function (g) {
				if (g === except) {
					return;
				}
				g.classList.remove('open');
				var t = g.querySelector('.mk-lead-split-add__toggle');
				if (t) {
					t.setAttribute('aria-expanded', 'false');
				}
			});
		}

		toggle.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var willOpen = !group.classList.contains('open');
			closeAllExcept(willOpen ? group : null);
			setOpen(willOpen);
		});

		if (!window._mkLeadActivityLogDropdownDocClose) {
			window._mkLeadActivityLogDropdownDocClose = true;
			document.addEventListener('click', function (e) {
				if (e.target.closest('.mk-lead-split-add')) {
					return;
				}
				closeAllExcept(null);
			});
		}
	}

	function bindActivityLogActions(lead) {
		bindActivityLogSplitDropdown();

		document.querySelectorAll('[data-mk-log]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var group = btn.closest('.mk-lead-split-add');
				if (group) {
					group.classList.remove('open');
					var t = group.querySelector('.mk-lead-split-add__toggle');
					if (t) {
						t.setAttribute('aria-expanded', 'false');
					}
				}
				openQuickCreateModal(btn.getAttribute('data-mk-log'), lead, btn);
			});
		});
	}

	function runConvertLead(lead, orderCategory) {
		if (!window.app || !app.request || !lead || !lead.id) return;
		app.helper.showProgress();
		app.request
			.post({
				data: {
					module: 'Leads',
					action: 'ModernApi',
					mode: 'convert',
					id: lead.id,
					order_category: orderCategory,
				},
			})
			.then(function (err, res) {
				app.helper.hideProgress();
				if (err || !res || res.success === false) {
					window.alert((err && err.message) || (res && res.error) || 'Convert thất bại');
					return;
				}
				if (res.redirect) {
					window.location.href = res.redirect;
					return;
				}
				if (res.potentialId) {
					window.location.href = potentialDetailUrl(res.potentialId);
				}
			});
	}

	function openConvertLeadModal(lead) {
		if (!lead || !lead.id) return;
		if (!$jq || typeof app === 'undefined' || !app.helper || !app.helper.showModal) {
			var fallback = window.prompt('Loại Opportunity: gõ Internal hoặc Project', 'Internal');
			if (fallback === null) return;
			var cat = String(fallback).trim();
			if (cat !== 'Internal' && cat !== 'Project') {
				window.alert('Chỉ chấp nhận Internal hoặc Project.');
				return;
			}
			runConvertLead(lead, cat);
			return;
		}

		var modalHtml =
			'<div class="modal-dialog mk-lead-convert-modal">' +
			'<div class="modal-content">' +
			'<div class="modal-header">' +
			'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
			'<h4 class="modal-title">Convert Lead</h4>' +
			'</div>' +
			'<div class="modal-body">' +
			'<p class="mk-lead-convert-modal__intro">Chuyển sang <strong>Contact + Account + Opportunity</strong>. Chọn loại đơn hàng cho Opportunity:</p>' +
			'<div class="mk-lead-convert-modal__choices" role="radiogroup" aria-label="Order category">' +
			'<label class="mk-lead-convert-modal__choice is-selected">' +
			'<input type="radio" name="mk_lead_convert_order_category" value="Internal" checked />' +
			'<span class="mk-lead-convert-modal__choice-body">' +
			'<span class="mk-lead-convert-modal__choice-title">Internal</span>' +
			'<span class="mk-lead-convert-modal__choice-desc">Đơn nội bộ / bán hàng thông thường</span>' +
			'</span></label>' +
			'<label class="mk-lead-convert-modal__choice">' +
			'<input type="radio" name="mk_lead_convert_order_category" value="Project" />' +
			'<span class="mk-lead-convert-modal__choice-body">' +
			'<span class="mk-lead-convert-modal__choice-title">Project</span>' +
			'<span class="mk-lead-convert-modal__choice-desc">Đơn dự án / triển khai theo project</span>' +
			'</span></label>' +
			'</div></div>' +
			'<div class="modal-footer">' +
			'<button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>' +
			'<button type="button" class="btn btn-primary mk-lead-convert-modal__submit">Convert</button>' +
			'</div></div></div>';

		app.helper.showModal(modalHtml, {
			backdrop: 'static',
			keyboard: false,
			cb: function (container) {
				var $root = container.find('.mk-lead-convert-modal');
				$root.find('.mk-lead-convert-modal__choice').on('click', function () {
					$root.find('.mk-lead-convert-modal__choice').removeClass('is-selected');
					$jq(this).addClass('is-selected');
					$jq(this).find('input[type="radio"]').prop('checked', true);
				});
				$root.find('.mk-lead-convert-modal__submit').on('click', function () {
					var cat = $root.find('input[name="mk_lead_convert_order_category"]:checked').val();
					if (cat !== 'Internal' && cat !== 'Project') {
						window.alert('Vui lòng chọn Internal hoặc Project.');
						return;
					}
					app.helper.hideModal();
					runConvertLead(lead, cat);
				});
			},
		});
	}

	function bindDemoActions(lead) {
		document.querySelectorAll('[data-mk-demo-action]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				if (btn.getAttribute('data-toggle') === 'dropdown') return;
				var action = btn.getAttribute('data-mk-demo-action') || '';
				if (action === 'edit' && lead && lead.id) {
					e.preventDefault();
					var editId = leadCrmId(lead) || lead.id;
					window.location.href =
						'index.php?module=Leads&view=Edit&record=' + encodeURIComponent(editId) + '&app=SALES';
					return;
				}
				if (action === 'convert') {
					e.preventDefault();
					openConvertLeadModal(lead);
					return;
				}
				if (action !== 'duplicate' && action !== 'delete') {
					window.alert(action + ' (UI demo).');
				}
			});
		});

		var follow = byId('mk-ld-ui-follow');
		if (follow) {
			follow.addEventListener('click', function (e) {
				e.stopPropagation();
				follow.classList.toggle('active');
			});
		}

		var addTag = byId('mk-ld-ui-add-tag');
		if (addTag) {
			addTag.addEventListener('click', function () {
				var tag = window.prompt('Thêm tag (UI demo):', 'moi');
				if (!tag) return;
				tag = tag.trim().toLowerCase().replace(/\s+/g, '_');
				if (lead.tags.indexOf(tag) < 0) {
					lead.tags.push(tag);
					renderTags(lead);
					bindTagRemove(lead);
				}
			});
		}

		var post = byId('mk-ld-ui-post-comment');
		var ta = byId('mk-ld-ui-comment');
		if (post && ta) {
			post.addEventListener('click', function () {
				var text = ta.value.trim();
				if (!text) {
					ta.focus();
					return;
				}
				post.disabled = true;
				saveCommentApi(lead, text)
					.then(function (res) {
						if (res && res.comments) {
							lead.comments = res.comments.map(function (c) {
								return {
									id: c.id,
									text: c.text || '',
									html: c.html || '',
									author: c.author || '',
									when: c.timeLabel || c.time || '',
								};
							});
						} else {
							lead.comments.unshift({ text: text, when: 'Vừa xong' });
						}
						ta.value = '';
						renderComments(lead);
						syncBadges(lead);
					})
					.catch(function () {
						lead.comments.unshift({ text: text, when: 'Vừa xong' });
						ta.value = '';
						renderComments(lead);
						syncBadges(lead);
					})
					.finally(function () {
						post.disabled = false;
					});
			});
		}

		document.querySelectorAll('[data-mk-qc]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var kind = btn.getAttribute('data-mk-qc') || 'task';
				openQuickCreateModal(kind, lead, btn);
			});
		});

		bindTagRemove(lead);
		bindActivityLogActions(lead);
	}

	function bindTagRemove(lead) {
		document.querySelectorAll('.mk-ld-ui-tag-remove').forEach(function (btn) {
			btn.onclick = function () {
				var tag = btn.getAttribute('data-tag');
				lead.tags = lead.tags.filter(function (t) {
					return t !== tag;
				});
				renderTags(lead);
				bindTagRemove(lead);
			};
		});
	}

	function render(lead) {
		setText('mk-ld-ui-crumb-name', lead.name);
		setText('mk-ld-ui-title', lead.name);
		var sub = byId('mk-ld-ui-subtitle');
		if (sub) {
			if (lead.company) {
				sub.textContent = lead.company;
				sub.style.display = '';
			} else {
				sub.textContent = '';
				sub.style.display = 'none';
			}
		}
		renderHeroMeta(lead);
		renderKeyFields(lead);
		renderDetailFields(lead);
		renderTags(lead);
		renderActivityLog(lead);
		renderActivities(lead);
		renderComments(lead);
		renderUpdates(lead);
		syncBadges(lead);
	}

	function markReady() {
		document.body.classList.remove('mk-lead-detail-ui-loading');
		document.body.classList.add('mk-lead-detail-ui-ready', 'mk-lead-detail-sales');
	}

	function boot() {
		if (!document.getElementById('mk-leads-detail-root')) return;
		var root = document.getElementById('mk-leads-detail-root');
		var param = root && root.getAttribute('data-record-id');
		var qs = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
		var mkId = qs ? qs.get('mkLeadId') : null;
		var id = mkId || param;
		var store = window.LeadsLocalStore;
		var bootStore = store && store.ready ? store.ready() : Promise.resolve();

		bootStore
			.then(function () {
				if (store && store.fetchLead && id) {
					return store.fetchLead(id, true);
				}
				return null;
			})
			.then(function () {
				var lead = cloneLeadData(resolveLead());
				render(lead);
				bindTabs();
				bindDemoActions(lead);
				markReady();
			})
			.catch(function (err) {
				console.error('Leads detail bootstrap failed', err);
				var lead = cloneLeadData(resolveLead());
				render(lead);
				bindTabs();
				bindDemoActions(lead);
				markReady();
			});
	}

	if ($jq) {
		$jq(boot);
	} else if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
