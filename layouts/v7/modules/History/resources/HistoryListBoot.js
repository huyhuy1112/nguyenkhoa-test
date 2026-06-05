/**
 * History audit log (TOOLS): mark page ready, wire client-side filters.
 */
(function () {
	'use strict';

	function isHistoryToolsList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'History' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		if ((b.getAttribute('data-app') || '').toUpperCase() === 'TOOLS') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'History' && params.get('view') === 'List' && params.get('app') === 'TOOLS';
	}

	function parseDate(s) {
		if (!s) {
			return null;
		}
		var d = new Date(s + 'T00:00:00');
		return isNaN(d.getTime()) ? null : d;
	}

	function parseRowTime(t) {
		if (!t) {
			return null;
		}
		var normalized = String(t).replace(' ', 'T');
		var d = new Date(normalized);
		return isNaN(d.getTime()) ? null : d;
	}

	function bindAuditFilters() {
		var tableBody = document.getElementById('historyTableBody');
		if (!tableBody) {
			return;
		}

		var searchEl = document.getElementById('historySearch');
		var userEl = document.getElementById('historyUser');
		var moduleEl = document.getElementById('historyModule');
		var actionEl = document.getElementById('historyAction');
		var fromEl = document.getElementById('historyDateFrom');
		var toEl = document.getElementById('historyDateTo');
		var countEl = document.getElementById('historyVisibleCount');
		var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr.history-row'));

		function applyFilters() {
			var search = (searchEl && searchEl.value ? searchEl.value : '').toLowerCase().trim();
			var userId = userEl ? userEl.value : 'All';
			var module = moduleEl ? moduleEl.value : 'All';
			var action = actionEl ? actionEl.value : 'All';
			var fromD = parseDate(fromEl ? fromEl.value : '');
			var toD = parseDate(toEl ? toEl.value : '');
			var visible = 0;

			rows.forEach(function (row) {
				var actionV = row.getAttribute('data-action') || '';
				var userIdV = row.getAttribute('data-userid') || '';
				var userNameV = row.getAttribute('data-user') || '';
				var moduleV = row.getAttribute('data-module') || '';
				var recordV = row.getAttribute('data-record') || '';
				var detailsV = row.getAttribute('data-details') || '';
				var timeV = row.getAttribute('data-time') || '';
				var haystack = (actionV + ' ' + userNameV + ' ' + userIdV + ' ' + moduleV + ' ' + recordV + ' ' + detailsV).toLowerCase();
				var ok = true;

				if (search) {
					ok = ok && haystack.indexOf(search) !== -1;
				}
				if (action && action !== 'All') {
					ok = ok && actionV === action;
				}
				if (userId && userId !== 'All') {
					ok = ok && String(userIdV) === String(userId);
				}
				if (module && module !== 'All') {
					ok = ok && moduleV === module;
				}

				var rowD = parseRowTime(timeV);
				if (fromD) {
					ok = ok && rowD && rowD >= fromD;
				}
				if (toD) {
					var end = new Date(toD.getTime());
					end.setHours(23, 59, 59, 999);
					ok = ok && rowD && rowD <= end;
				}

				row.style.display = ok ? '' : 'none';
				if (ok) {
					visible += 1;
				}
			});

			if (countEl) {
				countEl.textContent = String(visible);
			}
		}

		[searchEl, userEl, moduleEl, actionEl, fromEl, toEl].forEach(function (el) {
			if (!el) {
				return;
			}
			el.addEventListener('input', applyFilters);
			el.addEventListener('change', applyFilters);
		});

		applyFilters();
	}

	function boot() {
		if (!isHistoryToolsList()) {
			return;
		}
		document.body.classList.add('mk-history-list-ready');
		bindAuditFilters();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
