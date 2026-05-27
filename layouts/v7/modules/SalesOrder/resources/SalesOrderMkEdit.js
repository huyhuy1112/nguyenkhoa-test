/**
 * SalesOrder Create (SALES) — dashboard shell + stock Inventory #EditView unchanged.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260527_so_create1';

	var BLOCK_ICONS = {
		LBL_SO_INFORMATION: 'fa-info-circle',
		LBL_ITEM_DETAILS: 'fa-cubes',
		LBL_ADDRESS_INFORMATION: 'fa-map-marker',
		LBL_DESCRIPTION_INFORMATION: 'fa-align-left',
		LBL_TERMS_INFORMATION: 'fa-file-text-o'
	};

	function isScoped() {
		return (
			$('body').data('module') === 'SalesOrder' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'SALES' || !$('body').data('app')) &&
			$('#mkSoCreateWorkspace').length &&
			!$('#mkSoFormHost input[name="record"]').val()
		);
	}

	function $form() {
		return $('#mkSoFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkSoFormHost');
		$host.find('#modnavigator, .editViewModNavigator, .module-nav').addClass('mk-so-hide-legacy');
		$host.find('.editViewHeader').addClass('mk-so-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-so-form-footer');
		$host.find('.main-container').first().addClass('mk-so-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-so-block')) {
					return;
				}
				var blockKey = $block.attr('data-block') || '';
				$block.addClass('mk-so-block');
				var $header = $block.find('.fieldBlockHeader').first();
				$header.addClass('mk-so-block__header');
				if (!$header.find('.mk-so-block__icon').length && BLOCK_ICONS[blockKey]) {
					$header.prepend(
						$('<span>', { class: 'mk-so-block__icon', 'aria-hidden': 'true' }).append(
							$('<i>', { class: 'fa ' + BLOCK_ICONS[blockKey] })
						)
					);
				}
				$block.find('> hr').addClass('mk-so-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-so-fields-table');
			});

		$form().find('#lineItemTab').closest('.fieldBlockContainer').addClass('mk-so-block mk-so-block--line-items');
		$form().find('#lineItemResult').closest('.fieldBlockContainer').addClass('mk-so-block mk-so-block--totals');
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
		$('#mkSoSaveTop')
			.off('click.mkSoSave')
			.on('click.mkSoSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkSoCreate')
			.on('keydown.mkSoCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkSoFormHost').length) {
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

	window.__mkSoCreateBuild = MK_BUILD;
})($);
