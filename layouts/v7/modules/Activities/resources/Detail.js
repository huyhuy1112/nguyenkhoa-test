/* Activities Detail (SUPPORT) — tabs + more menu */
(function ($, window, document) {
	'use strict';

	function initTabs() {
		var $root = $('#mkActDetailPage');
		if (!$root.length) {
			return;
		}

		var $tabs = $root.find('.mk-act-detail-tabs__btn[data-mk-act-tab]');
		var $panels = $root.find('.mk-act-detail-panel');

		$tabs.on('click', function () {
			var tab = $(this).attr('data-mk-act-tab');
			if (!tab) {
				return;
			}
			$tabs.removeClass('is-active mk-act-detail-tabs__btn--active').attr('aria-selected', 'false');
			$(this).addClass('is-active mk-act-detail-tabs__btn--active').attr('aria-selected', 'true');
			$panels.each(function () {
				var $panel = $(this);
				var isTarget = $panel.attr('id') === 'mk-act-tab-' + tab;
				$panel.toggleClass('is-active mk-act-detail-panel--active', isTarget);
				if (isTarget) {
					$panel.removeAttr('hidden');
				} else {
					$panel.attr('hidden', 'hidden');
				}
			});
		});
	}

	function initMoreMenu() {
		var $wrap = $('.mk-act-detail-more');
		if (!$wrap.length) {
			return;
		}
		var $toggle = $wrap.find('.mk-act-detail-more__toggle');
		var $menu = $wrap.find('.mk-act-detail-more__menu');

		function closeMenu() {
			$menu.attr('hidden', 'hidden');
			$toggle.attr('aria-expanded', 'false');
		}

		function openMenu() {
			$menu.removeAttr('hidden');
			$toggle.attr('aria-expanded', 'true');
		}

		$toggle.on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if ($menu.is('[hidden]')) {
				openMenu();
			} else {
				closeMenu();
			}
		});

		$(document).on('click.mkActDetailMore', function (e) {
			if (!$(e.target).closest('.mk-act-detail-more').length) {
				closeMenu();
			}
		});

		$(document).on('keydown.mkActDetailMore', function (e) {
			if (e.key === 'Escape') {
				closeMenu();
			}
		});
	}

	$(function () {
		initTabs();
		initMoreMenu();
	});
})(jQuery, window, document);
