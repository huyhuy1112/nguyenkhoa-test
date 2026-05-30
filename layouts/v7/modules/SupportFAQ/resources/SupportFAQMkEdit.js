/**
 * SupportFAQ Create (SUPPORT) — dashboard shell + stock vtiger form.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260527_sf_edit1';

	function isScoped() {
		return (
			$('body').data('module') === 'SupportFAQ' &&
			$('body').data('view') === 'Edit' &&
			$('#mkSfFaqCreateWorkspace').length &&
			!$('input[name="record"]').val()
		);
	}

	function $form() {
		return $('#mkSfFaqFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkSfFaqFormHost');
		$host.find('#modnavigator, .editViewModNavigator').addClass('mk-sf-faq-hide-legacy');
		$host.find('.editViewHeader').addClass('mk-sf-faq-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-sf-faq-form-footer');
		$host.find('.main-container').first().addClass('mk-sf-faq-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-sf-faq-block')) {
					return;
				}
				$block.addClass('mk-sf-faq-block');
				$block.find('.fieldBlockHeader').first().addClass('mk-sf-faq-block__header');
				$block.find('> hr').addClass('mk-sf-faq-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-sf-faq-fields-table');
			});
	}

	function triggerSave() {
		var $save = $form().find('.saveButton').first();
		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$form().trigger('submit');
	}

	function bindActions() {
		$('#mkSfFaqSaveTop')
			.off('click.mkSfFaqSave')
			.on('click.mkSfFaqSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkSfFaqCreate')
			.on('keydown.mkSfFaqCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkSfFaqFormHost').length) {
						return;
					}
					e.preventDefault();
					triggerSave();
				}
			});
	}

	function runEnhancements() {
		if (!isScoped()) {
			return;
		}
		hideLegacyChrome();
		styleFieldBlocks();
		bindActions();
	}

	function init() {
		if (!isScoped()) {
			return;
		}
		runEnhancements();
		setTimeout(runEnhancements, 150);
		setTimeout(runEnhancements, 600);

		$(document).ajaxComplete(function () {
			if (isScoped()) {
				setTimeout(runEnhancements, 80);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	window.__mkSfFaqCreateBuild = MK_BUILD;
})($);
