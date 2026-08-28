/**
 * Invoice Create (TOOLS / SUPPORT) — dashboard shell + stock Inventory #EditView unchanged.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260605_inv_create1';

	var BLOCK_ICONS = {
		LBL_INVOICE_INFORMATION: 'fa-file-text-o',
		LBL_ITEM_DETAILS: 'fa-cubes',
		LBL_ADDRESS_INFORMATION: 'fa-map-marker',
		LBL_DESCRIPTION_INFORMATION: 'fa-align-left',
		LBL_TERMS_INFORMATION: 'fa-file-text-o'
	};

	function isScoped() {
		return (
			$('body').data('module') === 'Invoice' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'TOOLS' || $('body').data('app') === 'SUPPORT') &&
			$('#mkInvCreateWorkspace').length
		);
	}

	function $form() {
		return $('#mkInvFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkInvFormHost');
		$host.find('#modnavigator, .editViewModNavigator, .module-nav').addClass('mk-inv-hide-legacy');
		$host.find('.editViewHeader').addClass('mk-inv-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-inv-form-footer');
		$host.find('.main-container').first().addClass('mk-inv-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-inv-block')) {
					return;
				}
				var blockKey = $block.attr('data-block') || '';
				$block.addClass('mk-inv-block');
				var $header = $block.find('.fieldBlockHeader').first();
				$header.addClass('mk-inv-block__header');
				if (!$header.find('.mk-inv-block__icon').length && BLOCK_ICONS[blockKey]) {
					$header.prepend(
						$('<span>', { class: 'mk-inv-block__icon', 'aria-hidden': 'true' }).append(
							$('<i>', { class: 'fa ' + BLOCK_ICONS[blockKey] })
						)
					);
				}
				$block.find('> hr').addClass('mk-inv-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-inv-fields-table');
			});

		$form().find('#lineItemTab').closest('.fieldBlockContainer').addClass('mk-inv-block mk-inv-block--line-items');
		$form().find('#lineItemResult').closest('.fieldBlockContainer').addClass('mk-inv-block mk-inv-block--totals');
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
		$('#mkInvSaveTop')
			.off('click.mkInvSave')
			.on('click.mkInvSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkInvCreate')
			.on('keydown.mkInvCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkInvFormHost').length) {
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

	window.__mkInvCreateBuild = MK_BUILD;
})($);
