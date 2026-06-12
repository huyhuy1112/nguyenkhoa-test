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
			comments: [],
			activities:
				logic && logic.openCalendarTasks
					? logic.openCalendarTasks(raw).map(function (a) {
							return { subject: a.subject, when: a.when };
						})
					: raw.next_action
						? [{ subject: raw.next_action, when: logic && logic.touchLabel ? logic.touchLabel(d.days) : 'Today' }]
						: [],
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
				comments: 0,
				'activity-log': (raw.activities || []).length,
				purchases: (raw.purchases || []).length,
				calendar: (raw.calendarTasks || []).length,
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

	function purchaseTotal(items) {
		var sum = 0;
		var i;
		for (i = 0; i < items.length; i++) {
			sum += items[i].value || 0;
		}
		return sum;
	}

	function commerceMetrics(lead) {
		var logic = window.LeadsLeadsLogic;
		if (!logic) {
			return { monthlyOrders: 0, totalProducts: 0, recentOrder: 0, nextAction: '' };
		}
		return {
			monthlyOrders: logic.monthlyOrderCount(lead),
			totalProducts: logic.totalProductsPurchased(lead),
			recentOrder: logic.recentOrderValue(lead),
			nextAction: logic.deriveNextAction(lead),
		};
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

	function orderListHtml(orders, emptyMsg) {
		if (!orders.length) {
			return '<p class="mk-lead-commerce-panel__empty">' + esc(emptyMsg) + '</p>';
		}
		return (
			'<ul class="mk-lead-order-list">' +
			orders
				.map(function (o) {
					return (
						'<li class="mk-lead-order-list__item">' +
						'<span class="mk-lead-order-list__name">' +
						esc(o.orderName) +
						'</span>' +
						'<span class="mk-lead-order-list__meta">' +
						esc(formatVnd(o.value)) +
						' · ' +
						esc(o.date) +
						'</span></li>'
					);
				})
				.join('') +
			'</ul>'
		);
	}

	function renderCommercePanels(lead) {
		var logic = window.LeadsLeadsLogic;
		var metrics = commerceMetrics(lead);
		var ordersHost = byId('mk-ld-ui-commerce-orders-month');
		var productsHost = byId('mk-ld-ui-commerce-products-total');
		var monthOrders =
			logic && logic.ordersInLastDays ? logic.ordersInLastDays(lead, 30) : [];
		var historyOrders =
			logic && logic.ordersOutsideLastDays ? logic.ordersOutsideLastDays(lead, 30) : [];

		if (ordersHost) {
			ordersHost.innerHTML =
				'<p class="mk-lead-commerce-panel__kpi"><span class="mk-lead-commerce-panel__num">' +
				esc(String(metrics.monthlyOrders)) +
				'</span> đơn hàng trong 30 ngày</p>' +
				orderListHtml(monthOrders, 'Không có đơn trong 30 ngày gần nhất.');
		}
		if (productsHost) {
			productsHost.innerHTML =
				'<p class="mk-lead-commerce-panel__kpi"><span class="mk-lead-commerce-panel__num">' +
				esc(String(metrics.totalProducts)) +
				'</span> sản phẩm (tổng SL)</p>' +
				orderListHtml(historyOrders, 'Không có đơn mua trước 30 ngày (lịch sử).');
		}
	}

	function renderDetailFields(lead) {
		var host = byId('mk-ld-ui-detail-fields');
		if (!host) return;
		var metrics = commerceMetrics(lead);
		var rows = '';
		rows += kvRow('Lead ID', esc(lead.id));
		rows += kvRow('Trạng thái', esc(lead.leadstatus));
		rows += kvRow('Nguồn', esc(lead.leadsource));
		rows += kvRow('Phụ trách', esc(lead.owner));
		rows += kvRow('Giá trị', esc(formatVnd(lead.value)));
		if (lead.district) rows += kvRow('Khu vực', esc(lead.district));
		if (lead.address) rows += kvRow('Địa chỉ', esc(lead.address));
		rows += kvRow('Giá trị đơn gần nhất', metrics.recentOrder ? esc(formatVnd(metrics.recentOrder)) : '—');
		host.innerHTML = '<table class="summary-table"><tbody>' + rows + '</tbody></table>';
	}

	function persistLeadCache(lead) {
		var store = window.LeadsLocalStore;
		if (!store || !lead || !lead.id || typeof store.update !== 'function') return;
		store.update(lead.id, {
			purchases: lead.purchases || [],
			calendarTasks: lead.calendarTasks || [],
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

	function renderPurchases(lead) {
		var host = byId('mk-ld-ui-purchases');
		var title = byId('mk-ld-ui-purchase-title');
		var items = lead.purchases || [];
		if (title) title.textContent = 'Lịch sử mua hàng (' + items.length + ')';
		if (!host) return;
		if (!items.length) {
			host.innerHTML = '<p class="mk-lead-activity-log__empty">Chưa có đơn mua hàng.</p>';
			return;
		}
		var total = purchaseTotal(items);
		var metrics = commerceMetrics(lead);
		var rows = items
			.map(function (p) {
				return (
					'<tr><td>' +
					esc(p.product) +
					'</td><td>' +
					esc(p.qty) +
					'</td><td>' +
					esc(formatVnd(p.value)) +
					'</td><td>' +
					esc(p.date) +
					'</td></tr>'
				);
			})
			.join('');
		var recentRow = '';
		if (metrics.recentOrder) {
			recentRow =
				'<tr><td colspan="2" class="mk-lead-purchase__total-label">Đơn gần nhất:</td>' +
				'<td class="mk-lead-purchase__total-value" colspan="2">' +
				esc(formatVnd(metrics.recentOrder)) +
				'</td></tr>';
		}
		host.innerHTML =
			'<table class="mk-lead-purchase__table">' +
			'<thead><tr><th>Sản phẩm</th><th>SL</th><th>Giá trị</th><th>Ngày</th></tr></thead>' +
			'<tbody>' +
			rows +
			'</tbody>' +
			'<tfoot>' +
			recentRow +
			'<tr><td colspan="2" class="mk-lead-purchase__total-label">Tổng:</td>' +
			'<td class="mk-lead-purchase__total-value" colspan="2">' +
			esc(formatVnd(total)) +
			'</td></tr></tfoot></table>';
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
				return (
					'<div class="activityEntries">' +
					'<div class="summaryViewEntries"><a href="javascript:void(0)">' +
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
			host.innerHTML = '';
			return;
		}
		host.innerHTML = items
			.map(function (c) {
				return (
					'<div class="commentDetails"><p>' +
					esc(c.text) +
					'</p><small>' +
					esc(c.when) +
					'</small></div>'
				);
			})
			.join('');
	}

	function syncBadges(lead) {
		lead.badges.comments = (lead.comments || []).length;
		lead.badges['activity-log'] = (lead.activityLog || []).length;
		lead.badges.purchases = (lead.purchases || []).length;
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

	function activateCommerceSubTab(key) {
		var subKey = key || 'orders-month';
		document.querySelectorAll('[data-mk-commerce-tab]').forEach(function (btn) {
			var active = btn.getAttribute('data-mk-commerce-tab') === subKey;
			btn.classList.toggle('is-active', active);
			btn.setAttribute('aria-selected', active ? 'true' : 'false');
		});
		document.querySelectorAll('[data-mk-commerce-panel]').forEach(function (panel) {
			panel.classList.toggle('hide', panel.getAttribute('data-mk-commerce-panel') !== subKey);
		});
	}

	function bindCommerceSubTabs() {
		document.querySelectorAll('[data-mk-commerce-tab]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				activateCommerceSubTab(btn.getAttribute('data-mk-commerce-tab'));
			});
		});
	}

	function bindTabs() {
		var textTabs = document.querySelectorAll('#mk-ld-ui-related-tabs [data-mk-ui-tab]');
		var panels = {
			summary: byId('mk-ld-ui-panel-summary'),
			detail: byId('mk-ld-ui-panel-detail'),
			updates: byId('mk-ld-ui-panel-updates'),
			'purchase-history': byId('mk-ld-ui-panel-purchase-history'),
		};
		var i;
		for (i = 0; i < textTabs.length; i++) {
			textTabs[i].addEventListener('click', function (e) {
				var li = e.currentTarget;
				var key = li.getAttribute('data-mk-ui-tab');
				var k;
				for (k = 0; k < textTabs.length; k++) textTabs[k].classList.remove('active');
				li.classList.add('active');
				Object.keys(panels).forEach(function (name) {
					if (panels[name]) panels[name].classList.toggle('hide', name !== key);
				});
				if (key === 'purchase-history') activateCommerceSubTab('orders-month');
			});
		}

		bindCommerceSubTabs();

		document.querySelectorAll('#mk-ld-ui-related-tabs [data-mk-scroll]').forEach(function (li) {
			li.addEventListener('click', function () {
				var panel = byId('mk-ld-ui-panel-summary');
				if (panel) panel.classList.remove('hide');
				Object.keys(panels).forEach(function (name) {
					if (name !== 'summary' && panels[name]) panels[name].classList.add('hide');
				});
				textTabs.forEach(function (t) {
					t.classList.toggle('active', t.getAttribute('data-mk-ui-tab') === 'summary');
				});
				scrollToSection(li.getAttribute('data-mk-scroll'));
			});
		});
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

	function initQuickCreateModal(container, subject) {
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

		if (typeof app.event !== 'undefined' && app.event.trigger) {
			app.event.trigger('post.QuickCreateForm.show', form);
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
		if (targetInstance && typeof targetInstance.quickCreateSave === 'function') {
			targetInstance.quickCreateSave(form, {});
		}

		if (moduleName === 'Events') {
			form.find('input[name="activitytype"]').val('Meeting');
			form.find('select[name="activitytype"]').val('Meeting').trigger('change');
		}

		if (typeof CalendarQuickCreate !== 'undefined') {
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

	function bindQuickCreateSave(kind, lead, subject) {
		if (typeof app.event === 'undefined' || !app.event.one) {
			return;
		}
		app.event.one('post.QuickCreateForm.save', function () {
			var text = subject || 'Activity';
			var logType =
				kind === 'meeting' ? 'meeting' : kind === 'call' ? 'call' : kind === 'note' ? 'note' : 'task';
			addActivityLogEntry(lead, logType, text);
			if (kind === 'task' || kind === 'meeting' || kind === 'call') {
				if (!lead.calendarTasks) lead.calendarTasks = [];
				lead.calendarTasks.unshift({
					type: kind === 'meeting' ? 'meeting' : kind === 'call' ? 'call' : 'task',
					subject: text,
					status: 'open',
					dueAt: new Date().toISOString(),
					dueLabel: 'Today',
				});
				lead.activities.unshift({ subject: text, when: nowLabel() });
				renderActivities(lead);
				renderKeyFields(lead);
				persistLeadCache(lead);
			}
			syncBadges(lead);
		});
	}

	function fetchQuickCreateForm(qcModule, subject, lead, kind, triggerBtn, tryFallback) {
		var requestData = { module: qcModule, view: 'QuickCreateAjax' };
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
				var modalHtml = stripScriptTags(data);
				app.helper.showModal(modalHtml, {
					backdrop: 'static',
					keyboard: false,
					cb: function (container) {
						initQuickCreateModal(container, subject);
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

	function bindDemoActions(lead) {
		document.querySelectorAll('[data-mk-demo-action]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				if (btn.getAttribute('data-toggle') === 'dropdown') return;
				var action = btn.getAttribute('data-mk-demo-action') || '';
				if (action === 'edit' && lead && lead.id) {
					e.preventDefault();
					window.location.href =
						'index.php?module=Leads&view=Edit&record=' +
						encodeURIComponent(lead.id) +
						'&app=SALES';
					return;
				}
				window.alert(action + ' (UI demo).');
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
				lead.comments.unshift({ text: text, when: 'Vừa xong' });
				ta.value = '';
				renderComments(lead);
				syncBadges(lead);
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
		renderCommercePanels(lead);
		renderTags(lead);
		renderActivityLog(lead);
		renderPurchases(lead);
		renderActivities(lead);
		renderComments(lead);
		syncBadges(lead);
	}

	function markReady() {
		document.body.classList.remove('mk-lead-detail-ui-loading');
		document.body.classList.add('mk-lead-detail-ui-ready', 'mk-lead-detail-sales');
	}

	function boot() {
		if (!document.getElementById('mk-leads-detail-root')) return;
		var lead = resolveLead();
		render(lead);
		bindTabs();
		bindDemoActions(lead);
		markReady();
	}

	if ($jq) {
		$jq(boot);
	} else if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
