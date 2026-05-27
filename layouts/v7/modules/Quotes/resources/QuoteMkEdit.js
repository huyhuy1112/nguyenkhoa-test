/**
 * Quotes Create (SALES) — premium shell; stock Inventory #EditView unchanged.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260527_quote_create2';
	var autosaveTimer;

	var BLOCK_ICONS = {
		LBL_QUOTE_INFORMATION: 'fa-info-circle',
		LBL_ADDRESS_INFORMATION: 'fa-map-marker',
		LBL_ITEM_DETAILS: 'fa-cubes',
		LBL_DESCRIPTION_INFORMATION: 'fa-align-left',
		LBL_TERMS_INFORMATION: 'fa-file-text-o'
	};

	function isScoped() {
		var body = document.body;
		return (
			body &&
			body.getAttribute('data-module') === 'Quotes' &&
			body.getAttribute('data-view') === 'Edit' &&
			(body.getAttribute('data-app') === 'SALES' || !body.getAttribute('data-app')) &&
			$('#mkQtCreateWorkspace').length &&
			!$('#mkQtFormHost input[name="record"]').val()
		);
	}

	function $form() {
		return $('#mkQtFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkQtFormHost');
		$host.find('#modnavigator, .editViewModNavigator, .module-nav').addClass('mk-qt-hide-legacy');
		$host.find('.editViewHeader').addClass('mk-qt-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-qt-form-footer');
		$host.find('.main-container').first().addClass('mk-qt-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-qt-block')) {
					return;
				}
				var blockKey = $block.attr('data-block') || '';
				$block.addClass('mk-qt-block');
				var $header = $block.find('.fieldBlockHeader').first();
				$header.addClass('mk-qt-block__header');
				if (!$header.find('.mk-qt-block__icon').length && BLOCK_ICONS[blockKey]) {
					$header.prepend(
						$('<span>', { class: 'mk-qt-block__icon', 'aria-hidden': 'true' }).append(
							$('<i>', { class: 'fa ' + BLOCK_ICONS[blockKey] })
						)
					);
				}
				$block.find('> hr').addClass('mk-qt-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-qt-fields-table');
			});

		$form().find('#lineItemTab').closest('.fieldBlockContainer').addClass('mk-qt-block mk-qt-block--line-items');
		$form().find('#lineItemResult').closest('.fieldBlockContainer').addClass('mk-qt-block mk-qt-block--totals');
	}

	function triggerSave() {
		var $save = $form().find('.saveButton').first();
		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$form().trigger('submit');
	}

	function readFieldDisplay(name) {
		var $f = $form().find('[name="' + name + '"]');
		if (!$f.length) {
			return '';
		}
		if ($f.is('select')) {
			return $.trim($f.find('option:selected').text());
		}
		if ($f.hasClass('sourceField')) {
			return $.trim($form().find('[name="' + name + '_display"]').val());
		}
		return $.trim($f.val());
	}

	function readGrandTotal() {
		var $gt = $form().find('#grandTotal, [name="hdnGrandTotal"]').first();
		if ($gt.length) {
			return $.trim($gt.val() || $gt.text());
		}
		var $net = $form().find('#netTotal').first();
		return $net.length ? $.trim($net.text() || $net.val()) : '';
	}

	function syncRail() {
		var stage = readFieldDisplay('quotestage');
		var valid = readFieldDisplay('validtill');
		var org = readFieldDisplay('account_id');
		var opp = readFieldDisplay('potential_id');
		var total = readGrandTotal();

		$('#mkQtRailStage, #mkQtHeadStageBadge').text(stage || 'Draft');
		$('#mkQtRailValidUntil').text(valid || '—');
		$('#mkQtRailOrganization').text(org || '—');
		$('#mkQtRailOpportunity').text(opp || '—');
		$('#mkQtRailTotal').text(total || '—');
	}

	function markDirty() {
		var $el = $('#mkQtAutosave');
		$el.addClass('is-dirty').removeClass('is-saved');
		$el.find('.mk-qt-autosave__text').text('Unsaved changes');
		clearTimeout(autosaveTimer);
		autosaveTimer = setTimeout(function () {
			$el.removeClass('is-dirty').addClass('is-saved');
			$el.find('.mk-qt-autosave__text').text('Ready to save');
		}, 1800);
	}

	function bindActions() {
		$('#mkQtSaveTop')
			.off('click.mkQtSave')
			.on('click.mkQtSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$('#mkQtSaveSendTop')
			.off('click.mkQtSaveSend')
			.on('click.mkQtSaveSend', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$form()
			.off('change.mkQtRail input.mkQtRail')
			.on('change.mkQtRail input.mkQtRail', 'input, select, textarea', function () {
				markDirty();
				syncRail();
			});

		$(document)
			.off('keydown.mkQtCreate')
			.on('keydown.mkQtCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkQtFormHost').length) {
						return;
					}
					e.preventDefault();
					triggerSave();
				}
			});
	}

	function observeTotals() {
		var target = $form().find('#lineItemResult, #grandTotal').get(0);
		if (!target || typeof MutationObserver === 'undefined') {
			return;
		}
		var obs = new MutationObserver(function () {
			syncRail();
		});
		obs.observe(target, { childList: true, subtree: true, characterData: true });
	}

	function initStickyHead() {
		var $head = $('#mkQtStickyHead');
		if (!$head.length) {
			return;
		}
		$(window)
			.off('scroll.mkQtSticky')
			.on('scroll.mkQtSticky', function () {
				$head.toggleClass('is-elevated', window.scrollY > 8);
			});
	}

	function runEnhancements() {
		if (!isScoped()) {
			return;
		}
		hideLegacyChrome();
		styleFieldBlocks();
		syncRail();
		bindActions();
		observeTotals();
		initStickyHead();
	}

	$(function () {
		runEnhancements();
		setTimeout(runEnhancements, 100);
		setTimeout(runEnhancements, 500);
		setTimeout(syncRail, 1200);
	});

	window.__mkQuoteCreateUi = {
		build: MK_BUILD,
		refresh: runEnhancements,
		syncRail: syncRail
	};
})(jQuery);
