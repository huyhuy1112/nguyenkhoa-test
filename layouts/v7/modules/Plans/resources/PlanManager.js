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
		// vtiger Popup callback can return:
		// - JSON string: {"123":"Campaign A"}
		// - object: {123: "..."}
		// - array: [{id:"123", name:"..."}, ...]
		// - string list: "123,124"
		var payload = resultJson;
		if (typeof payload === 'string') {
			try {
				payload = JSON.parse(payload);
			} catch (e) {
				// maybe CSV
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
			// Requirement: group by start_date; if missing show "No date"
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

		// Defensive cleanup: avoid stacked backdrops
		if (window.jQuery) {
			jQuery(modal).on('shown.bs.modal', function () {
				console.log('[Plans] modal shown');
				var $b = jQuery('.modal-backdrop');
				if ($b.length > 1) $b.not(':last').remove();
			});
			jQuery(modal).on('hidden.bs.modal', function () {
				console.log('[Plans] modal hidden');
				jQuery('.modal-backdrop').remove();
				jQuery('body').removeClass('modal-open').css('padding-right', '');
			});
		}

		return modal;
	}

	function showCampaignModal(row) {
		console.log('[Plans] campaign clicked:', row);
		var modal = ensureModal();
		var titleEl = document.getElementById('pmCampaignModalTitle');
		var bodyEl = document.getElementById('pmCampaignModalBody');
		var linkEl = document.getElementById('pmCampaignModalOpenLink');
		if (!titleEl || !bodyEl || !linkEl) return;

		titleEl.textContent = row.campaignname || '';
		linkEl.setAttribute('href', row.link || '#');

		var desc = (row.description || '').trim();
		bodyEl.innerHTML = '' +
			'<div class="pm-modal-kv"><div class="pm-k"><strong>Status</strong></div><div class="pm-v">' + esc(row.status || '-') + '</div></div>' +
			'<div class="pm-modal-kv"><div class="pm-k"><strong>Start</strong></div><div class="pm-v">' + esc(row.start_date || 'No date') + '</div></div>' +
			'<div class="pm-modal-kv"><div class="pm-k"><strong>End</strong></div><div class="pm-v">' + esc(row.end_date || 'No date') + '</div></div>' +
			(desc ? ('<hr style="margin:12px 0;"/><div><strong>Description</strong><div style="margin-top:6px;white-space:pre-wrap;">' + esc(desc) + '</div></div>') : '');

		// Ensure modal appears above backdrop (some vtiger skins tweak z-index)
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

	PlanManager.prototype.loadSchedule = function () {
		var self = this;
		return withProgress(app.request.post({
			data: { module: 'Plans', action: 'GetSchedule', plan_id: self.planId }
		})).then(function (pair) {
			var err = pair[0], res = pair[1];
			if (err) {
				console.error('GetSchedule error', err);
				notifyError('Cannot load schedule. Check console for details.');
				return;
			}
			try { console.log('[Plans] GetSchedule response full:', JSON.stringify(res)); } catch (e) {}
			var payload = (res && res.result) ? res.result : res;
			console.log('[Plans] rows array:', payload ? payload.rows : null);
			if (payload && payload.success === false) {
				console.error('[Plans] GetSchedule failed:', res);
				notifyError(payload.error || 'Cannot load schedule.');
				return;
			}
			self.rows = (payload && payload.rows) ? payload.rows : [];
			console.log('[Plans] GetSchedule rows:', self.rows.length, payload);
			self.renderTable();
			self.renderSchedule();
		});
	};

	PlanManager.prototype.renderTable = function () {
		var self = this;
		if (!self.$tableBody) return;
		if (!self.rows.length) {
			self.$tableBody.innerHTML =
				'<tr><td colspan="5" class="mk-empty">' +
				'<div class="mk-empty__title">No campaigns added to this plan yet.</div>' +
				'<div class="mk-empty__subtitle">Click Add Campaign to include existing campaigns.</div>' +
				'</td></tr>';
			return;
		}

		self.$tableBody.innerHTML = self.rows.map(function (r) {
			return '' +
				'<tr data-id="' + esc(r.id) + '" data-cid="' + esc(r.campaign_id) + '">' +
				'<td><span class="pm-link pm-open-modal" role="button" tabindex="0">' + esc(r.campaignname) + '</span>' +
				' <a href="' + esc(r.link) + '" target="_blank" rel="noopener" class="pm-ext-link" title="Open in new tab">↗</a></td>' +
				'<td>' + esc(r.start_date || 'No date') + '</td>' +
				'<td>' + esc(r.end_date || 'No date') + '</td>' +
				'<td><span class="' + statusBadgeClass(r.status) + '">' + esc(r.status || '-') + '</span></td>' +
				'<td><button type="button" class="btn btn-xs mk-btn-danger pm-remove">Remove</button></td>' +
				'</tr>';
		}).join('');

		// Remove handler
		self.$tableBody.querySelectorAll('.pm-remove').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				var tr = e.currentTarget.closest('tr');
				if (!tr) return;
				var id = parseInt(tr.getAttribute('data-id') || '0', 10);
				self.deleteCampaign(id);
			});
		});

		// Row/modal handler
		self.$tableBody.querySelectorAll('tr').forEach(function (tr) {
			tr.addEventListener('click', function (e) {
				var t = e.target;
				// Don't hijack clicks on Remove button or external link
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
				'<div class="mk-empty">' +
				'<div class="mk-empty__title">No campaigns added to this plan yet.</div>' +
				'<div class="mk-empty__subtitle">Click Add Campaign to include existing campaigns.</div>' +
				'</div>';
			return;
		}

		var g = groupByDate(self.rows);
		self.$schedule.innerHTML = g.dates.map(function (d) {
			var items = g.map[d].map(function (r) {
				var desc = shortText(r.description, 120);
				return '' +
					'<div class="pm-item" data-id="' + esc(r.id) + '">' +
					'<div class="pm-item-name">' + esc(r.campaignname) + '</div>' +
					'<div class="pm-item-meta"><span class="' + statusBadgeClass(r.status) + '">' + esc(r.status || '') + '</span><span>' +
					esc((r.start_date || 'No date') + ' → ' + (r.end_date || 'No date')) +
					'</span></div>' +
					(desc ? '<div class="pm-item-desc" style="font-size:12px;color:#64748b;margin-top:4px;">' + esc(desc) + '</div>' : '') +
					'</div>';
			}).join('');

			return '' +
				'<div class="pm-day mk-panel mk-panel--tight" style="margin-bottom:12px;">' +
				'<div class="pm-day-title"><span>' + esc(d) + '</span><span class="text-muted">' + g.map[d].length + '</span></div>' +
				items +
				'</div>';
		}).join('');

		self.$schedule.querySelectorAll('.pm-item').forEach(function (el) {
			el.addEventListener('click', function (e) {
				var id = parseInt(e.currentTarget.getAttribute('data-id') || '0', 10);
				var row = self.rows.find(function (x) { return x.id === id; });
				if (row) showCampaignModal(row);
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
			console.log('[Plans] popup selected campaign ids:', ids, 'raw:', resultJson);
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
				console.error('AddCampaign error', err);
				notifyError('Add campaign failed. Check console for details.');
				return;
			}
			console.log('[Plans] AddCampaign response:', res);
			try { console.log('[Plans] AddCampaign response full:', JSON.stringify(res)); } catch (e) {}
			if (res && res.result && res.result.success === false) {
				console.error('[Plans] AddCampaign failed:', res);
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
				console.error('DeleteCampaign error', err);
				notifyError('Remove failed. Check console for details.');
				return;
			}
			if (res && res.result && res.result.success === false) {
				console.error('[Plans] DeleteCampaign failed:', res);
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

		// allow re-init after AJAX tab switch; prevent duplicate init per injected DOM
		if (root.getAttribute('data-pm-initialized') === '1') return;

		var mgr = new PlanManager(root);
		if (!mgr.planId) {
			console.warn('[Plans] PlanManager: missing planId, abort init');
			return;
		}

		root.setAttribute('data-pm-initialized', '1');
		console.log('[Plans] PlanManager init planId=', mgr.planId, 'ajax=', !!container);
		mgr.registerEvents();
		mgr.renderTable();
		mgr.renderSchedule();
		mgr.loadSchedule();
	}

	// Expose for AJAX re-init
	window.Plans_PlanManager = window.Plans_PlanManager || {};
	window.Plans_PlanManager.init = init;

	function boot() {
		init(document);

		// Re-init after Vtiger AJAX tab content load (Summary/Details are loaded via loadContents)
		if (window.app && app.event && typeof app.event.on === 'function') {
			app.event.on('post.relatedListLoad.click', function (e, container) {
				try { console.log('[Plans] post.relatedListLoad.click -> reinit'); } catch (ex) {}
				window.setTimeout(function () { init(document); }, 0);
			});
		}

		// Fallback: any AJAX completion might replace tab content
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

