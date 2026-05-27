/**
 * Potentials Opportunity Detail (Sales): scoped enhancement layer only.
 * - Does NOT replace Vtiger Detail.js / Detail-AJAX behavior.
 * - No MutationObservers, no polling, no repeated DOM rebuilds.
 * - Idempotent: safe to no-op on resume from AJAX tab loads.
 */
(function ($) {
	'use strict';

	function isScopedBody() {
		var b = document.body;
		return !!(b
			&& b.getAttribute('data-module') === 'Potentials'
			&& b.getAttribute('data-view')   === 'Detail'
			&& b.getAttribute('data-app')    === 'SALES');
	}

	/**
	 * Refresh the always-visible count badges on the right-side related-module
	 * icon tabs. Vtiger renders them with class `hide`; we always show.
	 */
	function refreshRelatedBadges() {
		var nodes = document.querySelectorAll(
			'.mk-opportunity-detail-related-tabs li[data-module] > a .numberCircle'
		);
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			el.classList.remove('hide');
			var raw = (el.textContent || '').trim();
			var count = parseInt(raw, 10);
			if (isNaN(count)) {
				count = 0;
			}
			el.setAttribute('data-count', String(count));
			if (raw === '') {
				el.textContent = '0';
			}
		}
	}

	function markReady() {
		document.body.classList.remove('mk-opportunity-detail-ui-loading');
		document.body.classList.add('mk-opportunity-detail-ui-ready');
	}

	function boot() {
		if (!isScopedBody()) {
			return;
		}
		document.body.classList.add('mk-opportunity-detail-sales');
		refreshRelatedBadges();
		markReady();

		// On AJAX tab switch (Detail summary <-> related), Vtiger replaces
		// inner content; re-run badge refresh once per event, no observer.
		if (typeof app !== 'undefined' && app && typeof app.event !== 'undefined' && app.event && typeof app.event.on === 'function') {
			app.event.on('post.summaryview.load', refreshRelatedBadges);
			app.event.on('post.detailedview.load', refreshRelatedBadges);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
			} else {
		boot();
	}
})(typeof jQuery !== 'undefined' ? jQuery : null);
