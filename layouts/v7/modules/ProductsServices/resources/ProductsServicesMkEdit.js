/**
 * ProductsServices Create (SALES) — dashboard shell + stock vtiger form.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260706_ps_edit2';

	function isScoped() {
		return (
			$('body').data('module') === 'ProductsServices' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'SALES' || !$('body').data('app')) &&
			$('#mkPsCreateWorkspace').length
		);
	}

	function $form() {
		return $('#mkPsFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideExtraPriceFields() {
		// Keep only "price" (base) and hide extra pricing fields.
		var names = ['retail_price', 'wholesale_price', 'bulk_price'];
		var $f = $form();
		if (!$f.length) return;

		names.forEach(function (n) {
			// Most vtiger edit rows: <td class="fieldValue" data-fieldname="..."> ... </td>
			var $valueCell = $f.find('td.fieldValue[data-fieldname="' + n + '"]');
			if ($valueCell.length) {
				$valueCell.closest('tr').addClass('mk-ps-hide-legacy');
				return;
			}
			// Fallback: any input/select with name=field.
			var $input = $f.find('[name="' + n + '"]');
			if ($input.length) {
				$input.closest('tr').addClass('mk-ps-hide-legacy');
			}
		});
	}

	function hideLegacyChrome() {
		var $host = $('#mkPsFormHost');
		$host.find('#modnavigator, .editViewModNavigator').remove();
		$host.find('.editViewHeader').addClass('mk-ps-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-ps-form-footer');
		$host.find('.main-container').first().addClass('mk-ps-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-ps-block')) {
					return;
				}
				$block.addClass('mk-ps-block');
				$block.find('.fieldBlockHeader').first().addClass('mk-ps-block__header');
				$block.find('> hr').addClass('mk-ps-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-ps-fields-table');
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
		$('#mkPsSaveTop')
			.off('click.mkPsSave')
			.on('click.mkPsSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkPsCreate')
			.on('keydown.mkPsCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkPsFormHost').length) {
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
		hideExtraPriceFields();
		if (window.MkCurrency) {
			window.MkCurrency.applyToDom('#mkPsFormHost');
		}
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

	window.__mkPsCreateBuild = MK_BUILD;
})($);
