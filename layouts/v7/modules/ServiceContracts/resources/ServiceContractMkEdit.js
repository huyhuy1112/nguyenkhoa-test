/**
 * ServiceContracts Create (SALES) — dashboard shell + stock vtiger form only.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260527_sc_create1';

	function isScoped() {
		return (
			$('body').data('module') === 'ServiceContracts' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'SALES' || !$('body').data('app')) &&
			$('#mkScCreateWorkspace').length &&
			!$('input[name="record"]').val()
		);
	}

	function $form() {
		return $('#mkScFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkScFormHost');
		$host.find('#modnavigator, .editViewModNavigator').addClass('mk-sc-hide-legacy');
		$host.find('.editViewHeader').addClass('mk-sc-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-sc-form-footer');
		$host.find('.main-container').first().addClass('mk-sc-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-sc-block')) {
					return;
				}
				$block.addClass('mk-sc-block');
				$block.find('.fieldBlockHeader').first().addClass('mk-sc-block__header');
				$block.find('> hr').addClass('mk-sc-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-sc-fields-table');
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
		$('#mkScSaveTop')
			.off('click.mkScSave')
			.on('click.mkScSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkScCreate')
			.on('keydown.mkScCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkScFormHost').length) {
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

	window.__mkScCreateBuild = MK_BUILD;
})($);
