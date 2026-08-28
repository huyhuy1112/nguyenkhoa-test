/**
 * Accounts Organizations Detail (Sales + Marketing): related-tab badge refresh; dropdown click fixes.
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

	function toggleDropdown($parent, open) {
		if (!$parent || !$parent.length) {
			return;
		}
		if (open) {
			$parent.addClass('open');
			$parent.find('.dropdown-toggle').attr('aria-expanded', 'true');
		} else {
			$parent.removeClass('open');
			$parent.find('.dropdown-toggle').attr('aria-expanded', 'false');
		}
	}

	function bindDropdownClicks() {
		var $doc = $(document);

		$doc.off('click.mkAccHeaderMore', '.mk-acc-detail-actions__group > .dropdown-toggle');
		$doc.on('click.mkAccHeaderMore', '.mk-acc-detail-actions__group > .dropdown-toggle', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $group = $(this).closest('.mk-acc-detail-actions__group');
			var shouldOpen = !$group.hasClass('open');
			$('.mk-acc-detail-actions__group, .mk-acc-detail-related-tabs .related-tab-more-element').removeClass('open');
			$('.mk-acc-detail-actions__group .dropdown-toggle, .mk-acc-detail-related-tabs .related-tab-more-element .dropdown-toggle').attr('aria-expanded', 'false');
			if (shouldOpen) {
				toggleDropdown($group, true);
			}
		});

		$doc.off('click.mkAccTabMore', '.mk-acc-detail-related-tabs .related-tab-more-element > .dropdown-toggle');
		$doc.on('click.mkAccTabMore', '.mk-acc-detail-related-tabs .related-tab-more-element > .dropdown-toggle', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $li = $(this).closest('.related-tab-more-element');
			var shouldOpen = !$li.hasClass('open');
			$('.mk-acc-detail-actions__group, .mk-acc-detail-related-tabs .related-tab-more-element').removeClass('open');
			$('.mk-acc-detail-actions__group .dropdown-toggle, .mk-acc-detail-related-tabs .related-tab-more-element .dropdown-toggle').attr('aria-expanded', 'false');
			if (shouldOpen) {
				toggleDropdown($li, true);
			}
		});

		$doc.off('click.mkAccDropdownClose');
		$doc.on('click.mkAccDropdownClose', function (e) {
			if ($(e.target).closest('.mk-acc-detail-actions__group, .related-tab-more-element').length) {
				return;
			}
			$('.mk-acc-detail-actions__group, .mk-acc-detail-related-tabs .related-tab-more-element').removeClass('open');
			$('.mk-acc-detail-actions__group .dropdown-toggle, .mk-acc-detail-related-tabs .related-tab-more-element .dropdown-toggle').attr('aria-expanded', 'false');
		});
	}

	function boot() {
		if (!isScopedBody()) {
			return;
		}
		document.body.classList.add('mk-acc-detail-modern');
		refreshRelatedBadges();
		bindDropdownClicks();

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
