/**
 * Leads Detail (Sales): related-tab badges + ready state (same pattern as Potentials Detail.js).
 */
(function ($) {
	'use strict';

	function isScopedBody() {
		var b = document.body;
		return !!(b
			&& b.getAttribute('data-module') === 'Leads'
			&& b.getAttribute('data-view') === 'Detail'
			&& b.getAttribute('data-app') === 'SALES');
	}

	function refreshRelatedBadges() {
		var nodes = document.querySelectorAll(
			'.mk-lead-detail-related-tabs li[data-module] > a .numberCircle'
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
		document.body.classList.remove('mk-lead-detail-ui-loading');
		document.body.classList.add('mk-lead-detail-ui-ready');
	}

	function boot() {
		if (!isScopedBody()) {
			return;
		}
		document.body.classList.add('mk-lead-detail-sales');
		refreshRelatedBadges();
		markReady();

		if (typeof app !== 'undefined' && app && typeof app.event !== 'undefined' && app.event && typeof app.event.on === 'function') {
			app.event.on('post.summaryview.load', refreshRelatedBadges);
			app.event.on('post.detailedview.load', refreshRelatedBadges);
			app.event.on('post.relatedListLoad.click', refreshRelatedBadges);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})(typeof jQuery !== 'undefined' ? jQuery : null);
