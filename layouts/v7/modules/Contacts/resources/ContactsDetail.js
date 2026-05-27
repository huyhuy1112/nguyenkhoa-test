/**
 * Contacts Detail (Sales): related-tab badge refresh only. No layout DOM moves.
 * Scope: body[data-module="Contacts"][data-view="Detail"][data-app="SALES"]
 */
(function ($) {
	'use strict';

	function isScopedBody() {
		var b = document.body;
		return !!(b
			&& b.getAttribute('data-module') === 'Contacts'
			&& b.getAttribute('data-view') === 'Detail'
			&& (b.getAttribute('data-app') === 'SALES' || b.getAttribute('data-app') === 'MARKETING'));
	}

	function refreshRelatedBadges() {
		var nodes = document.querySelectorAll(
			'.mk-contact-detail-related-tabs li[data-module] > a .numberCircle'
		);
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			var raw = (el.textContent || '').trim();
			var count = parseInt(raw, 10);
			if (isNaN(count)) {
				count = 0;
			}
			el.setAttribute('data-count', String(count));
			if (count === 0) {
				el.classList.add('hide');
			}
		}
	}

	function boot() {
		if (!isScopedBody()) {
			return;
		}
		refreshRelatedBadges();

		if (typeof app !== 'undefined' && app.event && app.event.on) {
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
