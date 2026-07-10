(function () {
	'use strict';

	function notifySuccess(text) {
		if (window.app && app.helper && typeof app.helper.showSuccessNotification === 'function') {
			app.helper.showSuccessNotification({ message: text });
		}
	}

	function notifyError(text) {
		if (window.app && app.helper && typeof app.helper.showErrorNotification === 'function') {
			app.helper.showErrorNotification({ message: text });
		} else {
			console.error(text);
		}
	}

	function withProgress(promiseLike) {
		if (window.app && app.helper && typeof app.helper.showProgress === 'function') {
			app.helper.showProgress();
		}
		var done = function () {
			if (window.app && app.helper && typeof app.helper.hideProgress === 'function') {
				app.helper.hideProgress();
			}
		};
		return promiseLike.then(function (err, res) {
			done();
			return [err, res];
		});
	}

	function decodeHtmlEntities(s) {
		return (s || '')
			.replace(/&quot;/g, '"')
			.replace(/&#039;/g, "'")
			.replace(/&lt;/g, '<')
			.replace(/&gt;/g, '>')
			.replace(/&amp;/g, '&');
	}

	function esc(s) {
		return String(s || '').replace(/[&<>"']/g, function (c) {
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c];
		});
	}

	function num(v) {
		if (v === null || typeof v === 'undefined') return 0;
		var n = parseFloat(String(v).replace(/,/g, ''));
		return isNaN(n) ? 0 : n;
	}

	function formatMoney(v) {
		var n = num(v);
		if (window.MkCurrency && typeof MkCurrency.format === 'function') {
			return MkCurrency.format(n, { decimals: 0 });
		}
		return Math.round(n).toLocaleString('vi-VN');
	}

	function formatRoi(v) {
		var n = num(v);
		return (Math.round(n * 100) / 100).toFixed(2);
	}

	function parseDateLoose(s) {
		if (!s || String(s).trim() === '' || String(s).trim().toLowerCase() === 'no date') {
			return null;
		}
		var t = Date.parse(s);
		if (!isNaN(t)) return new Date(t);
		return null;
	}

	function formatShortDate(d) {
		if (!d) return '—';
		try {
			return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
		} catch (e) {
			return '—';
		}
	}

	function formatDisplayDateStr(s) {
		var d = parseDateLoose(s);
		if (d) return formatShortDate(d);
		if (!s || String(s).trim() === '' || String(s).trim().toLowerCase() === 'no date') {
			return 'No date';
		}
		return String(s).trim();
	}

	function formatDateRange(start, end) {
		var a = formatDisplayDateStr(start);
		var b = formatDisplayDateStr(end);
		if (a === b) return a;
		return a + ' → ' + b;
	}

	function slugify(s) {
		return String(s || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
	}

	function statusPillHtml(status) {
		var text = String(status || '-').trim() || '-';
		var key = slugify(text);
		return '<span class="pm-status-pill pm-status-pill--' + esc(key) + '">' +
			'<span class="pm-status-pill__dot" aria-hidden="true"></span>' +
			'<span class="pm-status-pill__text">' + esc(text) + '</span></span>';
	}

	function roiToneClass(roi) {
		var r = num(roi);
		if (r > 0.0001) return 'pm-tl-metric--pos';
		if (r < -0.0001) return 'pm-tl-metric--neg';
		return '';
	}

	function computePlanStats(rows) {
		var total = rows.length;
		var completed = 0;
		var planning = 0;
		var inProgress = 0;
		var totalCost = 0;
		rows.forEach(function (r) {
			var st = String(r.status || '').toLowerCase();
			if (st.indexOf('complete') !== -1) {
				completed++;
			} else if (st.indexOf('planning') !== -1) {
				planning++;
			} else if (st.indexOf('progress') !== -1 || st.indexOf('running') !== -1 || st.indexOf('active') !== -1) {
				inProgress++;
			}
			totalCost += num(r.cost);
		});
		var rate = total > 0 ? (100 * completed) / total : 0;
		return {
			total: total,
			completed: completed,
			planning: planning,
			inProgress: inProgress,
			rate: rate,
			totalCost: totalCost
		};
	}

	function computeInsights(rows) {
		var earliest = null;
		var latest = null;
		var longest = { days: -1, name: '' };
		var completed = 0;

		rows.forEach(function (r) {
			var st = String(r.status || '').toLowerCase();
			if (st.indexOf('complete') !== -1) completed++;

			var sd = parseDateLoose(r.start_date);
			var ed = parseDateLoose(r.end_date);
			if (sd && (!earliest || sd.getTime() < earliest.getTime())) earliest = sd;
			if (ed && (!latest || ed.getTime() > latest.getTime())) latest = ed;
			if (sd && ed && ed.getTime() >= sd.getTime()) {
				var days = Math.round((ed.getTime() - sd.getTime()) / 86400000);
				if (days > longest.days) {
					longest.days = days;
					longest.name = r.campaignname || '';
				}
			}
		});

		return {
			earliest: earliest,
			latest: latest,
			longest: longest,
			completed: completed
		};
	}

	function parseInitialRows() {
		var el = document.getElementById('PlanCampaignData');
		if (!el) return [];
		try {
			var raw = decodeHtmlEntities(el.textContent || el.innerText || '');
			var data = JSON.parse(raw);
			return Array.isArray(data) ? data : [];
		} catch (e) {
			return [];
		}
	}

	function parseSelectedCampaignIds(resultJson) {
		var payload = resultJson;
		if (typeof payload === 'string') {
			try {
				payload = JSON.parse(payload);
			} catch (e) {
				return payload.split(',').map(function (x) { return parseInt(String(x).trim(), 10); }).filter(Boolean);
			}
		}
		if (Array.isArray(payload)) {
			return payload
				.map(function (x) { return parseInt((x && (x.id || x.campaignid || x.record || x.value)) || 0, 10); })
				.filter(Boolean);
		}
		if (payload && typeof payload === 'object') {
			return Object.keys(payload).map(function (k) { return parseInt(k, 10); }).filter(Boolean);
		}
		return [];
	}

	function groupByDate(rows) {
		var map = {};
		rows.forEach(function (r) {
			var d = (r.start_date && String(r.start_date).trim()) ? String(r.start_date).trim() : 'No date';
			if (!map[d]) map[d] = [];
			map[d].push(r);
		});
		var dates = Object.keys(map).sort(function (a, b) {
			if (a === 'No date') return 1;
			if (b === 'No date') return -1;
			return a.localeCompare(b);
		});
		return { map: map, dates: dates };
	}

	function shortText(s, maxLen) {
		var t = String(s || '').trim();
		if (!t) return '';
		if (t.length <= maxLen) return t;
		return t.slice(0, Math.max(0, maxLen - 1)) + '…';
	}

	function statusBadgeClass(status) {
		var s = String(status || '').toLowerCase();
		if (!s) return 'mk-badge';
		if (s.indexOf('complete') !== -1) return 'mk-badge mk-badge--success';
		if (s.indexOf('planning') !== -1) return 'mk-badge mk-badge--info';
		if (s.indexOf('progress') !== -1 || s.indexOf('running') !== -1) return 'mk-badge mk-badge--warn';
		return 'mk-badge';
	}

	function rowRoiClass(roi) {
		var r = num(roi);
		if (r > 0.0001) return 'pm-row--pos';
		if (r < -0.0001) return 'pm-row--neg';
		return '';
	}

	function ensureModal() {
		var existing = document.getElementById('pmCampaignModal');
		if (existing) return existing;

		var wrapper = document.createElement('div');
		wrapper.innerHTML = '' +
			'<div class="modal fade" id="pmCampaignModal" tabindex="-1" role="dialog" aria-hidden="true">' +
			'  <div class="modal-dialog modal-md" role="document">' +
			'    <div class="modal-content">' +
			'      <div class="modal-header">' +
			'        <button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
			'          <span aria-hidden="true">&times;</span>' +
			'        </button>' +
			'        <h4 class="modal-title" id="pmCampaignModalTitle"></h4>' +
			'      </div>' +
			'      <div class="modal-body" id="pmCampaignModalBody"></div>' +
			'      <div class="modal-footer">' +
			'        <a class="btn btn-primary" id="pmCampaignModalOpenLink" target="_blank" rel="noopener">Open Campaign</a>' +
			'        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
			'      </div>' +
			'    </div>' +
			'  </div>' +
			'</div>';
		var modal = wrapper.firstChild;
		document.body.appendChild(modal);

		if (window.jQuery) {
			jQuery(modal).on('shown.bs.modal', function () {
				var $b = jQuery('.modal-backdrop');
				if ($b.length > 1) $b.not(':last').remove();
			});
			jQuery(modal).on('hidden.bs.modal', function () {
				jQuery('.modal-backdrop').remove();
				jQuery('body').removeClass('modal-open').css('padding-right', '');
			});
		}

		return modal;
	}

	function showCampaignModal(row) {
		var modal = ensureModal();
		var titleEl = document.getElementById('pmCampaignModalTitle');
		var bodyEl = document.getElementById('pmCampaignModalBody');
		var linkEl = document.getElementById('pmCampaignModalOpenLink');
		if (!titleEl || !bodyEl || !linkEl) return;

		titleEl.textContent = row.campaignname || '';
		linkEl.setAttribute('href', row.link || '#');

		var desc = (row.description || '').trim();
		var cost = num(row.cost);
		var revenue = num(row.revenue);
		var roi = num(row.roi);
		var finBlock =
			'<div class="pm-modal-kv"><div class="pm-k"><strong>Cost</strong></div><div class="pm-v">' + esc(formatMoney(cost)) + '</div></div>' +
			'<div class="pm-modal-kv"><div class="pm-k"><strong>Revenue</strong></div><div class="pm-v">' + esc(formatMoney(revenue)) + '</div></div>' +
			'<div class="pm-modal-kv"><div class="pm-k"><strong>ROI</strong></div><div class="pm-v">' + esc(formatRoi(roi)) + '%</div></div>';

		bodyEl.innerHTML = '' +
			'<div class="pm-modal-kv"><div class="pm-k"><strong>Status</strong></div><div class="pm-v">' + esc(row.status || '-') + '</div></div>' +
			'<div class="pm-modal-kv"><div class="pm-k"><strong>Start</strong></div><div class="pm-v">' + esc(row.start_date || 'No date') + '</div></div>' +
			'<div class="pm-modal-kv"><div class="pm-k"><strong>End</strong></div><div class="pm-v">' + esc(row.end_date || 'No date') + '</div></div>' +
			finBlock +
			(desc ? ('<hr style="margin:12px 0;"/><div><strong>Description</strong><div style="margin-top:6px;white-space:pre-wrap;">' + esc(desc) + '</div></div>') : '');

		modal.style.zIndex = '1060';
		var backdrops = document.querySelectorAll('.modal-backdrop');
		backdrops.forEach(function (b) { b.style.zIndex = '1050'; });

		if (window.jQuery && jQuery.fn && typeof jQuery.fn.modal === 'function') {
			jQuery('#pmCampaignModal').modal('show');
		} else {
			window.alert((row.campaignname || '') + '\n' + (row.start_date || '') + ' - ' + (row.end_date || ''));
		}
	}

	function PlanManager(root) {
		this.root = root;
		var pid = parseInt(root.getAttribute('data-plan-id') || '0', 10) || 0;
		if (!pid) {
			var input = document.querySelector('input[name="record"]');
			pid = parseInt((input && input.value) || '0', 10) || 0;
		}
		this.planId = pid;
		this.rows = parseInitialRows();
		this.$tableBody = document.getElementById('pmCampaignTableBody');
		this.$schedule = document.getElementById('pmSchedule');
		this.$addBtn = document.getElementById('pmAddCampaignBtn');
	}

	PlanManager.prototype.renderKpis = function () {
		var st = computePlanStats(this.rows);
		var elTotal = document.getElementById('pmKpiTotal');
		var elDone = document.getElementById('pmKpiCompleted');
		var elRate = document.getElementById('pmKpiRate');
		var elCost = document.getElementById('pmKpiCost');
		var elHint = document.getElementById('pmKpiCostHint');
		if (elTotal) elTotal.textContent = st.total ? String(st.total) : '0';
		if (elDone) elDone.textContent = st.total ? String(st.completed) : '0';
		if (elRate) elRate.textContent = st.total ? (Math.round(st.rate * 10) / 10).toFixed(1) + '%' : '0%';
		if (elCost) {
			if (st.totalCost > 0.0001) {
				elCost.textContent = formatMoney(st.totalCost);
				if (elHint) elHint.textContent = 'Sum of effective campaign costs';
			} else {
				elCost.textContent = '—';
				if (elHint) elHint.textContent = 'No cost data on linked campaigns';
			}
		}
	};

	PlanManager.prototype.renderInsights = function () {
		var rows = this.rows;
		var ins = computeInsights(rows);
		var elE = document.getElementById('pmInEarliest');
		var elL = document.getElementById('pmInLatest');
		var elLong = document.getElementById('pmInLongest');
		var elMix = document.getElementById('pmInMix');
		if (elE) elE.textContent = ins.earliest ? formatShortDate(ins.earliest) : '—';
		if (elL) elL.textContent = ins.latest ? formatShortDate(ins.latest) : '—';
		if (elLong) {
			if (ins.longest.days >= 0 && ins.longest.name) {
				elLong.textContent = ins.longest.days + ' d · ' + shortText(ins.longest.name, 40);
			} else {
				elLong.textContent = '—';
			}
		}
		if (elMix) {
			var st = computePlanStats(rows);
			var bar = document.getElementById('pmMixBarFill');
			if (!rows.length) {
				elMix.textContent = '—';
				if (bar) bar.style.width = '0%';
			} else {
				var active = st.inProgress + st.planning;
				var pct = st.total > 0 ? Math.round((active / st.total) * 100) : 0;
				elMix.textContent = 'Manual / Automated';
				if (bar) bar.style.width = Math.max(8, Math.min(100, pct)) + '%';
			}
		}
	};

	PlanManager.prototype.loadSchedule = function () {
		var self = this;
		return withProgress(app.request.post({
			data: { module: 'Plans', action: 'GetSchedule', plan_id: self.planId }
		})).then(function (pair) {
			var err = pair[0], res = pair[1];
			if (err) {
				notifyError('Cannot load schedule. Check console for details.');
				return;
			}
			var payload = (res && res.result) ? res.result : res;
			if (payload && payload.success === false) {
				notifyError(payload.error || 'Cannot load schedule.');
				return;
			}
			self.rows = (payload && payload.rows) ? payload.rows : [];
			self.renderTable();
			self.renderSchedule();
		});
	};

	PlanManager.prototype.renderTable = function () {
		var self = this;
		if (!self.$tableBody) return;

		self.renderKpis();
		self.renderInsights();

		if (!self.rows.length) {
			self.$tableBody.innerHTML =
				'<tr><td colspan="8" class="mk-empty">' +
				'<div class="mk-empty__title">No campaigns added to this plan yet.</div>' +
				'<div class="mk-empty__subtitle">Click Add Campaign to include existing campaigns.</div>' +
				'</td></tr>';
			return;
		}

		self.$tableBody.innerHTML = self.rows.map(function (r) {
			var roi = num(r.roi);
			var rowClass = rowRoiClass(roi);
			var cost = num(r.cost);
			var revenue = num(r.revenue);
			return '' +
				'<tr class="' + esc(rowClass) + '" data-id="' + esc(r.id) + '" data-cid="' + esc(r.campaign_id) + '">' +
				'<td><span class="pm-link pm-open-modal" role="button" tabindex="0">' + esc(r.campaignname) + '</span>' +
				' <a href="' + esc(r.link) + '" target="_blank" rel="noopener" class="pm-ext-link" title="Open in new tab">↗</a></td>' +
				'<td>' + esc(r.start_date || 'No date') + '</td>' +
				'<td>' + esc(r.end_date || 'No date') + '</td>' +
				'<td><span class="pm-status-pill pm-status-pill--' + esc(String(r.status || '').toLowerCase().replace(/[^a-z0-9]+/g, '-')) + '">' +
				'<span class="pm-status-pill__dot" aria-hidden="true"></span><span class="pm-status-pill__text">' + esc(r.status || '-') + '</span></span></td>' +
				'<td class="pm-num">' + esc(formatMoney(cost)) + '</td>' +
				'<td class="pm-num">' + esc(formatMoney(revenue)) + '</td>' +
				'<td class="pm-num">' + esc(formatRoi(roi)) + '</td>' +
				'<td class="pm-actions"><button type="button" class="btn btn-xs mk-btn-danger pm-remove">Remove</button></td>' +
				'</tr>';
		}).join('');

		self.$tableBody.querySelectorAll('.pm-remove').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				var tr = e.currentTarget.closest('tr');
				if (!tr) return;
				var id = parseInt(tr.getAttribute('data-id') || '0', 10);
				self.deleteCampaign(id);
			});
		});

		self.$tableBody.querySelectorAll('tr').forEach(function (tr) {
			tr.addEventListener('click', function (e) {
				var t = e.target;
				if (t && (t.classList.contains('pm-remove') || t.classList.contains('pm-ext-link'))) return;
				var id = parseInt(tr.getAttribute('data-id') || '0', 10);
				var row = self.rows.find(function (x) { return x.id === id; });
				if (row) showCampaignModal(row);
			});
		});
	};


	PlanManager.prototype.renderSchedule = function () {
		var self = this;
		if (!self.$schedule) return;
		if (!self.rows.length) {
			self.$schedule.innerHTML =
				'<div class="pm-schedule-empty">' +
				'<div class="pm-schedule-empty__title">No campaigns in this plan yet</div>' +
				'<p class="pm-schedule-empty__text">Add campaigns from Campaign Management — they will appear here on the timeline.</p>' +
				'</div>';
			return;
		}

		var g = groupByDate(self.rows);
		self.$schedule.innerHTML = g.dates.map(function (d) {
			var count = g.map[d].length;
			var label = formatDisplayDateStr(d);
			var items = g.map[d].map(function (r) {
				var desc = shortText(r.description, 120);
				var roi = num(r.roi);
				return '' +
					'<article class="pm-tl-card" data-id="' + esc(r.id) + '" role="listitem" tabindex="0">' +
					'<div class="pm-tl-card__main">' +
					'<h4 class="pm-tl-card__title">' + esc(r.campaignname) + '</h4>' +
					'<div class="pm-tl-card__meta">' +
					statusPillHtml(r.status) +
					'<span class="pm-tl-card__dates"><i class="fa fa-calendar-o" aria-hidden="true"></i> ' + esc(formatDateRange(r.start_date, r.end_date)) + '</span>' +
					'</div>' +
					(desc ? '<p class="pm-tl-card__desc">' + esc(desc) + '</p>' : '') +
					'</div>' +
					'<div class="pm-tl-card__metrics">' +
					'<div class="pm-tl-metric"><span class="pm-tl-metric__label">Cost</span><span class="pm-tl-metric__value">' + esc(formatMoney(r.cost)) + '</span></div>' +
					'<div class="pm-tl-metric"><span class="pm-tl-metric__label">Revenue</span><span class="pm-tl-metric__value">' + esc(formatMoney(r.revenue)) + '</span></div>' +
					'<div class="pm-tl-metric ' + roiToneClass(roi) + '"><span class="pm-tl-metric__label">ROI</span><span class="pm-tl-metric__value">' + esc(formatRoi(roi)) + '%</span></div>' +
					'</div>' +
					'<span class="pm-tl-card__chev" aria-hidden="true"><i class="fa fa-chevron-right"></i></span>' +
					'</article>';
			}).join('');

			return '' +
				'<section class="pm-tg" role="group" aria-label="' + esc(label) + '">' +
				'<header class="pm-tg__header">' +
				'<div class="pm-tg__date-badge"><i class="fa fa-calendar" aria-hidden="true"></i><span class="pm-tg__date-text">' + esc(label) + '</span></div>' +
				'<span class="pm-tg__count">' + count + ' campaign' + (count === 1 ? '' : 's') + '</span>' +
				'</header>' +
				'<div class="pm-tg__list" role="list">' + items + '</div>' +
				'</section>';
		}).join('');

		self.$schedule.querySelectorAll('.pm-tl-card').forEach(function (el) {
			function openCard() {
				var id = parseInt(el.getAttribute('data-id') || '0', 10);
				var row = self.rows.find(function (x) { return x.id === id; });
				if (row) showCampaignModal(row);
			}
			el.addEventListener('click', openCard);
			el.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					openCard();
				}
			});
		});
	};

	PlanManager.prototype.openCampaignPopup = function () {
		var self = this;
		if (!window.Vtiger_Popup_Js) return;
		var popupInstance = Vtiger_Popup_Js.getInstance();
		var params = { module: 'Campaigns', view: 'Popup', multi_select: true, src_module: 'Plans' };

		var onSelect = function (resultJson) {
			var ids = parseSelectedCampaignIds(resultJson);
			if (!ids.length) {
				notifyError('No campaign selected.');
				return;
			}
			self.addCampaigns(ids);
		};

		popupInstance.showPopup(params, onSelect);
	};

	PlanManager.prototype.addCampaigns = function (ids) {
		var self = this;
		if (!ids || !ids.length) return;
		return withProgress(app.request.post({
			data: { module: 'Plans', action: 'AddCampaign', plan_id: self.planId, campaign_ids: ids }
		})).then(function (pair) {
			var err = pair[0], res = pair[1];
			if (err) {
				notifyError('Add campaign failed. Check console for details.');
				return;
			}
			if (res && res.result && res.result.success === false) {
				notifyError(res.result.error || 'Add campaign failed.');
				return;
			}
			var inserted = (res && res.result && typeof res.result.inserted !== 'undefined') ? res.result.inserted : null;
			var skipped = (res && res.result && typeof res.result.skipped !== 'undefined') ? res.result.skipped : null;
			var msg = 'Campaign(s) added.';
			if (inserted !== null || skipped !== null) {
				msg = 'Added ' + (inserted || 0) + ' (skipped ' + (skipped || 0) + ').';
			}
			notifySuccess(msg);
			self.loadSchedule();
		});
	};

	PlanManager.prototype.deleteCampaign = function (rowId) {
		var self = this;
		if (!rowId) return;
		return withProgress(app.request.post({
			data: { module: 'Plans', action: 'DeleteCampaign', plan_id: self.planId, id: rowId }
		})).then(function (pair) {
			var err = pair[0], res = pair[1];
			if (err) {
				notifyError('Remove failed. Check console for details.');
				return;
			}
			if (res && res.result && res.result.success === false) {
				notifyError(res.result.error || 'Remove failed.');
				return;
			}
			notifySuccess('Removed.');
			self.loadSchedule();
		});
	};

	PlanManager.prototype.registerEvents = function () {
		var self = this;
		if (self.$addBtn) {
			self.$addBtn.addEventListener('click', function () {
				self.openCampaignPopup();
			});
		}
	};

	function init(container) {
		var scope = container || document;
		var root = scope.querySelector ? scope.querySelector('#PlanManagerRoot') : document.getElementById('PlanManagerRoot');
		if (!root || !window.app || !app.request) return;

		if (root.getAttribute('data-pm-initialized') === '1') return;

		var mgr = new PlanManager(root);
		if (!mgr.planId) {
			return;
		}

		root.setAttribute('data-pm-initialized', '1');
		mgr.registerEvents();
		mgr.renderTable();
		mgr.renderSchedule();
		mgr.loadSchedule();
	}

	window.Plans_PlanManager = window.Plans_PlanManager || {};
	window.Plans_PlanManager.init = init;

	function boot() {
		init(document);

		if (window.app && app.event && typeof app.event.on === 'function') {
			app.event.on('post.relatedListLoad.click', function () {
				window.setTimeout(function () { init(document); }, 0);
			});
		}

		if (window.jQuery) {
			var t = null;
			jQuery(document).ajaxComplete(function () {
				if (t) window.clearTimeout(t);
				t = window.setTimeout(function () { init(document); }, 50);
			});
		}
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
	else boot();
})();
