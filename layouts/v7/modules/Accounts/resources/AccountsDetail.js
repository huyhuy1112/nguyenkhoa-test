/**
 * Accounts Organizations Detail (Sales + Marketing): related-tab badge refresh; does not replace Vtiger Detail.js.
 */
(function ($) {
	'use strict';

	function isScopedBody() {
		var b = document.body;
		var app = b && b.getAttribute('data-app');
		return !!(b && b.getAttribute('data-module') === 'Accounts' && b.getAttribute('data-view') === 'Detail' &&
			(app === 'SALES' || app === 'MARKETING' || app === 'SUPPORT'));
	}

	function refreshRelatedBadges() {
		var nodes = document.querySelectorAll('.mk-acc-detail-related-tabs li[data-module] > a .numberCircle');
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

	function boot() {
		if (!isScopedBody()) {
			return;
		}
		document.body.classList.add('mk-acc-detail-modern');
		refreshRelatedBadges();

		var tabs = document.querySelector('.mk-acc-detail-related-tabs');
		if (tabs && typeof MutationObserver !== 'undefined') {
			var mo = new MutationObserver(function () { refreshRelatedBadges(); });
			mo.observe(tabs, { childList: true, subtree: true, characterData: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})(jQuery);
