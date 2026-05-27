/**
 * Accounts Create (SALES) — dashboard shell + stock Inventory #EditView unchanged.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260527_account_create1';

	var BLOCK_ICONS = {
		LBL_ACCOUNT_INFORMATION: 'fa-building',
		LBL_CUSTOM_INFORMATION: 'fa-sliders',
		LBL_ADDRESS_INFORMATION: 'fa-map-marker',
		LBL_DESCRIPTION_INFORMATION: 'fa-align-left'
	};

	function isScoped() {
		return (
			$('body').data('module') === 'Accounts' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'SALES' || !$('body').data('app')) &&
			$('#mkAcCreateWorkspace').length &&
			!$('#mkAcFormHost input[name="record"]').val()
		);
	}

	function $form() {
		return $('#mkAcFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkAcFormHost');
		$host.find('#modnavigator, .editViewModNavigator, .module-nav').addClass('mk-ac-hide-legacy');
		$host.find('.editViewHeader').addClass('mk-ac-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-ac-form-footer');
		$host.find('.main-container').first().addClass('mk-ac-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-ac-block')) {
					return;
				}
				var blockKey = $block.attr('data-block') || '';
				$block.addClass('mk-ac-block');
				var $header = $block.find('.fieldBlockHeader').first();
				$header.addClass('mk-ac-block__header');
				if (!$header.find('.mk-ac-block__icon').length && BLOCK_ICONS[blockKey]) {
					$header.prepend(
						$('<span>', { class: 'mk-ac-block__icon', 'aria-hidden': 'true' }).append(
							$('<i>', { class: 'fa ' + BLOCK_ICONS[blockKey] })
						)
					);
				}
				$block.find('> hr').addClass('mk-ac-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-ac-fields-table');
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
		$('#mkAcSaveTop')
			.off('click.mkAcSave')
			.on('click.mkAcSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkAcCreate')
			.on('keydown.mkAcCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkAcFormHost').length) {
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

	window.__mkAcCreateBuild = MK_BUILD;
})($);
