/* SupportFAQ Detail (SUPPORT) — tabs, more menu, increase occurrence POST */
(function ($, window, document) {
	'use strict';

	function initTabs() {
		var $root = $('#mkSfFaqDetailPage');
		if (!$root.length) {
			return;
		}

		var $tabs = $root.find('.mk-sf-faq-detail-tabs__btn[data-mk-sf-faq-tab]');
		var $panels = $root.find('.mk-sf-faq-detail-panel');

		$tabs.on('click', function () {
			var tab = $(this).attr('data-mk-sf-faq-tab');
			if (!tab) {
				return;
			}
			$tabs.removeClass('is-active').attr('aria-selected', 'false');
			$(this).addClass('is-active').attr('aria-selected', 'true');
			$panels.each(function () {
				var $panel = $(this);
				var isTarget = $panel.attr('id') === 'mk-sf-faq-tab-' + tab;
				$panel.toggleClass('is-active', isTarget);
				if (isTarget) {
					$panel.removeAttr('hidden');
				} else {
					$panel.attr('hidden', 'hidden');
				}
			});
		});
	}

	function initMoreMenu() {
		var $wrap = $('.mk-sf-faq-detail-more');
		if (!$wrap.length) {
			return;
		}
		var $toggle = $wrap.find('.mk-sf-faq-detail-more__toggle');
		var $menu = $wrap.find('.mk-sf-faq-detail-more__menu');

		function closeMenu() {
			$menu.attr('hidden', 'hidden');
			$toggle.attr('aria-expanded', 'false');
		}

		$toggle.on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if ($menu.is('[hidden]')) {
				$menu.removeAttr('hidden');
				$toggle.attr('aria-expanded', 'true');
			} else {
				closeMenu();
			}
		});

		$(document).on('click.mkSfFaqDetailMore', function (e) {
			if (!$(e.target).closest('.mk-sf-faq-detail-more').length) {
				closeMenu();
			}
		});

		$(document).on('keydown.mkSfFaqDetailMore', function (e) {
			if (e.key === 'Escape') {
				closeMenu();
			}
		});
	}

	function initIncreaseOccurrence() {
		var $btn = $('#mkSfFaqIncreaseOccurrence');
		if (!$btn.length) {
			return;
		}
		$btn.on('click', function (e) {
			e.preventDefault();
			var url = $btn.data('increase-url');
			if (!url) {
				return;
			}
			var form = $('<form/>', { method: 'post', action: url });
			if (typeof csrfMagicName !== 'undefined' && typeof csrfMagicToken !== 'undefined') {
				form.append($('<input/>', { type: 'hidden', name: csrfMagicName, value: csrfMagicToken }));
			}
			form.appendTo('body').submit();
		});
	}

	$(function () {
		initTabs();
		initMoreMenu();
		initIncreaseOccurrence();
	});
})(jQuery, window, document);
