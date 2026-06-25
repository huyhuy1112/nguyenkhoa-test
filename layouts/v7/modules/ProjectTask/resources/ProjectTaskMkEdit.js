/**
 * ProjectTask Create (MANAGEMENT) — dashboard shell + stock vtiger form only.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260625_ptask_save1';

	function isScoped() {
		return (
			$('body').data('module') === 'ProjectTask' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'MANAGEMENT' || !$('body').data('app')) &&
			$('#mkPtaskCreateWorkspace').length
		);
	}

	function $form() {
		return $('#mkPtaskFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkPtaskFormHost');
		$host.find('#modnavigator, .editViewModNavigator').remove();
		$host.find('.editViewHeader').addClass('mk-ptask-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-ptask-form-footer');
		$host.find('.main-container').first().addClass('mk-ptask-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-ptask-block')) {
					return;
				}
				$block.addClass('mk-ptask-block');
				$block.find('.fieldBlockHeader').first().addClass('mk-ptask-block__header');
				$block.find('> hr').addClass('mk-ptask-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-ptask-fields-table');
			});
	}

	function triggerSave() {
		var $editForm = $form();
		if (!$editForm.length) {
			return;
		}
		var $save = $editForm.find('.saveButton').first();
		var $top = $('#mkPtaskSaveTop');
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
		$editForm.off('invalid-form.validate.mkPtaskSave').on('invalid-form.validate.mkPtaskSave', function () {
			$editForm.find('.saveButton').prop('disabled', false);
			$('#mkPtaskSaveTop').prop('disabled', false);
		});
	}

	function bindActions() {
		bindSaveValidationRecovery();
		$('#mkPtaskSaveTop')
			.off('click.mkPtaskSave')
			.on('click.mkPtaskSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkPtaskCreate')
			.on('keydown.mkPtaskCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkPtaskFormHost').length) {
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

	window.__mkPtaskCreateBuild = MK_BUILD;
})($);
