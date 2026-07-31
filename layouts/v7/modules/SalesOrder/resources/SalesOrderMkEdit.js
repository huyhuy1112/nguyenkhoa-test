/**
 * SalesOrder Create (SALES) — dashboard shell + stock Inventory #EditView unchanged.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260724_so_quote_ui5';
	var WAREHOUSE_MODAL_ID = 'mkSoWarehouseModal';
	var warehouseConfirmed = false;
	var warehousePickerOpen = false;

	function revealPage() {
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				document.documentElement.classList.add('mk-so-create-styled');
				if (!document.documentElement.classList.contains('mk-inv-ui-ready')) {
					document.documentElement.classList.add('mk-inv-ui-ready', 'mk-quote-create-enhanced');
				}
			});
		});
	}

	/** True when create uses Quote create shell (70/30 grid + #mkSoOrderRail). */
	function usesQuoteShell() {
		return $('#mkSoOrderRail').length > 0 && $('#mkSoCreateWorkspace').hasClass('mk-qt-create');
	}

	var TERMS_MODAL_ID = 'mkSoTermsModal';
	var TERMS_EDITOR_ID = 'mkSoTermsEditor';
	var termsModalOpen = false;
	var DEFAULT_DELIVERY_NOTE_TEXT = [
		'- Khi nhận hàng: Nếu có sai lệch về số lượng kiện hàng thực tế so với PGH / phiếu giao nhận của dịch vụ vận chuyển, hãy liên hệ ngay với NVKD để được giải quyết (chúng tôi chỉ giải quyết khiếu nại về giao nhận kiện hàng trong ngày Quý khách nhận được hàng).',
		'- Về đơn hàng: Chúng tôi chỉ giải quyết khiếu nại trong vòng 3 ngày kể từ ngày Quý khách nhận được hàng (Bao gồm tất cả các trường hợp về số lượng sản phẩm, tình trạng hàng hóa như: vỡ hỏng, móp méo, lỗi). Quý khách hãy cung cấp hình ảnh, video hàng hóa thực nhận cho NVKD để khiếu nại.',
		'',
		'Mọi ý kiến đóng góp của Quý khách về chất lượng sản phẩm, dịch vụ xin vui lòng liên hệ SĐT 0964.468.929.'
	].join('\n');

	var TERMS_CK_TOOLBAR = [
		{
			name: 'document',
			items: ['Source', '-', 'Preview', 'Print']
		},
		{
			name: 'clipboard',
			items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']
		},
		{
			name: 'editing',
			items: ['Find', 'Replace', '-', 'SelectAll']
		},
		{
			name: 'basicstyles',
			items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']
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
				'Blockquote',
				'CreateDiv',
				'-',
				'JustifyLeft',
				'JustifyCenter',
				'JustifyRight',
				'JustifyBlock'
			]
		},
		{
			name: 'links',
			items: ['Link', 'Unlink', 'Anchor']
		},
		{
			name: 'insert',
			items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar', 'PageBreak']
		},
		{
			name: 'styles',
			items: ['Styles', 'Format', 'Font', 'FontSize']
		},
		{
			name: 'colors',
			items: ['TextColor', 'BGColor']
		},
		{
			name: 'tools',
			items: ['Maximize', 'ShowBlocks']
		}
	];

	var DESC_MODAL_ID = 'mkSoDescModal';
	var DESC_EDITOR_ID = 'mkSoDescEditor';
	var descModalOpen = false;

	var BLOCK_ICONS = {
		LBL_SO_INFORMATION: 'fa-info-circle',
		LBL_ITEM_DETAILS: 'fa-cubes',
		LBL_ADDRESS_INFORMATION: 'fa-map-marker',
		LBL_DESCRIPTION_INFORMATION: 'fa-align-left',
		LBL_TERMS_INFORMATION: 'fa-file-text-o',
		'Recurring Invoice Information': 'fa-refresh'
	};

	function isScoped() {
		var $body = $('body');
		var mod = $body.attr('data-module') || $body.data('module');
		var view = $body.attr('data-view') || $body.data('view');
		var app = ($body.attr('data-app') || $body.data('app') || '').toString().toUpperCase();
		return (
			mod === 'SalesOrder' &&
			view === 'Edit' &&
			(app === 'SALES' || app === '') &&
			$('#mkSoCreateWorkspace').length > 0
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

				// Highlight payment method field row
				$block
					.find('select[name="mk_payment_method"], select[name="mk_payment_terms"], select[name="payment_duration"]')
					.each(function () {
						var $row = $(this).closest('tr');
						if ($row.length) {
							$row.addClass('mk-so-payment-method');
						}
					});
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

	function isCreateMode() {
		var recordId = ($form().find('input[name="record"]').val() || '').trim();
		return !recordId;
	}

	function collectLineItemsForStock() {
		var productIds = [];
		var productNames = [];
		var quantities = [];
		$form()
			.find('tr.lineItemRow')
			.each(function () {
				var $row = $(this);
				var pid = parseInt($row.find('input.selectedModuleId').val(), 10) || 0;
				var name = ($row.find('input.productName').val() || '').trim();
				var qty = parseFloat($row.find('.qty').val()) || 0;
				if (qty <= 0 || (!pid && !name)) {
					return;
				}
				productIds.push(pid);
				productNames.push(name);
				quantities.push(qty);
			});
		return { productIds: productIds, productNames: productNames, quantities: quantities };
	}

	function ensureWarehouseModal() {
		if ($('#' + WAREHOUSE_MODAL_ID).length) {
			return;
		}
		var $modal = $(
			'<div class="modal fade mk-so-wh-modal" id="' +
				WAREHOUSE_MODAL_ID +
				'" tabindex="-1" role="dialog" aria-hidden="true">' +
				'<div class="modal-dialog modal-dialog-centered">' +
				'<div class="modal-content">' +
				'<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>' +
				'<h4 class="modal-title">Chọn kho xuất hàng</h4></div>' +
				'<div class="modal-body"><p class="mk-so-wh-modal__lead">Chọn kho để tạo phiếu xuất ở trạng thái <strong>Chờ in phiếu</strong>.</p>' +
				'<div class="mk-so-wh-modal__list" id="mkSoWarehouseList"></div>' +
				'<div class="mk-so-wh-modal__error hide" id="mkSoWarehouseError"></div></div>' +
				'<div class="modal-footer">' +
				'<button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>' +
				'<button type="button" class="btn btn-success" id="mkSoWarehouseConfirm" disabled>Xác nhận &amp; lưu</button>' +
				'</div></div></div></div>'
		);
		$('body').append($modal);
	}

	function decodeEntities(s) {
		var text = String(s || '');
		if (/&(?:#x?[0-9a-f]+|[a-z]+);/i.test(text)) {
			var el = document.createElement('textarea');
			el.innerHTML = text;
			text = el.value;
		}
		return text;
	}

	function renderWarehouseOptions(warehouses) {
		var $list = $('#mkSoWarehouseList');
		$list.empty();
		warehouses.forEach(function (wh, idx) {
			var id = 'mkSoWh_' + wh.id;
			$list.append(
				$('<label class="mk-so-wh-option"></label>')
					.append(
						$('<input type="radio" name="mk_so_warehouse_pick" />')
							.attr('id', id)
							.attr('value', wh.id)
							.prop('checked', idx === 0)
					)
					.append(
						$('<span class="mk-so-wh-option__body"></span>')
							.append($('<strong></strong>').text(decodeEntities(wh.name)))
							.append($('<small></small>').text(decodeEntities(wh.address || wh.code)))
					)
			);
		});
		$('#mkSoWarehouseConfirm').prop('disabled', warehouses.length === 0);
	}

	function openWarehousePicker(onConfirm) {
		if (warehousePickerOpen) {
			return;
		}
		ensureWarehouseModal();
		warehousePickerOpen = true;
		var $modal = $('#' + WAREHOUSE_MODAL_ID);
		$('#mkSoWarehouseError').addClass('hide').empty();
		$modal.off('hidden.bs.modal.mkSoWh').on('hidden.bs.modal.mkSoWh', function () {
			warehousePickerOpen = false;
		});
		app.request.get({ url: 'index.php?module=SalesOrder&action=WarehouseList' }).then(function (err, res) {
			var warehouses = (res && res.warehouses) || [];
			renderWarehouseOptions(warehouses);
			$modal.modal('show');
			$('#mkSoWarehouseConfirm')
				.off('click.mkSoWh')
				.on('click.mkSoWh', function () {
					var warehouseId = $('input[name="mk_so_warehouse_pick"]:checked').val();
					var warehouse = null;
					warehouses.forEach(function (wh) {
						if (wh.id === warehouseId) {
							warehouse = wh;
						}
					});
					if (!warehouse) {
						$('#mkSoWarehouseError').removeClass('hide').text('Vui lòng chọn kho.');
						return;
					}
					var lines = collectLineItemsForStock();
					app.request
						.post({
							data: {
								module: 'SalesOrder',
								action: 'CheckWarehouseStock',
								warehouse_id: warehouse.id,
								product_id: lines.productIds,
								product_name: lines.productNames,
								quantity: lines.quantities
							}
						})
						.then(function (stockErr, stockRes) {
							if (stockErr || !stockRes || !stockRes.success) {
								var msg =
									stockRes && stockRes.errors && stockRes.errors.length
										? stockRes.errors.join('\n')
										: 'Tồn kho không đủ để đặt hàng.';
								$('#mkSoWarehouseError').removeClass('hide').text(msg);
								return;
							}
							$form().find('input[name="mk_warehouse_id"], input[name="mk_warehouse_name"]').remove();
							$form().append(
								$('<input type="hidden" name="mk_warehouse_id" />').val(warehouse.id)
							);
							$form().append(
								$('<input type="hidden" name="mk_warehouse_name" />').val(warehouse.name)
							);
							$form().trigger('mkSoWarehouseSelected', [warehouse]);
							warehouseConfirmed = true;
							$modal.modal('hide');
							if (typeof onConfirm === 'function') {
								onConfirm(warehouse);
							}
						});
				});
		});
	}

	function doActualSave() {
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
				/* fall through */
			}
		}

		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$editForm.trigger('submit');
	}

	function hasRequiredQuote() {
		var $editForm = $form();
		if (!$editForm.length) {
			return false;
		}
		if (!isCreateMode()) {
			return true;
		}
		return (parseInt($editForm.find('[name="quote_id"]').val(), 10) || 0) > 0;
	}

	function notifyQuoteRequired() {
		var msg = 'Vui lòng chọn Báo giá trước khi tạo đơn hàng.';
		try {
			if (window.app && app.helper && app.helper.showErrorNotification) {
				app.helper.showErrorNotification({ message: msg });
			} else {
				window.alert(msg);
			}
		} catch (err) {
			window.alert(msg);
		}
		$form().find('[name="quote_id_display"]').trigger('focus');
	}

	function triggerSave() {
		var $editForm = $form();
		if (!$editForm.length) {
			return;
		}
		if (!hasRequiredQuote()) {
			notifyQuoteRequired();
			return;
		}
		// Create stays as Phiếu tạm — warehouse outbound is created on confirm, not on save.
		doActualSave();
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
				'<button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>' +
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

	function syncDescPreview($ta, $preview) {
		if (!$ta.length || !$preview.length) {
			return;
		}
		var html = prepareTermsHtml($ta.val() || '', true);
		if (!$.trim($('<div/>').html(html).text())) {
			$preview.html(
				'<span class="mk-so-desc-preview__placeholder">Nhấn để soạn Ghi chú hợp đồng (trình soạn thảo kiểu Word)…</span>'
			);
			return;
		}
		$preview.html(html);
	}

	function destroyDescCkEditor() {
		if (typeof CKEDITOR === 'undefined') {
			return;
		}
		var inst = CKEDITOR.instances[DESC_EDITOR_ID];
		if (inst) {
			try {
				inst.updateElement();
				inst.destroy(true);
			} catch (e) {
				CKEDITOR.remove(inst);
			}
			delete CKEDITOR.instances[DESC_EDITOR_ID];
		}
		$('#' + DESC_MODAL_ID)
			.find('.cke')
			.remove();
	}

	function resetDescEditorTextarea() {
		var $modal = $('#' + DESC_MODAL_ID);
		var $body = $modal.find('.modal-body');
		var currentVal = '';
		var $existing = $('#' + DESC_EDITOR_ID);
		if ($existing.length) {
			currentVal = $existing.val() || '';
		}
		$body.html(
			'<textarea id="' +
				DESC_EDITOR_ID +
				'" class="form-control mk-so-desc-editor-ta" rows="18"></textarea>'
		);
		$('#' + DESC_EDITOR_ID).val(currentVal);
	}

	function ensureDescModal() {
		if ($('#' + DESC_MODAL_ID).length) {
			return;
		}
		var $modal = $(
			'<div class="modal fade" id="' +
				DESC_MODAL_ID +
				'" tabindex="-1" role="dialog" aria-labelledby="' +
				DESC_MODAL_ID +
				'Label" aria-hidden="true">' +
				'<div class="modal-dialog modal-lg mk-so-desc-modal-dialog" role="document">' +
				'<div class="modal-content mk-so-desc-modal-content">' +
				'<div class="modal-header">' +
				'<button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>' +
				'<h4 class="modal-title" id="' +
				DESC_MODAL_ID +
				'Label">Ghi chú hợp đồng</h4>' +
				'</div>' +
				'<div class="modal-body">' +
				'<textarea id="' +
				DESC_EDITOR_ID +
				'" class="form-control" rows="18"></textarea>' +
				'</div>' +
				'<div class="modal-footer">' +
				'<button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>' +
				'<button type="button" class="btn btn-success" id="mkSoDescSaveBtn">Lưu nội dung</button>' +
				'</div></div></div></div>'
		);
		$('body').append($modal);

		$modal.off('hidden.bs.modal.mkSoDesc').on('hidden.bs.modal.mkSoDesc', function () {
			descModalOpen = false;
			destroyDescCkEditor();
		});

		$modal.off('click.mkSoDescSave', '#mkSoDescSaveBtn').on('click.mkSoDescSave', '#mkSoDescSaveBtn', function () {
			var html = '';
			if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[DESC_EDITOR_ID]) {
				html = CKEDITOR.instances[DESC_EDITOR_ID].getData();
			} else {
				html = $('#' + DESC_EDITOR_ID).val();
			}
			html = prepareTermsHtml(html, false);
			var $ta = $form().find('textarea[name="description"]').first();
			if ($ta.length) {
				$ta.val(html).trigger('change');
				syncDescPreview($ta, $ta.siblings('.mk-so-desc-preview'));
			}
			destroyDescCkEditor();
			descModalOpen = false;
			$modal.modal('hide');
		});
	}

	function initDescCkEditorOnce() {
		if (typeof CKEDITOR === 'undefined' || typeof Vtiger_CkEditor_Js === 'undefined') {
			return;
		}
		if (CKEDITOR.instances[DESC_EDITOR_ID]) {
			return;
		}
		var $editor = $('#' + DESC_EDITOR_ID);
		if (!$editor.length) {
			return;
		}
		var ck = new Vtiger_CkEditor_Js();
		ck.loadCkEditor($editor, {
			height: 460,
			toolbar: TERMS_CK_TOOLBAR,
			fullPage: false,
			allowedContent: true,
			extraPlugins: 'justify,font,colorbutton,colordialog,find,print,pagebreak'
		});
	}

	function openDescEditor($ta) {
		if (!$ta.length || descModalOpen) {
			return;
		}
		ensureDescModal();
		var $modal = $('#' + DESC_MODAL_ID);
		if ($modal.hasClass('in') || $modal.is(':visible')) {
			return;
		}
		descModalOpen = true;
		destroyDescCkEditor();
		resetDescEditorTextarea();
		var cleanVal = prepareTermsHtml($ta.val() || '', true);
		$('#' + DESC_EDITOR_ID).val(cleanVal);

		$modal.off('shown.bs.modal.mkSoDesc').on('shown.bs.modal.mkSoDesc', function () {
			destroyDescCkEditor();
			resetDescEditorTextarea();
			$('#' + DESC_EDITOR_ID).val(cleanVal);
			initDescCkEditorOnce();
		});

		$modal.modal('show');
	}

	function initDescriptionRichEditor() {
		var $ta = $form().find('textarea[name="description"]').first();
		if (!$ta.length || $ta.data('mkSoDescReady')) {
			return;
		}
		$ta.data('mkSoDescReady', true);

		var cleaned = prepareTermsHtml($ta.val() || '', true);
		$ta.val(cleaned);

		var $preview = $(
			'<div class="mk-so-desc-preview inputElement textAreaElement col-lg-12" role="button" tabindex="0" title="Nhấn để soạn Ghi chú hợp đồng"></div>'
		);
		$ta.addClass('mk-so-desc-source').attr({ 'aria-hidden': 'true', tabindex: '-1' });
		$ta.after($preview);
		syncDescPreview($ta, $preview);

		$preview
			.off('click.mkSoDesc keydown.mkSoDesc')
			.on('click.mkSoDesc', function (e) {
				e.preventDefault();
				e.stopPropagation();
				openDescEditor($ta);
			})
			.on('keydown.mkSoDesc', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					e.stopPropagation();
					openDescEditor($ta);
				}
			});

		$form()
			.off('submit.mkSoDesc')
			.on('submit.mkSoDesc', function () {
				destroyDescCkEditor();
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

	function fixFormDisplayEncoding() {
		$form()
			.find('[name$="_display"], .sourceField')
			.each(function () {
				var $el = $(this);
				var val = $el.val();
				if (val && /&/.test(val)) {
					var decoded = decodeHtmlText(val);
					if (decoded !== val) {
						$el.val(decoded);
					}
				}
			});

		$form()
			.find('input[name="subject"]')
			.each(function () {
				var $el = $(this);
				var val = $el.val();
				if (val && /&/.test(val)) {
					var decoded = decodeHtmlText(val);
					if (decoded !== val) {
						$el.val(decoded);
					}
				}
			});
	}

	function ensureMandatoryHiddenDefaults() {
		var $editForm = $form();
		if (!$editForm.length) {
			return;
		}
		// Subject is mandatory in vtiger Inventory modules; keep it filled from quote name (field is hidden).
		syncSubjectFromQuote();
		var $subject = $editForm.find('input[name="subject"]').first();
		if ($subject.length && !$.trim($subject.val())) {
			$subject.val('Đơn hàng');
		}
		// Assigned user is mandatory; always default to the current user (no UI picker).
		var $assigned = $editForm.find('[name="assigned_user_id"]').first();
		if ($assigned.length) {
			var uid = '';
			try {
				if (window._USERMETA && _USERMETA.id) {
					uid = String(_USERMETA.id);
				} else if (window.app && typeof app.getUserId === 'function') {
					uid = String(app.getUserId() || '');
				}
			} catch (e) {}
			if (!uid) {
				uid = String($assigned.val() || $editForm.find('[name="assigned_user_id"] option').first().val() || '');
			}
			if (uid) {
				if ($assigned.is('select') && !$assigned.find('option[value="' + uid + '"]').length) {
					$assigned.append($('<option/>', { value: uid, text: uid }));
				}
				$assigned.val(uid);
			}
		}
		// Keep subject synced from quote label when contact/account changes (prefer quote).
		$editForm
			.off('change.mkSoSubjectSync', '[name="quote_id_display"], [name="contact_id_display"], [name="account_id_display"]')
			.on('change.mkSoSubjectSync', '[name="quote_id_display"], [name="contact_id_display"], [name="account_id_display"]', function () {
				syncSubjectFromQuote();
			});
	}

	function ensureCreateStatusPhieuTam() {
		if (!isCreateMode()) {
			return;
		}
		var $editForm = $form();
		var $status = $editForm.find('[name="sostatus"]').first();
		if (!$status.length) {
			return;
		}

		// Prefer picklist value "Created" (UI: Phiếu tạm); fall back to Vietnamese aliases.
		var preferred = ['Created', 'Phiếu tạm', 'Đã tạo', 'Draft'];
		var pick = '';
		if ($status.is('select')) {
			preferred.forEach(function (cand) {
				if (pick) {
					return;
				}
				$status.find('option').each(function () {
					var v = String($(this).attr('value') || '');
					var t = $.trim($(this).text() || '');
					if (
						v === cand ||
						t === cand ||
						v.toLowerCase() === cand.toLowerCase() ||
						t.toLowerCase() === cand.toLowerCase()
					) {
						pick = v || cand;
						return false;
					}
					return true;
				});
			});
		}
		if (!pick) {
			pick = 'Created';
		}
		$status.val(pick);
		if ($status.is('select')) {
			$status.find('option').each(function () {
				if (String($(this).attr('value') || '') === pick) {
					$(this).prop('selected', true);
				}
			});
		}
		$status.trigger('change');

		// Hide status on create — still submit as Phiếu tạm.
		var $valueTd = $status.closest('td.fieldValue');
		if ($valueTd.length) {
			$valueTd.addClass('mk-so-hide-legacy');
			$valueTd.prev('td.fieldLabel').addClass('mk-so-hide-legacy');
			$valueTd.closest('tr').addClass('mk-so-hide-legacy');
		} else {
			$status.closest('tr').addClass('mk-so-hide-legacy');
		}
	}

	function bindActions() {
		bindSaveValidationRecovery();
		markOppCommerceRefreshOnSubmit();
		var $editForm = $form();
		// Create = Phiếu tạm only; warehouse picker runs on confirm, not on save.
		$editForm.off('submit.mkSoWarehouse');
		$editForm.find('.saveButton').off('click.mkSoWarehouse');
		ensureCreateStatusPhieuTam();

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

	function initOdooInventoryUi() {
		if (window.MkInventoryOdooEdit && window.MkInventoryOdooEdit.init) {
			window.MkInventoryOdooEdit.init($form(), { hideDescriptionBlock: false });
		}
	}

	function ensureListNoteField($editForm) {
		if (!$editForm || !$editForm.length) {
			return;
		}
		var $existing = $editForm.find('textarea[name="mk_list_note"]').first();
		var $infoBlock = $editForm.find('.fieldBlockContainer[data-block="LBL_SO_INFORMATION"]').first();
		var $infoTable = $infoBlock.find('table.table-borderless > tbody').first();
		if (!$infoTable.length) {
			return;
		}

		if ($existing.length) {
			var $row = $existing.closest('tr');
			showFieldPairFor($existing.closest('td.fieldValue'));
			$row.removeClass('mk-so-hide-legacy mk-inv-hide-legacy');
			if (!$infoBlock.find('textarea[name="mk_list_note"]').length) {
				$infoTable.append($row);
			}
			$row.find('td.fieldLabel label').first().text('Ghi chú');
			$existing.attr('placeholder', 'Ghi chú hiển thị trên list…');
			$existing.closest('td.fieldValue').addClass('fieldValueWidth80');
			return;
		}

		var $row = $(
			'<tr class="mk-so-list-note-row">' +
				'<td class="fieldLabel">' +
				'<label class="muted pull-right marginRight10px">Ghi chú</label>' +
				'</td>' +
				'<td class="fieldValue fieldValueWidth80">' +
				'<textarea class="inputElement textAreaElement col-lg-12" name="mk_list_note" rows="3" placeholder="Ghi chú hiển thị trên list…"></textarea>' +
				'</td>' +
				'</tr>'
		);
		$infoTable.append($row);
	}

	function showFieldPairFor($valueTd) {
		if (!$valueTd || !$valueTd.length) {
			return;
		}
		$valueTd.removeClass('mk-so-hide-legacy mk-inv-hide-legacy');
		var $label = $valueTd.prev('td.fieldLabel');
		if ($label.length) {
			$label.removeClass('mk-so-hide-legacy mk-inv-hide-legacy');
		}
	}

	function simplifySalesOrderForm() {
		$form().data('mkSoSimplified', true);
		var $editForm = $form();
		if (!$editForm.length) {
			return;
		}

		// Only: Báo giá, Ghi chú hợp đồng, Ghi chú (list) + Chi tiết đơn hàng.
		// Subject stays in DOM (mandatory) but hidden — auto-filled from quote name.
		// Trạng thái hidden on create & edit (create still auto-sets Phiếu tạm in background).
		var allowNames = {
			quote_id: true,
			quote_id_display: true,
			description: true,
			mk_list_note: true
		};

		function fieldNameAllowed(name) {
			if (!name) {
				return false;
			}
			name = String(name).replace(/\[\]$/, '');
			return !!(allowNames[name] || allowNames[name.replace(/_display$/, '')]);
		}

		function hideFieldPair($valueTd) {
			if (!$valueTd || !$valueTd.length) {
				return;
			}
			$valueTd.addClass('mk-so-hide-legacy');
			var $label = $valueTd.prev('td.fieldLabel');
			if ($label.length) {
				$label.addClass('mk-so-hide-legacy');
			}
		}

		function showFieldPair($valueTd) {
			if (!$valueTd || !$valueTd.length) {
				return;
			}
			$valueTd.removeClass('mk-so-hide-legacy mk-inv-hide-legacy');
			var $label = $valueTd.prev('td.fieldLabel');
			if ($label.length) {
				$label.removeClass('mk-so-hide-legacy mk-inv-hide-legacy');
			}
		}

		// Hide blocks we never want on create/edit.
		$editForm
			.find(
				'.fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"],' +
					'.fieldBlockContainer[data-block="LBL_TERMS_INFORMATION"],' +
					'.fieldBlockContainer[data-block="Recurring Invoice Information"]'
			)
			.addClass('mk-so-hide-legacy');

		// Hide field pairs (label+value) that are not in the allowlist.
		// Important: Vtiger puts 2 fields per row — do NOT keep a whole row just because one field is allowed.
		$editForm.find('td.fieldValue').each(function () {
			var $valueTd = $(this);
			if ($valueTd.closest('#lineItemTab, #lineItemResult, .lineItemTable, .lineitemTableContainer').length) {
				return;
			}
			var keep = false;
			$valueTd.find('input[name], select[name], textarea[name]').each(function () {
				if (fieldNameAllowed($(this).attr('name'))) {
					keep = true;
					return false;
				}
				return true;
			});
			if (keep) {
				showFieldPair($valueTd);
			} else {
				hideFieldPair($valueTd);
			}
		});

		// Extra safety: hide by known junk field names + label text from screenshots.
		var forceHideNames = [
			'customerno',
			'purchaseorder',
			'pending',
			'exciseduty',
			'purpose',
			'internal_cost',
			'needed_time',
			'internal_order_status',
			'created_user_id',
			'approved_by',
			'approval_note',
			'team_group',
			'leadsource',
			'lead_id',
			'leadid',
			'account_id',
			'contact_id',
			'potential_id',
			'carrier',
			'shipping',
			'salescommission',
			'duedate',
			'currency_id',
			'conversion_rate',
			'assigned_user_id',
			'enable_recurring',
			'sostatus',
			'subject'
		];
		forceHideNames.forEach(function (name) {
			$editForm.find('[name="' + name + '"], [name="' + name + '_display"]').each(function () {
				hideFieldPair($(this).closest('td.fieldValue'));
			});
		});
		var hideLabelRe = /mã\s*số\s*khách\s*hàng|mua\s*đặt\s*hàng|đang\s*chờ\s*xử\s*lý|excise\s*duty|mục\s*đích|remaining\s*amount|^lead$|người\s*đặt|người\s*duyệt|ghi\s*chú\s*duyệt|chi\s*phí|thời\s*điểm\s*cần|tên\s*cơ\s*hội|để\s*mục\s*cơ\s*hội|^tiêu\s*đề$/i;
		$editForm.find('td.fieldLabel').each(function () {
			var $labelTd = $(this);
			var text = $.trim($labelTd.text() || '').replace(/\*/g, '');
			if (!text || !hideLabelRe.test(text)) {
				return;
			}
			// Keep our Ghi chú label (description) — only hide "Remaining Amount" style junk labels before rename.
			if (/^ghi\s*chú$/i.test(text)) {
				return;
			}
			var $valueTd = $labelTd.next('td.fieldValue');
			if ($valueTd.find('[name="description"], [name="mk_list_note"], [name="quote_id"], [name="quote_id_display"]').length) {
				return;
			}
			$labelTd.addClass('mk-so-hide-legacy');
			$valueTd.addClass('mk-so-hide-legacy');
		});

		// Hide empty leftover rows (all cells hidden / no visible fields).
		$editForm.find('table.table-borderless > tbody > tr').each(function () {
			var $tr = $(this);
			if ($tr.closest('#lineItemTab, #lineItemResult').length) {
				return;
			}
			var $values = $tr.children('td.fieldValue');
			if (!$values.length) {
				return;
			}
			var anyVisible = false;
			$values.each(function () {
				if (!$(this).hasClass('mk-so-hide-legacy')) {
					anyVisible = true;
					return false;
				}
				return true;
			});
			$tr.toggleClass('mk-so-hide-legacy', !anyVisible);
		});

		// Keep SO info + line items blocks; hide other blocks with no allowed fields.
		$editForm.find('.fieldBlockContainer[data-block]').each(function () {
			var $block = $(this);
			if ($block.find('#lineItemTab, #lineItemResult').length) {
				$block.removeClass('mk-so-hide-legacy');
				return;
			}
			var blockKey = $block.attr('data-block') || '';
			if (blockKey === 'LBL_SO_INFORMATION') {
				$block.removeClass('mk-so-hide-legacy');
				return;
			}
			if (blockKey === 'LBL_DESCRIPTION_INFORMATION') {
				// description row is moved into SO info; hide empty block
				$block.addClass('mk-so-hide-legacy');
				return;
			}
			var hasAllowed = false;
			$block.find('input[name], select[name], textarea[name]').each(function () {
				if (fieldNameAllowed($(this).attr('name'))) {
					hasAllowed = true;
					return false;
				}
				return true;
			});
			$block.toggleClass('mk-so-hide-legacy', !hasAllowed);
		});

		// Move Ghi chú into SO info card (Odoo CSS hides LBL_DESCRIPTION_INFORMATION).
		var $desc = $editForm.find('textarea[name="description"]').first();
		if ($desc.length) {
			var $descRow = $desc.closest('tr');
			var $descBlock = $desc.closest('.fieldBlockContainer');
			var $infoBlock = $editForm.find('.fieldBlockContainer[data-block="LBL_SO_INFORMATION"]').first();
			showFieldPair($desc.closest('td.fieldValue'));
			$descRow.removeClass('mk-so-hide-legacy mk-inv-hide-legacy');
			if ($infoBlock.length && $descRow.length && !$infoBlock.find('textarea[name="description"]').length) {
				var $infoTable = $infoBlock.find('table.table-borderless > tbody').first();
				if ($infoTable.length) {
					$infoTable.append($descRow);
				}
			}
			if ($descBlock.length && $descBlock.attr('data-block') === 'LBL_DESCRIPTION_INFORMATION') {
				$descBlock.addClass('mk-so-hide-legacy mk-inv-hide-legacy');
			}
			$descRow.find('td.fieldLabel label').first().text('Ghi chú hợp đồng');
			$desc.closest('td.fieldValue').addClass('fieldValueWidth80');
			// Keep BA delivery-note policy prefilled for new/empty notes.
			var currentDesc = String($desc.val() || '').trim();
			if (!currentDesc) {
				$desc.val(DEFAULT_DELIVERY_NOTE_TEXT);
			}
		}

		ensureListNoteField($editForm);

		// Always hide Trạng thái on this SO create/edit shell.
		$editForm.find('[name="sostatus"]').each(function () {
			hideFieldPair($(this).closest('td.fieldValue'));
		});

		var labelMap = {
			subject: 'Tiêu đề',
			quote_id: 'Báo giá',
			quote_id_display: 'Báo giá',
			description: 'Ghi chú hợp đồng',
			mk_list_note: 'Ghi chú'
		};
		Object.keys(labelMap).forEach(function (name) {
			$editForm
				.find('[name="' + name + '"]')
				.closest('td.fieldValue')
				.prev('td.fieldLabel')
				.find('label')
				.first()
				.each(function () {
					$(this).text(labelMap[name]);
				});
		});

		var $lineHeader = $editForm
			.find('#lineItemTab')
			.closest('.fieldBlockContainer')
			.find('.fieldBlockHeader')
			.first();
		if ($lineHeader.length) {
			var $lineIcon = $lineHeader.find('.mk-so-block__icon').detach();
			$lineHeader.empty();
			if ($lineIcon.length) {
				$lineHeader.append($lineIcon);
			}
			$lineHeader.append(document.createTextNode(' Chi tiết đơn hàng'));
		}
	}

	function pinAddProductToLineHeader() {
		var $editForm = $form();
		var $lineBlock = $editForm.find('#lineItemTab').closest('.fieldBlockContainer');
		if (!$lineBlock.length) {
			return;
		}
		var $tabs = $lineBlock.find('.mk-inv-odoo-tabs').first();
		var $addBtn = $editForm.find('#addProductsServices').first();
		if (!$tabs.length || !$addBtn.length) {
			return;
		}
		var $actions = $tabs.find('.mk-inv-line-header-actions, .mk-qt-line-actions').first();
		if (!$actions.length) {
			$actions = $('<div class="mk-inv-line-header-actions mk-qt-line-actions" aria-label="Thao tác dòng sản phẩm"></div>');
			$tabs.append($actions);
		}
		if (!$addBtn.closest('.mk-inv-line-header-actions, .mk-qt-line-actions').length) {
			$actions.append($addBtn.detach());
		}
		// Quick search replaces the visible add button.
		$addBtn.addClass('mk-inv-add-line-btn--hidden').attr('aria-hidden', 'true');
		if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.initQuickProductSearch === 'function') {
			window.MkInventoryOdooEdit.initQuickProductSearch($editForm);
		}
	}

	function syncSubjectFromQuote() {
		var $editForm = $form();
		var $subject = $editForm.find('input[name="subject"]').first();
		if (!$subject.length) {
			return;
		}
		var quoteLabel = $.trim($editForm.find('[name="quote_id_display"]').val() || '');
		if (quoteLabel) {
			$subject.val(quoteLabel);
			return;
		}
		if (!$.trim($subject.val())) {
			$subject.val('Đơn hàng');
		}
	}

	function polishQuoteReferenceField() {
		var $editForm = $form();
		var $display = $editForm.find('[name="quote_id_display"]').first();
		if (!$display.length) {
			return;
		}
		var $ref = $display.closest('.referencefield-wrapper');
		if (!$ref.length) {
			$ref = $display.closest('td.fieldValue').find('.referencefield-wrapper').first();
		}
		$ref.addClass('mk-qt-opp-ref mk-so-quote-ref');
		$display
			.attr('placeholder', 'Chọn báo giá để tạo đơn hàng...')
			.attr('data-rule-required', 'true')
			.addClass('required');
		$editForm.find('[name="quote_id"]').attr('data-rule-required', 'true').addClass('required');
		$editForm
			.find('[name="quote_id"]')
			.closest('td.fieldValue')
			.prev('td.fieldLabel')
			.find('label')
			.first()
			.html('Tên báo giá <span class="redColor">*</span>');

		// Quote is the primary field — show its row first in the rail info card.
		var $quoteValue = $editForm.find('[name="quote_id"]').closest('td.fieldValue');
		var $quoteRow = $quoteValue.closest('tr');
		var $infoTable = $editForm
			.find('.fieldBlockContainer[data-block="LBL_SO_INFORMATION"] table.table-borderless > tbody')
			.first();
		if ($quoteRow.length && $infoTable.length && $infoTable.children('tr').first()[0] !== $quoteRow[0]) {
			$infoTable.prepend($quoteRow);
		}

		var $group = $ref.find('.input-group').first();
		if ($group.length && !$group.hasClass('mk-qt-opp-ref-group')) {
			var $clear = $group.find('.clearReferenceSelection').first();
			var $addons = $group.find('.input-group-addon');
			var $actions = $('<div class="mk-qt-opp-actions"></div>');
			if ($clear.length) {
				$actions.append($clear);
			}
			if ($addons.length) {
				$actions.append($addons);
			}
			$group.append($actions);
			$group.addClass('mk-qt-opp-ref-group');
		}

		// Hide + create on quote (must pick existing quote).
		$ref.find('.createReferenceRecord').addClass('mk-so-hide-legacy').hide();

		syncSubjectFromQuote();

		// Selecting a quote on create → reload so line items copy from that quote.
		if (isCreateMode() && !$editForm.data('mkSoQuoteReloadBound')) {
			$editForm.data('mkSoQuoteReloadBound', 1);
			var reloadFromQuote = function () {
				syncSubjectFromQuote();
				var id = parseInt($editForm.find('[name="quote_id"]').val(), 10) || 0;
				if (id <= 0) {
					return;
				}
				var cur = parseInt(
					(window.location.search.match(/[?&]quote_id=(\d+)/) || [])[1] || '0',
					10
				);
				if (cur === id) {
					return;
				}
				window.location.href =
					'index.php?module=SalesOrder&view=Edit&app=SALES&quote_id=' + id;
			};
			$editForm.on(
				(window.Vtiger_Edit_Js && Vtiger_Edit_Js.referenceSelectionEvent
					? Vtiger_Edit_Js.referenceSelectionEvent
					: 'Vtiger.Reference.Selection') + '.mkSoQuote',
				'[name="quote_id"]',
				function () {
					setTimeout(reloadFromQuote, 80);
				}
			);
			$editForm.on('change.mkSoQuote', '[name="quote_id"]', function () {
				setTimeout(reloadFromQuote, 80);
			});
		}
	}

	function pinTotalsBelowOrderDetails() {
		var $editForm = $form();
		var $items = $editForm.find('#lineItemTab').closest('.fieldBlockContainer').first();
		var $totals = $editForm.find('#lineItemResult').closest('.fieldBlockContainer').first();
		if (!$items.length || !$totals.length) {
			return;
		}
		if (!$items.find('#lineItemResult').length) {
			$items.append($totals.detach());
		}
		$totals = $items.find('#lineItemResult').closest('.fieldBlockContainer').first();
		$totals.addClass('mk-qt-totals-below mk-inv-totals-odoo mk-so-block--totals');

		var $result = $items.find('#lineItemResult');
		if (!$result.length) {
			return;
		}
		var $preTax = $result.find('#preTaxTotal').closest('tr');
		var $net = $result.find('#netTotal, .netTotal').closest('tr');
		var $sub = $preTax.length ? $preTax : $net;
		var $tax = $result.find('#group_tax_row');
		var $grand = $result.find('#grandTotal, .grandTotal').closest('tr');
		if ($sub.length) {
			$sub.removeClass('mk-inv-totals-hide hide').addClass('mk-inv-totals-row mk-inv-totals-row--sub').show();
			if (!$sub.find('.mk-inv-totals-label').length) {
				$sub.find('td:first').html('<div class="mk-inv-totals-label">Số tiền trước thuế</div>');
			}
			if ($preTax.length && $net.length && !$net.is($preTax)) {
				$net.addClass('mk-inv-totals-hide').hide();
			}
		}
		if ($tax.length) {
			$tax.removeClass('mk-inv-totals-hide hide').addClass('mk-inv-totals-row mk-inv-totals-row--tax').show();
			if (!$tax.find('.mk-inv-totals-label').length) {
				$tax.find('td:first').html('<div class="mk-inv-totals-label">Thuế GTGT</div>');
			}
		}
		if ($grand.length) {
			$grand.removeClass('mk-inv-totals-hide hide').addClass('mk-inv-totals-row mk-inv-totals-row--grand').show();
			if (!$grand.find('.mk-inv-totals-label').length) {
				$grand.find('td:first').html('<div class="mk-inv-totals-label">Tổng cộng</div>');
			}
		}
	}

	function lockAssignedAndMoveSoInfoToRail() {
		var $editForm = $form();
		var $rail = $('#mkSoOrderRail');
		if (!$editForm.length || !$rail.length) {
			return;
		}

		var $assigned = $editForm.find('[name="assigned_user_id"]').first();
		if ($assigned.length) {
			$assigned.closest('tr').addClass('mk-so-hide-legacy mk-qt-hide-legacy');
			var uid = '';
			try {
				if (window._USERMETA && _USERMETA.id) {
					uid = String(_USERMETA.id);
				} else if (window.app && typeof app.getUserId === 'function') {
					uid = String(app.getUserId() || '');
				}
			} catch (e) {}
			if (uid) {
				if ($assigned.is('select')) {
					if (!$assigned.find('option[value="' + uid + '"]').length) {
						$assigned.append($('<option/>', { value: uid, text: uid }));
					}
					$assigned.val(uid);
				} else {
					$assigned.val(uid);
				}
				try {
					$assigned.trigger('change');
				} catch (e2) {}
			}
		}

		$rail.find('.mk-qt-rail-card').filter(function () {
			return /assigned\s*to|phụ\s*trách/i.test($.trim($(this).find('.mk-qt-rail-card__title').text() || ''));
		}).remove();

		var $host = $editForm.find('[name="editContent"]').first();
		if (!$host.length) {
			$host = $editForm.find('.editViewContents').first();
		}
		if (!$host.length) {
			$host = $editForm;
		}
		// Always enforce single-column outer shell once rail lives inside the form.
		$host.addClass('mk-qt-edit-split mk-so-edit-split');
		$('.mk-qt-create__grid, .mk-so-create__grid').addClass('mk-qt-create__grid--single');
		if (!$editForm.find('#mkSoOrderRail').length) {
			$rail.addClass('mk-qt-rail--in-form mk-so-rail--in-form');
			$host.append($rail.detach());
		} else {
			$rail.addClass('mk-qt-rail--in-form mk-so-rail--in-form');
		}
		// Remove empty outer rail slots left behind after detach.
		$('.mk-qt-create__grid > .mk-qt-rail, .mk-so-create__grid > .mk-so-rail')
			.not($rail)
			.remove();

		// IMPORTANT: do not use .mk-so-edit-main here — page <main> already has that class,
		// so closest('.mk-so-edit-main') would always match and skip moving line items.
		var $main = $host.children('.mk-qt-edit-main').first();
		if (!$main.length) {
			$main = $('<div class="mk-qt-edit-main" data-mk-so-split-main="1"></div>');
			$host.prepend($main);
		}
		var $items = $editForm.find('#lineItemTab').closest('.fieldBlockContainer').first();
		if ($items.length && !$items.parent().is($main)) {
			$main.append($items.detach());
		}

		var $info = $editForm.find('.fieldBlockContainer[data-block="LBL_SO_INFORMATION"]').first();
		if ($info.length && !$info.closest('#mkSoOrderRail').length) {
			$info.addClass('mk-qt-block mk-qt-rail-quote-info mk-so-rail-info');
			var $h = $info.find('.fieldBlockHeader').first();
			if ($h.length) {
				var $icon = $h.find('.mk-so-block__icon, .mk-qt-block__icon').detach();
				$h.empty();
				if ($icon.length) {
					$h.append($icon);
				}
				$h.append(document.createTextNode(' Chi tiết bán hàng'));
			}
			var $addr = $rail.find('.mk-qt-address-rail, .mk-qt-rail-card--address').first();
			if ($addr.length) {
				$info.insertBefore($addr);
			} else {
				$rail.prepend($info);
			}
		}

		// Compact leftover empty rows in rail info (subject is hidden).
		$info = $rail.find('.mk-so-rail-info, .mk-qt-rail-quote-info').first();
		if ($info.length) {
			$info.find('tr').each(function () {
				var $tr = $(this);
				if ($tr.find('[name="quote_id"], [name="quote_id_display"], [name="description"], [name="mk_list_note"], .mk-so-terms-preview, .mk-qt-terms-preview, .mk-so-desc-preview').length) {
					return;
				}
				if ($tr.find('[name="subject"]').length) {
					$tr.addClass('mk-so-hide-legacy mk-qt-hide-legacy');
					return;
				}
				var hasVisible = $tr.find('input:visible, select:visible, textarea:visible, .referencefield-wrapper:visible').length;
				if (!hasVisible) {
					$tr.addClass('mk-so-hide-legacy mk-qt-hide-legacy');
				}
			});
		}

		pinTotalsBelowOrderDetails();
	}

	function requireQuoteBeforeSave() {
		var $editForm = $form();
		if (!$editForm.length || $editForm.data('mkSoQuoteRequiredBound')) {
			return;
		}
		$editForm.data('mkSoQuoteRequiredBound', 1);
		$editForm.on('submit.mkSoQuoteReq', function (e) {
			if (hasRequiredQuote()) {
				return true;
			}
			e.preventDefault();
			e.stopImmediatePropagation();
			notifyQuoteRequired();
			return false;
		});
	}

	function initStickyHead() {
		var $head = $('#mkSoStickyHead');
		if (!$head.length || $head.data('mkStickyBound')) {
			return;
		}
		$head.data('mkStickyBound', true);
		var threshold = $head.offset().top + $head.outerHeight();
		$(window).on('scroll.mkSoSticky', function () {
			$head.toggleClass('is-elevated', window.scrollY > threshold);
		});
	}

	function runEnhancements() {
		if (!isScoped()) {
			return;
		}
		hideLegacyChrome();
		styleFieldBlocks();
		initOdooInventoryUi();
		if (window.MkQuoteBa && typeof window.MkQuoteBa.init === 'function') {
			window.MkQuoteBa.init($form());
		}
		simplifySalesOrderForm();
		polishQuoteReferenceField();
		lockAssignedAndMoveSoInfoToRail();
		pinTotalsBelowOrderDetails();
		pinAddProductToLineHeader();
		initTermsRichEditor();
		initDescriptionRichEditor();
		requireQuoteBeforeSave();
		bindActions();
		initStickyHead();
		fixFormDisplayEncoding();
		ensureMandatoryHiddenDefaults();
		markOppCommerceRefreshOnSubmit();
		revealPage();
		setTimeout(function () {
			simplifySalesOrderForm();
			polishQuoteReferenceField();
			lockAssignedAndMoveSoInfoToRail();
			pinTotalsBelowOrderDetails();
			initDescriptionRichEditor();
			if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo === 'function') {
				window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo($form());
			} else if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.relocateCommerceToRail === 'function') {
				window.MkInventoryOdooEdit.relocateCommerceToRail($form());
			}
			fixFormDisplayEncoding();
		}, 300);
		setTimeout(function () {
			lockAssignedAndMoveSoInfoToRail();
			pinTotalsBelowOrderDetails();
			if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo === 'function') {
				window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo($form());
			} else if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.relocateCommerceToRail === 'function') {
				window.MkInventoryOdooEdit.relocateCommerceToRail($form());
			}
		}, 1200);
	}

	function bindWarehouseInterceptOnly() {
		var $editForm = $form();
		if (!$editForm.length || $editForm.data('mkSoWhBound')) {
			return;
		}
		$editForm.data('mkSoWhBound', true);
		bindSaveValidationRecovery();
		// No warehouse gate on create — outbound is created when confirming the order.
		$editForm.off('submit.mkSoWarehouse');
		$editForm.find('.saveButton').off('click.mkSoWarehouse');
		ensureCreateStatusPhieuTam();
	}

	function ensureSubjectSyncFromAccount() {
		var $editForm = $form();
		if (!$editForm.length) {
			return;
		}
		$editForm
			.off('change.mkSoSubjectSync', '[name="quote_id_display"], [name="contact_id_display"], [name="account_id_display"]')
			.on('change.mkSoSubjectSync', '[name="quote_id_display"], [name="contact_id_display"], [name="account_id_display"]', function () {
				syncSubjectFromQuote();
			});
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

	setTimeout(revealPage, 3000);

	window.__mkSoCreateBuild = MK_BUILD;
	window.__mkSoCreateSave = triggerSave;
})($);
