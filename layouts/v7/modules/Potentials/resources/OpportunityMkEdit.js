/**
 * Potentials Create (SALES) — dashboard shell + stock vtiger form only.
 * No virtual chips, sliders, or AI panels; all fields stay native.
 */
(function ($) {
	'use strict';

var MK_BUILD = '20260527_create7';

	function isScoped() {
		return (
			$('body').data('module') === 'Potentials' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'SALES' || !$('body').data('app')) &&
			$('#mkOppCreateWorkspace').length &&
			!$('input[name="record"]').val()
		);
	}

	function $form() {
		return $('#mkOppFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkOppFormHost');
		$host.find('#modnavigator, .editViewModNavigator').addClass('mk-opp-hide-legacy');
		$host.find('.editViewHeader').addClass('mk-opp-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-opp-form-footer');
		$host.find('.main-container').first().addClass('mk-opp-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-opp-block')) {
					return;
				}
				$block.addClass('mk-opp-block');
				$block.find('.fieldBlockHeader').first().addClass('mk-opp-block__header');
				$block.find('> hr').addClass('mk-opp-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-opp-fields-table');
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
		$('#mkOppSaveTop')
			.off('click.mkOppSave')
			.on('click.mkOppSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkOppCreate')
			.on('keydown.mkOppCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkOppFormHost').length) {
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

	window.__mkOppCreateBuild = MK_BUILD;
})($);
