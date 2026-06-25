/**
 * Project Create (MANAGEMENT) — dashboard shell + stock vtiger form only.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260625_proj_save1';

	function isScoped() {
		return (
			$('body').data('module') === 'Project' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'MANAGEMENT' || !$('body').data('app')) &&
			$('#mkProjCreateWorkspace').length
		);
	}

	function $form() {
		return $('#mkProjFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkProjFormHost');
		$host.find('#modnavigator, .editViewModNavigator').remove();
		$host.find('.editViewHeader').addClass('mk-proj-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-proj-form-footer');
		$host.find('.main-container').first().addClass('mk-proj-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-proj-block')) {
					return;
				}
				$block.addClass('mk-proj-block');
				$block.find('.fieldBlockHeader').first().addClass('mk-proj-block__header');
				$block.find('> hr').addClass('mk-proj-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-proj-fields-table');
			});
	}

	function triggerSave() {
		var $editForm = $form();
		if (!$editForm.length) {
			return;
		}
		var $save = $editForm.find('.saveButton').first();
		var $top = $('#mkProjSaveTop');
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
		$editForm.off('invalid-form.validate.mkProjSave').on('invalid-form.validate.mkProjSave', function () {
			$editForm.find('.saveButton').prop('disabled', false);
			$('#mkProjSaveTop').prop('disabled', false);
		});
	}

	function bindActions() {
		bindSaveValidationRecovery();
		$('#mkProjSaveTop')
			.off('click.mkProjSave')
			.on('click.mkProjSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkProjCreate')
			.on('keydown.mkProjCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkProjFormHost').length) {
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

	window.__mkProjCreateBuild = MK_BUILD;
})($);
