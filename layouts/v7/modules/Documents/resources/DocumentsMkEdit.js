/**
 * Documents Edit/Create (MANAGEMENT) — dashboard shell + stock vtiger form.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260529_doc_form2';

	function isScoped() {
		return (
			$('body').data('module') === 'Documents' &&
			$('body').data('view') === 'Edit' &&
			$('body').data('app') === 'MANAGEMENT' &&
			$('#mkDocCreateWorkspace').length
		);
	}

	function $form() {
		return $('#mkDocFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideLegacyChrome() {
		var $host = $('#mkDocFormHost');
		$host.find('#modnavigator, .editViewModNavigator').remove();
		$host.find('.editViewHeader').addClass('mk-doc-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-doc-form-footer');
		$host.find('.main-container').first().addClass('mk-doc-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-doc-block')) {
					return;
				}
				$block.addClass('mk-doc-block');
				$block.find('.fieldBlockHeader').first().addClass('mk-doc-block__header');
				$block.find('> hr').addClass('mk-doc-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-doc-fields-table');
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
		$('#mkDocSaveTop')
			.off('click.mkDocSave')
			.on('click.mkDocSave', function (e) {
				e.preventDefault();
				triggerSave();
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

	window.__mkDocCreateBuild = MK_BUILD;
})($);
