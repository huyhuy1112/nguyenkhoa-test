/**
 * SalesOrder Create (SALES) — dashboard shell + stock Inventory #EditView unchanged.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260624_so_save1';

	var BLOCK_ICONS = {
		LBL_SO_INFORMATION: 'fa-info-circle',
		LBL_ITEM_DETAILS: 'fa-cubes',
		LBL_ADDRESS_INFORMATION: 'fa-map-marker',
		LBL_DESCRIPTION_INFORMATION: 'fa-align-left',
		LBL_TERMS_INFORMATION: 'fa-file-text-o',
		'Recurring Invoice Information': 'fa-refresh'
	};

	function isScoped() {
		return (
			$('body').data('module') === 'SalesOrder' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'SALES' || !$('body').data('app')) &&
			$('#mkSoCreateWorkspace').length
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

	function markRecurringBlockCells($scope) {
		var depNames = ['recurring_frequency', 'start_period', 'end_period', 'payment_duration', 'invoicestatus'];
		var markCells = function (fieldName, cellClass) {
			var $field = $scope.find('[name="' + fieldName + '"]');
			if (!$field.length) {
				return;
			}
			var $valueCell = $field.closest('td.fieldValue');
			if ($valueCell.length) {
				$valueCell.addClass(cellClass);
				var $labelCell = $valueCell.prev('td.fieldLabel');
				if ($labelCell.length) {
					$labelCell.addClass(cellClass);
				}
			}
		};
		depNames.forEach(function (name) {
			markCells(name, 'mk-so-recurring-dependent');
		});
		markCells('enable_recurring', 'mk-so-recurring-toggle');
	}

	function initRecurringBlockUi() {
		var $block = $form().find('.fieldBlockContainer[data-block="Recurring Invoice Information"]');
		if (!$block.length) {
			return;
		}
		$block.addClass('mk-so-recurring-block');
		markRecurringBlockCells($form());
		var $enable = $form().find('[name="enable_recurring"]');
		$block.toggleClass('mk-so-recurring-on', $enable.is(':checked'));
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
		initRecurringBlockUi();
	}

	function notifySaveError(message) {
		if (typeof app !== 'undefined' && app.helper && app.helper.showErrorNotification) {
			app.helper.showErrorNotification({ message: message });
		} else {
			window.alert(message);
		}
	}

	function prepRecurringForSave($editForm) {
		var enableRec = $editForm.find('[name="enable_recurring"]');
		if (!enableRec.length || enableRec.is(':checked')) {
			return;
		}
		['recurring_frequency', 'start_period', 'end_period', 'payment_duration', 'invoicestatus'].forEach(function (name) {
			$editForm
				.find('[name="' + name + '"]')
				.addClass('ignore-validation')
				.removeAttr('data-rule-required')
				.prop('disabled', true);
		});
	}

	function triggerSave() {
		var $editForm = $form();
		if (!$editForm.length) {
			return;
		}

		prepRecurringForSave($editForm);

		if ($editForm.find('.deletedItem').length) {
			notifySaveError(app.vtranslate('JS_PLEASE_REMOVE_LINE_ITEM_THAT_IS_DELETED'));
			return;
		}
		if ($editForm.find('.lineItemRow').length <= 0) {
			notifySaveError(app.vtranslate('JS_NO_LINE_ITEM'));
			return;
		}

		var $save = $editForm.find('.saveButton').first();
		var $top = $('#mkSoSaveTop');
		$save.prop('disabled', false);
		$top.prop('disabled', false);

		var formEl = $editForm.get(0);
		if ($save.length && formEl && typeof formEl.requestSubmit === 'function') {
			try {
				formEl.requestSubmit($save.get(0));
				return;
			} catch (err) {
				/* fall through to click */
			}
		}

		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$editForm.trigger('submit');
	}

	function bindSaveValidationRecovery() {
		var $editForm = $form();
		if (!$editForm.length) {
			return;
		}
		$editForm.off('invalid-form.validate.mkSoSave').on('invalid-form.validate.mkSoSave', function () {
			$editForm.find('.saveButton').prop('disabled', false);
			$('#mkSoSaveTop').prop('disabled', false);
		});
	}

	function bindActions() {
		bindSaveValidationRecovery();
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
