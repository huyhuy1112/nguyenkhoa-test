/**
 * ServiceContracts Detail (Sales): related-tab badges + dropdown click fixes.
 */
(function ($) {
	'use strict';

	function isScopedBody() {
		var b = document.body;
		return !!(b
			&& b.getAttribute('data-module') === 'ServiceContracts'
			&& b.getAttribute('data-view') === 'Detail'
			&& b.getAttribute('data-app') === 'SALES');
	}

	function refreshRelatedBadges() {
		var nodes = document.querySelectorAll(
			'.mk-sc-detail-related-tabs li[data-module] > a .numberCircle'
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
			if (count === 0) {
				el.classList.add('hide');
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

		$doc.off('click.mkScHeaderMore', '.mk-sc-detail-actions__group > .dropdown-toggle');
		$doc.on('click.mkScHeaderMore', '.mk-sc-detail-actions__group > .dropdown-toggle', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $group = $(this).closest('.mk-sc-detail-actions__group');
			var shouldOpen = !$group.hasClass('open');
			$('.mk-sc-detail-actions__group, .mk-sc-detail-related-tabs .related-tab-more-element').removeClass('open');
			$('.mk-sc-detail-actions__group .dropdown-toggle, .mk-sc-detail-related-tabs .related-tab-more-element .dropdown-toggle').attr('aria-expanded', 'false');
			if (shouldOpen) {
				toggleDropdown($group, true);
			}
		});

		$doc.off('click.mkScTabMore', '.mk-sc-detail-related-tabs .related-tab-more-element > .dropdown-toggle');
		$doc.on('click.mkScTabMore', '.mk-sc-detail-related-tabs .related-tab-more-element > .dropdown-toggle', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $li = $(this).closest('.related-tab-more-element');
			var shouldOpen = !$li.hasClass('open');
			$('.mk-sc-detail-actions__group, .mk-sc-detail-related-tabs .related-tab-more-element').removeClass('open');
			$('.mk-sc-detail-actions__group .dropdown-toggle, .mk-sc-detail-related-tabs .related-tab-more-element .dropdown-toggle').attr('aria-expanded', 'false');
			if (shouldOpen) {
				toggleDropdown($li, true);
			}
		});

		$doc.off('click.mkScDropdownClose');
		$doc.on('click.mkScDropdownClose', function (e) {
			if ($(e.target).closest('.mk-sc-detail-actions__group, .related-tab-more-element').length) {
				return;
			}
			$('.mk-sc-detail-actions__group, .mk-sc-detail-related-tabs .related-tab-more-element').removeClass('open');
			$('.mk-sc-detail-actions__group .dropdown-toggle, .mk-sc-detail-related-tabs .related-tab-more-element .dropdown-toggle').attr('aria-expanded', 'false');
		});
	}

	function boot() {
		if (!isScopedBody()) {
			return;
		}
		refreshRelatedBadges();
		bindDropdownClicks();

		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.summaryview.load', refreshRelatedBadges);
			app.event.on('post.detailedview.load', refreshRelatedBadges);
		}

		var tabs = document.querySelector('.mk-sc-detail-related-tabs');
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
