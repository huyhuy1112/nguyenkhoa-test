/**
 * SalesOrder Create (SALES) — dashboard shell + stock Inventory #EditView unchanged.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260624_so_save1';

	// Anti-FOUC: unhide once this bundle executes (CSS is already linked in pre-process).
	try {
		document.documentElement.classList.add('mk-so-create-styled');
	} catch (e) {
		/* ignore */
	}
	var TERMS_MODAL_ID = 'mkSoTermsModal';
	var TERMS_EDITOR_ID = 'mkSoTermsEditor';
	var termsModalOpen = false;

	var TERMS_CK_TOOLBAR = [
		{
			name: 'clipboard',
			items: ['Undo', 'Redo']
		},
		{
			name: 'basicstyles',
			items: ['Bold', 'Italic', 'Underline', 'Strike']
		},
		{
			name: 'paragraph',
			items: [
				'NumberedList',
				'BulletedList',
				'-',
				'Outdent',
				'Indent',
				'-',
				'JustifyLeft',
				'JustifyCenter',
				'JustifyRight',
				'JustifyBlock'
			]
		},
		{
			name: 'styles',
			items: ['Format', 'Font', 'FontSize']
		},
		{
			name: 'colors',
			items: ['TextColor', 'BGColor']
		},
		{
			name: 'insert',
			items: ['Table', 'HorizontalRule']
		},
		{
			name: 'tools',
			items: ['RemoveFormat', 'Maximize']
		}
	];

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

	function markOppCommerceRefreshOnSubmit() {
		var $editForm = $form();
		if (!$editForm.length) {
			return;
		}
		$editForm.off('submit.mkOppCommerceFlag').on('submit.mkOppCommerceFlag', function () {
			var src = ($editForm.find('input[name="sourceModule"]').val() || '').trim();
			var srcId = ($editForm.find('input[name="sourceRecord"]').val() || '').trim();
			var potId = ($editForm.find('input[name="potential_id"], input[name="potentialid"]').val() || '').trim();
			var refreshId = '';
			if (src === 'Potentials' && srcId) {
				refreshId = srcId;
			} else if (potId) {
				refreshId = potId;
			}
			if (refreshId) {
				try {
					sessionStorage.setItem('mkOppCommerceRefresh', refreshId);
				} catch (e) {
					/* ignore */
				}
			}
		});
	}

	function decodeHtmlText(value) {
		if (value === null || value === undefined) {
			return '';
		}
		var text = String(value);
		if (!text) {
			return '';
		}
		var prev;
		var i = 0;
		do {
			prev = text;
			if (typeof app !== 'undefined' && app.htmlDecode) {
				text = app.htmlDecode(text);
			} else {
				var ta = document.createElement('textarea');
				ta.innerHTML = text;
				text = ta.value;
			}
			i++;
		} while (text !== prev && /&(?:#\d+|#x[\da-fA-F]+|\w+);/.test(text) && i < 4);
		return text;
	}

	function prepareTermsHtml(html, decodeEscaped) {
		if (!html) {
			return '';
		}
		var text = String(html);
		if (decodeEscaped && /&lt;\/?[a-z]/i.test(text)) {
			text = decodeHtmlText(text);
		}
		text = text.replace(/<div>\s*<\/div>/gi, '');
		text = text.replace(/<div><br\s*\/?><\/div>/gi, '<br />');
		return text.trim();
	}

	function syncTermsPreview($ta, $preview) {
		if (!$ta.length || !$preview.length) {
			return;
		}
		var html = prepareTermsHtml($ta.val() || '', true);
		if (!$.trim(html)) {
			$preview.html('<span class="mk-so-terms-preview__placeholder">Nhấn để nhập điều khoản &amp; điều kiện…</span>');
			return;
		}
		$preview.html(html);
	}

	function destroyTermsCkEditor() {
		if (typeof CKEDITOR === 'undefined') {
			return;
		}
		var inst = CKEDITOR.instances[TERMS_EDITOR_ID];
		if (inst) {
			try {
				inst.updateElement();
				inst.destroy(true);
			} catch (e) {
				CKEDITOR.remove(inst);
			}
			delete CKEDITOR.instances[TERMS_EDITOR_ID];
		}
		$('#' + TERMS_MODAL_ID)
			.find('.cke')
			.remove();
	}

	function resetTermsEditorTextarea() {
		var $modal = $('#' + TERMS_MODAL_ID);
		var $body = $modal.find('.modal-body');
		var currentVal = '';
		var $existing = $('#' + TERMS_EDITOR_ID);
		if ($existing.length) {
			currentVal = $existing.val() || '';
		}
		$body.html('<textarea id="' + TERMS_EDITOR_ID + '" class="form-control mk-so-terms-editor-ta" rows="14"></textarea>');
		$('#' + TERMS_EDITOR_ID).val(currentVal);
	}

	function ensureTermsModal() {
		if ($('#' + TERMS_MODAL_ID).length) {
			return;
		}
		var $modal = $(
			'<div class="modal fade" id="' +
				TERMS_MODAL_ID +
				'" tabindex="-1" role="dialog" aria-labelledby="' +
				TERMS_MODAL_ID +
				'Label" aria-hidden="true">' +
				'<div class="modal-dialog modal-lg mk-so-terms-modal-dialog" role="document">' +
				'<div class="modal-content mk-so-terms-modal-content">' +
				'<div class="modal-header">' +
				'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
				'<h4 class="modal-title" id="' +
				TERMS_MODAL_ID +
				'Label">Điều khoản &amp; điều kiện</h4>' +
				'</div>' +
				'<div class="modal-body">' +
				'<textarea id="' +
				TERMS_EDITOR_ID +
				'" class="form-control" rows="14"></textarea>' +
				'</div>' +
				'<div class="modal-footer">' +
				'<button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>' +
				'<button type="button" class="btn btn-success" id="mkSoTermsSaveBtn">Lưu nội dung</button>' +
				'</div></div></div></div>'
		);
		$('body').append($modal);

		$modal.off('hidden.bs.modal.mkSoTerms').on('hidden.bs.modal.mkSoTerms', function () {
			termsModalOpen = false;
			destroyTermsCkEditor();
		});

		$modal.off('click.mkSoTermsSave', '#mkSoTermsSaveBtn').on('click.mkSoTermsSave', '#mkSoTermsSaveBtn', function () {
			var html = '';
			if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[TERMS_EDITOR_ID]) {
				html = CKEDITOR.instances[TERMS_EDITOR_ID].getData();
			} else {
				html = $('#' + TERMS_EDITOR_ID).val();
			}
			html = prepareTermsHtml(html, false);
			var $ta = $form().find('textarea[name="terms_conditions"]').first();
			if ($ta.length) {
				$ta.val(html).trigger('change');
				syncTermsPreview($ta, $ta.siblings('.mk-so-terms-preview'));
			}
			destroyTermsCkEditor();
			termsModalOpen = false;
			$modal.modal('hide');
		});
	}

	function initTermsCkEditorOnce() {
		if (typeof CKEDITOR === 'undefined' || typeof Vtiger_CkEditor_Js === 'undefined') {
			return;
		}
		if (CKEDITOR.instances[TERMS_EDITOR_ID]) {
			return;
		}
		var $editor = $('#' + TERMS_EDITOR_ID);
		if (!$editor.length) {
			return;
		}
		var ck = new Vtiger_CkEditor_Js();
		ck.loadCkEditor($editor, {
			height: 380,
			toolbar: TERMS_CK_TOOLBAR,
			fullPage: false
		});
	}

	function openTermsEditor($ta) {
		if (!$ta.length || termsModalOpen) {
			return;
		}
		ensureTermsModal();
		var $modal = $('#' + TERMS_MODAL_ID);
		if ($modal.hasClass('in') || $modal.is(':visible')) {
			return;
		}
		termsModalOpen = true;
		destroyTermsCkEditor();
		resetTermsEditorTextarea();
		var cleanVal = prepareTermsHtml($ta.val() || '', true);
		$('#' + TERMS_EDITOR_ID).val(cleanVal);

		$modal.off('shown.bs.modal.mkSoTerms').on('shown.bs.modal.mkSoTerms', function () {
			destroyTermsCkEditor();
			resetTermsEditorTextarea();
			$('#' + TERMS_EDITOR_ID).val(cleanVal);
			initTermsCkEditorOnce();
		});

		$modal.modal('show');
	}

	function initTermsRichEditor() {
		var $ta = $form().find('textarea[name="terms_conditions"]').first();
		if (!$ta.length || $ta.data('mkSoTermsReady')) {
			return;
		}
		$ta.data('mkSoTermsReady', true);

		var cleaned = prepareTermsHtml($ta.val() || '', true);
		$ta.val(cleaned);

		var $preview = $(
			'<div class="mk-so-terms-preview inputElement textAreaElement col-lg-12" role="button" tabindex="0" title="Nhấn để nhập và định dạng điều khoản"></div>'
		);
		$ta.addClass('mk-so-terms-source').attr({ 'aria-hidden': 'true', tabindex: '-1' });
		$ta.after($preview);
		syncTermsPreview($ta, $preview);

		$preview
			.off('click.mkSoTerms keydown.mkSoTerms')
			.on('click.mkSoTerms', function (e) {
				e.preventDefault();
				e.stopPropagation();
				openTermsEditor($ta);
			})
			.on('keydown.mkSoTerms', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					e.stopPropagation();
					openTermsEditor($ta);
				}
			});

		$form()
			.off('submit.mkSoTerms')
			.on('submit.mkSoTerms', function () {
				destroyTermsCkEditor();
			});
	}

	function bindActions() {
		bindSaveValidationRecovery();
		markOppCommerceRefreshOnSubmit();
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
		initTermsRichEditor();
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
