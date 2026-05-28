/**
 * Contacts Create (SALES) — dashboard shell + stock Inventory #EditView unchanged.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260527_contact_create1';

	var BLOCK_ICONS = {
		LBL_CONTACT_INFORMATION: 'fa-user',
		LBL_CUSTOM_INFORMATION: 'fa-sliders',
		LBL_ADDRESS_INFORMATION: 'fa-map-marker',
		LBL_DESCRIPTION_INFORMATION: 'fa-align-left'
	};

	function isScoped() {
		return (
			$('body').data('module') === 'Contacts' &&
			$('body').data('view') === 'Edit' &&
			(
				$('body').data('app') === 'SALES' ||
				$('body').data('app') === 'MARKETING' ||
				!$('body').data('app')
			) &&
			$('#mkCtCreateWorkspace').length &&
			!$('#mkCtFormHost input[name="record"]').val()
		);
	}

	function $form() {
		return $('#mkCtFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkCtFormHost');
		// Remove modnavigator to avoid spacing/padding offsets.
		$host.find('#modnavigator').remove();
		$host.find('.editViewModNavigator, .module-nav').addClass('mk-ct-hide-legacy');
		$host.find('.editViewHeader').addClass('mk-ct-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-ct-form-footer');
		$host.find('.main-container').first().addClass('mk-ct-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-ct-block')) {
					return;
				}
				var blockKey = $block.attr('data-block') || '';
				$block.addClass('mk-ct-block');
				var $header = $block.find('.fieldBlockHeader').first();
				$header.addClass('mk-ct-block__header');
				if (!$header.find('.mk-ct-block__icon').length && BLOCK_ICONS[blockKey]) {
					$header.prepend(
						$('<span>', { class: 'mk-ct-block__icon', 'aria-hidden': 'true' }).append(
							$('<i>', { class: 'fa ' + BLOCK_ICONS[blockKey] })
						)
					);
				}
				$block.find('> hr').addClass('mk-ct-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-ct-fields-table');
			});

		$form().find('#lineItemTab').closest('.fieldBlockContainer').addClass('mk-ct-block mk-ct-block--line-items');
		$form().find('#lineItemResult').closest('.fieldBlockContainer').addClass('mk-ct-block mk-ct-block--totals');
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
		$('#mkCtSaveTop')
			.off('click.mkCtSave')
			.on('click.mkCtSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkCtCreate')
			.on('keydown.mkCtCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkCtFormHost').length) {
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

	window.__mkCtCreateBuild = MK_BUILD;
})($);
