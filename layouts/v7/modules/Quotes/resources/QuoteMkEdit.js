/**
 * Quotes Create (SALES) — premium shell; stock Inventory #EditView unchanged.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260730_quote_save_err';
	var autosaveTimer;
	var draftAutosaveTimer;
	var draftSaveInFlight = false;
	var quoteSaveInFlight = false;
	var confirmSaveRequested = false;
	var formDirty = false;
	var TERMS_MODAL_ID = 'mkQtTermsModal';
	var TERMS_EDITOR_ID = 'mkQtTermsEditor';
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
		LBL_QUOTE_INFORMATION: 'fa-info-circle',
		LBL_SO_INFORMATION: 'fa-info-circle',
		LBL_ADDRESS_INFORMATION: 'fa-map-marker',
		LBL_ITEM_DETAILS: 'fa-cubes',
		LBL_DESCRIPTION_INFORMATION: 'fa-align-left',
		LBL_TERMS_INFORMATION: 'fa-file-text-o',
		LBL_MK_QUOTE_VAT: 'fa-calculator',
		'Recurring Invoice Information': 'fa-refresh'
	};

	function getModuleName() {
		return (document.body && document.body.getAttribute('data-module')) || '';
	}

	function isSalesOrder() {
		return getModuleName() === 'SalesOrder';
	}

	function isScoped() {
		var body = document.body;
		var mod = body && body.getAttribute('data-module');
		return (
			body &&
			body.getAttribute('data-view') === 'Edit' &&
			(mod === 'Quotes' || mod === 'SalesOrder') &&
			(body.getAttribute('data-app') === 'SALES' || !body.getAttribute('data-app'))
		);
	}

	function $formHost() {
		if (isSalesOrder()) {
			return $('#mkSoFormHost');
		}
		return $('#mkQtFormHost');
	}

	function $form() {
		var $host = $formHost();
		var $f = $host.find('form#EditView, form[name="EditView"]').first();
		if (!$f.length) {
			$f = $('form#EditView.recordEditView, form[name="edit"].recordEditView').first();
		}
		return $f;
	}

	function hideLegacyChrome() {
		var $host = $formHost();
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

	var SIGNATURE_LABEL_RE =
		/(?:Giám\s*đốc|\(Director\)|Người\s*báo\s*giá|\(Quotation\s*created\s*by\)|Khách\s*hàng|\(Customer\)|XÁC\s*NHẬN\s*ĐẶT\s*HÀNG)/i;

	function stripLegacySignatureHtml(html) {
		if (!html) {
			return '';
		}
		var s = String(html);
		s = s.replace(/<div[^>]*class="[^"]*mk-quote-signature-block[^"]*"[^>]*>[\s\S]*?<\/div>/gi, '');
		s = s.replace(/<div[^>]*mk-quote-signature-block[^>]*>[\s\S]*?<\/div>/gi, '');
		s = s.replace(/<table[^>]*>[\s\S]*?(?:Giám\s*đốc|\(Director\)|Người\s*báo\s*giá|\(Quotation\s*created\s*by\)|\(Customer\))[\s\S]*?<\/table>/gi, '');
		s = s.replace(/<p[^>]*>\s*<strong>\s*Giám\s*đốc\s*<\/strong>\s*<\/p>/gi, '');
		s = s.replace(/<(p|div|td|th|li|h[1-6])[^>]*>([\s\S]*?)<\/\1>/gi, function (match, _tag, inner) {
			var text = inner.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
			if (text && text.length < 120 && SIGNATURE_LABEL_RE.test(text)) {
				return '';
			}
			return match;
		});
		return s.trim();
	}

	function markCreateEnhanced() {
		if (document.documentElement.classList.contains('mk-inv-ui-ready')) {
			document.documentElement.classList.add('mk-quote-create-enhanced');
			return;
		}
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				if (document.documentElement.classList.contains('mk-inv-ui-ready')) {
					document.documentElement.classList.add('mk-quote-create-enhanced');
				}
			});
		});
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
		var html = stripLegacySignatureHtml(prepareTermsHtml($ta.val() || '', true));
		if (!$.trim(html)) {
			$preview.html('<span class="mk-qt-terms-preview__placeholder">Nhấn để nhập ghi chú…</span>');
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
		$body.html(
			'<textarea id="' +
				TERMS_EDITOR_ID +
				'" class="form-control mk-qt-terms-editor-ta" rows="14"></textarea>'
		);
		$('#' + TERMS_EDITOR_ID).val(currentVal);
	}

	function syncTermsToSource() {
		if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[TERMS_EDITOR_ID]) {
			try {
				var html = CKEDITOR.instances[TERMS_EDITOR_ID].getData();
				var $ta = $form().find('textarea[name="terms_conditions"]').first();
				if ($ta.length) {
					$ta.val(stripLegacySignatureHtml(prepareTermsHtml(html, false)));
				}
			} catch (ignore) {
				/* keep existing textarea value */
			}
		}
		destroyTermsCkEditor();
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
				'<div class="modal-dialog modal-lg mk-qt-terms-modal-dialog" role="document">' +
				'<div class="modal-content mk-qt-terms-modal-content">' +
				'<div class="modal-header">' +
				'<button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>' +
				'<h4 class="modal-title" id="' +
				TERMS_MODAL_ID +
				'Label">Ghi chú</h4>' +
				'</div>' +
				'<div class="modal-body">' +
				'<textarea id="' +
				TERMS_EDITOR_ID +
				'" class="form-control" rows="14"></textarea>' +
				'</div>' +
				'<div class="modal-footer">' +
				'<button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>' +
				'<button type="button" class="btn btn-success" id="mkQtTermsSaveBtn">Lưu nội dung</button>' +
				'</div></div></div></div>'
		);
		$('body').append($modal);

		$modal.off('hidden.bs.modal.mkQtTerms').on('hidden.bs.modal.mkQtTerms', function () {
			termsModalOpen = false;
			destroyTermsCkEditor();
		});

		$modal.off('click.mkQtSave', '#mkQtTermsSaveBtn').on('click.mkQtSave', '#mkQtTermsSaveBtn', function () {
			var html = '';
			if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[TERMS_EDITOR_ID]) {
				html = CKEDITOR.instances[TERMS_EDITOR_ID].getData();
			} else {
				html = $('#' + TERMS_EDITOR_ID).val();
			}
			html = stripLegacySignatureHtml(prepareTermsHtml(html, false));
			var $ta = $form().find('textarea[name="terms_conditions"]').first();
			if ($ta.length) {
				$ta.val(html).trigger('change');
				syncTermsPreview($ta, $ta.siblings('.mk-qt-terms-preview'));
				markDirty();
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
		var cleanVal = stripLegacySignatureHtml(prepareTermsHtml($ta.val() || '', true));
		$('#' + TERMS_EDITOR_ID).val(cleanVal);

		$modal.off('shown.bs.modal.mkQtTerms').on('shown.bs.modal.mkQtTerms', function () {
			destroyTermsCkEditor();
			resetTermsEditorTextarea();
			$('#' + TERMS_EDITOR_ID).val(cleanVal);
			initTermsCkEditorOnce();
		});

		$modal.modal('show');
	}

	function getTermsTemplates() {
		var c = window.MkQuoteBa && MkQuoteBa.cfg ? MkQuoteBa.cfg() : window.__MK_QUOTE_BA_CONFIG || {};
		return (c && c.terms_templates) || [];
	}

	function findTermsTemplate(id) {
		var templates = getTermsTemplates();
		for (var i = 0; i < templates.length; i++) {
			if (templates[i].id === id) {
				return templates[i];
			}
		}
		return null;
	}

	function applyTermsTemplate($ta, templateId, force) {
		if (!$ta || !$ta.length) {
			return;
		}
		var tpl = findTermsTemplate(templateId);
		if (!tpl) {
			return;
		}
		var current = stripLegacySignatureHtml(prepareTermsHtml($ta.val() || '', true));
		if (!force && $.trim(current)) {
			return;
		}
		var html = stripLegacySignatureHtml(prepareTermsHtml(tpl.html || '', false));
		$ta.val(html).trigger('change');
		syncTermsPreview($ta, $ta.siblings('.mk-qt-terms-preview'));
		markDirty();
	}

	function injectTermsTemplatePicker($ta) {
		// UX change: "Điều kiện & điều khoản" is no longer used.
		// Keep notes minimal: no template picker.
		return;
		if (!$ta.length || $ta.closest('td').find('.mk-qt-terms-template-wrap').length) {
			return;
		}
		var templates = getTermsTemplates();
		if (!templates.length) {
			return;
		}
		var options = templates
			.map(function (tpl) {
				return (
					'<option value="' +
					tpl.id +
					'">' +
					$('<div>').text(tpl.label || tpl.id).html() +
					'</option>'
				);
			})
			.join('');
		var $wrap = $(
			'<div class="mk-qt-terms-template-wrap">' +
				'<label class="mk-qt-terms-template-label" for="mkQtTermsTemplateSelect">Mẫu điều khoản</label>' +
				'<select id="mkQtTermsTemplateSelect" class="form-control mk-qt-terms-template-select">' +
				options +
				'</select>' +
				'</div>'
		);
		$ta.closest('td.fieldValue').prepend($wrap);
		$wrap.find('select').on('change.mkQtTermsTpl', function () {
			var id = $(this).val();
			if (!id || id === 'blank') {
				return;
			}
			if (
				!window.confirm(
					'Áp dụng mẫu này sẽ thay thế nội dung điều khoản hiện tại. Bạn có muốn tiếp tục?'
				)
			) {
				return;
			}
			applyTermsTemplate($ta, id, true);
		});
	}

	function isNewQuoteRecord() {
		var recordId = $.trim($form().find('[name="record"], #recordId').first().val() || '');
		var isDuplicate = $.trim($form().find('[name="isDuplicate"]').first().val() || '');
		var duplicating =
			isDuplicate === 'true' ||
			isDuplicate === '1' ||
			isDuplicate === 'yes' ||
			isDuplicate === 'on';
		// Blank create only — keep source terms/notes when duplicating.
		return !recordId && !duplicating;
	}

	function initTermsRichEditor() {
		var $ta = $form().find('textarea[name="terms_conditions"]').first();
		if (!$ta.length || $ta.data('mkQtTermsReady')) {
			return;
		}
		$ta.data('mkQtTermsReady', true);

		// Bỏ nội dung mặc định / chữ ký cũ — user tự nhập.
		var cleaned = stripLegacySignatureHtml(prepareTermsHtml($ta.val() || '', true));
		// For new quotes, keep notes EMPTY by default (no "terms template" content).
		if (isNewQuoteRecord()) {
			cleaned = '';
		}
		$ta.val(cleaned);

		var $preview = $(
			'<div class="mk-qt-terms-preview inputElement textAreaElement col-lg-12" role="button" tabindex="0" title="Nhấn để nhập và định dạng điều khoản"></div>'
		);
		$ta.addClass('mk-qt-terms-source').attr({ 'aria-hidden': 'true', tabindex: '-1' });
		$ta.after($preview);
		injectTermsTemplatePicker($ta);

		syncTermsPreview($ta, $preview);

		$preview
			.off('click.mkQtTerms keydown.mkQtTerms')
			.on('click.mkQtTerms', function (e) {
				e.preventDefault();
				e.stopPropagation();
				openTermsEditor($ta);
			})
			.on('keydown.mkQtTerms', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					e.stopPropagation();
					openTermsEditor($ta);
				}
			});

		$form()
			.off('submit.mkQtTerms')
			.on('submit.mkQtTerms', function () {
				syncTermsToSource();
			});
	}

	function simplifyQuoteForm() {
		var flag = isSalesOrder() ? 'mkSoSimplified' : 'mkQtSimplified';
		if ($form().data(flag)) {
			return;
		}
		$form().data(flag, true);

		var hideFields = [
			'carrier',
			'shipping',
			'inventorymanager',
			'assigned_user_id',
			'assigned_user_id1',
			'bill_pobox',
			'bill_city',
			'bill_state',
			'bill_code',
			'bill_country',
			'ship_pobox',
			'ship_city',
			'ship_state',
			'ship_code',
			'ship_country'
		];
		if (isSalesOrder()) {
			// SO still shows Assigned To in rail — don't hide assigned_user_id via this list.
			hideFields = hideFields.filter(function (n) {
				return n !== 'assigned_user_id';
			});
			hideFields = hideFields.concat([
				'salescommission',
				'leadsource',
				'team_group',
				'invoicestatus',
				'purchaseorder',
				'quote_id',
				'contact_id',
				'currency_id',
				'conversion_rate',
				'hdnTaxType',
				'taxtype'
			]);
		} else {
			// Quote create: status is auto-draft — hide field, keep contact visible.
			hideFields.push('quotestage');
			// Hide "Có giá trị đến" (validtill) on Quotes create/edit SALES form.
			hideFields.push('validtill');
			// Price tier UI lives in line items — hide leftover label in quote info.
			hideFields.push(
				'mk_invoice_price_tier',
				'currency_id',
				'conversion_rate',
				'account_id',
				'hdnTaxType',
				'pre_tax_total',
				'hdnSubTotal',
				'hdnGrandTotal',
				'txtAdjustment',
				'hdnS_H_Amount'
			);
		}
		hideFields.forEach(function (name) {
			hideQuoteFieldPair(name);
		});

		$form()
			.find('.fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"]')
			.addClass('mk-qt-address-simplified mk-qt-hide-legacy');

		if (!isSalesOrder()) {
			ensureDraftQuoteStage();
			layoutQuoteHeaderFields();
		}
	}

	/** Group clear / search / add into a single action strip on the right. */
	function polishOppReferenceField($refWrap) {
		if (!$refWrap || !$refWrap.length) {
			return;
		}
		var $group = $refWrap.find('.input-group').first();
		if (!$group.length || $group.hasClass('mk-qt-opp-ref-group')) {
			return;
		}
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

	/**
	 * Always ensure a visible "Khách hàng" row at the top of Chi tiết báo giá.
	 * Does not rely on stock contact/potential cells (those may be hidden).
	 */
	function ensureCustomerFieldVisible() {
		var $f = $form();
		if (!$f.length || isSalesOrder()) {
			return;
		}

		var $infoBlock = $f.find('.fieldBlockContainer[data-block="LBL_QUOTE_INFORMATION"]').first();
		var $infoTable = $infoBlock.find('table.table-borderless').first();
		var $infoBody = $infoTable.children('tbody').first();
		if (!$infoBody.length && $infoTable.length) {
			$infoBody = $infoTable;
		}
		if (!$infoBody.length) {
			return;
		}

		// Hide stock Opportunity + Subject (kept in DOM for save).
		['potential_id', 'subject'].forEach(function (name) {
			var $el = $f.find('[name="' + name + '"]').first();
			if (!$el.length) {
				return;
			}
			var $val = $el.closest('td.fieldValue');
			if ($val.length) {
				$val.addClass('mk-qt-hide-legacy');
				$val.prev('td.fieldLabel').addClass('mk-qt-hide-legacy');
			}
			$el.removeAttr('data-rule-required').removeClass('required');
			$f.find('[name="' + name + '_display"]').removeAttr('data-rule-required').removeClass('required');
		});

		var $existingRow = $f.find('tr.mk-qt-customer-row').first();
		var $contact = $f.find('[name="contact_id"]').first();
		var $contactDisplay = $f.find('[name="contact_id_display"]').first();

		if (!$existingRow.length) {
			var $row = $(
				'<tr class="mk-qt-customer-row mk-qt-contact-injected">' +
					'<td class="fieldLabel alignMiddle">' +
						'<label class="muted mk-qt-customer-label">' +
							'Khách hàng <span class="redColor">*</span>' +
							'<button type="button" class="mk-qt-customer-info hide" title="Thông tin khách hàng" aria-label="Thông tin khách hàng">' +
								'<i class="fa fa-info-circle" aria-hidden="true"></i> Thông tin' +
							'</button>' +
						'</label>' +
					'</td>' +
					'<td class="fieldValue mk-qt-customer-field" colspan="3"></td>' +
				'</tr>'
			);
			var $customerTd = $row.find('td.fieldValue.mk-qt-customer-field').first();

			if ($contact.length && $contactDisplay.length) {
				// Move existing contact reference UI into the new row.
				var $refWrap = $contactDisplay.closest('.referencefield-wrapper');
				if (!$refWrap.length) {
					$refWrap = $contact.closest('.referencefield-wrapper');
				}
				if ($refWrap.length) {
					$customerTd.append($refWrap.detach());
				} else {
					$customerTd.append($contact.detach()).append($contactDisplay.detach());
				}
				// Hide leftover empty cells from old contact placement.
				var $oldVal = $f.find('td.fieldValue').filter(function () {
					return $(this).find('[name="contact_id"], [name="contact_id_display"]').length === 0
						&& $(this).text().replace(/\s+/g, '') === ''
						&& $(this).children().length === 0;
				});
			} else {
				$customerTd.append(buildContactReferenceHtml());
				registerInjectedContactEvents($customerTd);
			}

			$infoBody.prepend($row);
			$existingRow = $row;
			$contact = $f.find('[name="contact_id"]').first();
			$contactDisplay = $f.find('[name="contact_id_display"]').first();
		} else {
			$infoBody.prepend($existingRow);
			var $customerTd = $existingRow.find('td.mk-qt-customer-field').first();
			if ($customerTd.length) {
				$customerTd.attr('colspan', '3');
			}
			$existingRow.find('td.mk-qt-tier-field').remove();
			$existingRow.find('td.fieldLabel').filter(function () {
				return /bảng giá/i.test($.trim($(this).text() || ''));
			}).remove();
		}

		// Ensure Info control next to "Khách hàng" label
		var $label = $existingRow.find('td.fieldLabel').first();
		if ($label.length && !$label.find('.mk-qt-customer-info').length) {
			var $lbl = $label.find('label').first();
			if (!$lbl.length) {
				$lbl = $('<label class="muted mk-qt-customer-label"></label>');
				$label.prepend($lbl);
			}
			$lbl.addClass('mk-qt-customer-label');
			if (!$lbl.find('.redColor').length && !/khách hàng/i.test($lbl.html() || '')) {
				$lbl.prepend('Khách hàng <span class="redColor">*</span> ');
			}
			$lbl.append(
				'<button type="button" class="mk-qt-customer-info hide" title="Thông tin khách hàng" aria-label="Thông tin khách hàng">' +
					'<i class="fa fa-info-circle" aria-hidden="true"></i> Thông tin' +
				'</button>'
			);
		}

		// Hide any other stock contact cells outside our row (avoid duplicates).
		$f.find('[name="contact_id"], [name="contact_id_display"]').each(function () {
			var $cell = $(this).closest('td.fieldValue');
			if (!$cell.closest('tr.mk-qt-customer-row').length) {
				$cell.addClass('mk-qt-hide-legacy');
				$cell.prev('td.fieldLabel').addClass('mk-qt-hide-legacy');
			}
		});

		$existingRow.removeClass('mk-qt-hide-legacy mk-inv-hide-legacy').show();
		$existingRow.find('td').removeClass('mk-qt-hide-legacy mk-inv-hide-legacy');

		var $refWrap = $existingRow.find('.referencefield-wrapper').first();
		$refWrap.addClass('mk-qt-opp-ref mk-qt-customer-ref');
		$refWrap.find('input[name="popupReferenceModule"]').val('Contacts');
		// Kill stock Contacts popup on this field — we open the tabbed picker instead.
		$refWrap.find('.relatedPopup').off('click').attr('data-mk-custom-search', '1');
		polishOppReferenceField($refWrap);

		if ($contactDisplay.length) {
			$contactDisplay
				.attr('placeholder', 'Tìm khách hàng / cơ hội / KH tiềm năng...')
				.attr('data-rule-required', 'true')
				.addClass('required');
		}
		if ($contact.length) {
			$contact.removeAttr('data-rule-required').removeClass('required');
		}
		if (($contact.length && $.trim($contact.val() || '')) || ($contactDisplay.length && $.trim($contactDisplay.val() || ''))) {
			$refWrap.find('.clearReferenceSelection').removeClass('hide');
		}

		registerUnifiedCustomerPicker();
		registerCustomerInfoButton();
		syncCustomerInfoButtonVisibility();
		ensureTierDropdownVisible($f);
		if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo === 'function') {
			setTimeout(function () {
				window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo($f);
				ensureTierDropdownVisible($f);
			}, 0);
			setTimeout(function () {
				window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo($f);
				ensureTierDropdownVisible($f);
			}, 250);
		}
	}

	/**
	 * Quote header: one "Khách hàng" field (search Contacts/Opp/Leads + create Contact).
	 */
	function layoutQuoteHeaderFields() {
		ensureCustomerFieldVisible();
	}

	function clearQuoteCustomerFields() {
		var $f = $form();
		$f.find('[name="contact_id"]').val('');
		$f.find('[name="contact_id_display"]').val('').removeData('mkCustomerModule').removeData('mkLeadId').removeData('mkServiceContractId');
		$f.find('[name="potential_id"]').val('');
		$f.find('[name="potential_id_display"]').val('');
		$f.find('[name="subject"]').val('');
		$f.find('.mk-qt-customer-ref .clearReferenceSelection').addClass('hide');
		$f.find('.mk-qt-customer-ref').removeClass('selected');
		ensureServiceContractLinkFields($f, 0);
		applyQuotePriceChannel('retail', { clearSc: true });
		closeCustomerInfoPopover();
		syncCustomerInfoButtonVisibility();
	}

	function ensureServiceContractLinkFields($f, scId) {
		$f = $f || $form();
		scId = parseInt(scId, 10) || 0;
		var $scField = $f.find('[name="mk_servicecontract_id"]');
		if (!$scField.length) {
			$scField = $('<input type="hidden" name="mk_servicecontract_id" id="mk_servicecontract_id" />');
			$f.prepend($scField);
		}
		$scField.val(scId > 0 ? String(scId) : '');
		var $scAlt = $f.find('[name="servicecontract_id"]');
		if (!$scAlt.length) {
			$scAlt = $('<input type="hidden" name="servicecontract_id" id="servicecontract_id" />');
			$f.prepend($scAlt);
		}
		$scAlt.val(scId > 0 ? String(scId) : '');
		return scId;
	}

	/**
	 * retail = Opp / Leads / Khách hàng (bảng giá lẻ theo bậc HĐ)
	 * tuibao = Nhượng quyền / ServiceContracts
	 */
	function applyQuotePriceChannel(channel, opts) {
		opts = opts || {};
		var inv = window.MkInventoryOdooEdit;
		if (inv && typeof inv.setPriceChannel === 'function') {
			inv.setPriceChannel(channel, {
				scPrefill: opts.scPrefill || null,
				clearScPrefill: opts.clearSc !== false
			});
		} else {
			window.MK_PRICE_CHANNEL = channel === 'tuibao' ? 'tuibao' : 'retail';
			if (channel !== 'tuibao') {
				window.MK_SC_PREFILL = null;
			} else if (opts.scPrefill) {
				window.MK_SC_PREFILL = opts.scPrefill;
			}
		}
		if (inv && typeof inv.applyInvoiceTierPricing === 'function') {
			inv.applyInvoiceTierPricing($form(), { force: true });
		}
	}

	function resolveSelectedCustomerRef() {
		var $f = $form();
		var $display = $f.find('[name="contact_id_display"]').first();
		var mod = ($display.data('mkCustomerModule') || '').toString();
		var contactId = parseInt($f.find('[name="contact_id"]').val(), 10) || 0;
		var potentialId = parseInt($f.find('[name="potential_id"]').val(), 10) || 0;
		var leadId = parseInt($display.data('mkLeadId'), 10) || 0;
		var scId =
			parseInt($display.data('mkServiceContractId'), 10) ||
			parseInt($f.find('[name="mk_servicecontract_id"]').val(), 10) ||
			parseInt($f.find('[name="servicecontract_id"]').val(), 10) ||
			0;
		var label = $.trim($display.val() || '');

		if ((mod === 'ServiceContracts' || mod === 'Franchise') && scId > 0) {
			return { module: 'ServiceContracts', record: scId, label: label };
		}
		if (mod === 'Leads' && leadId > 0) {
			return { module: 'Leads', record: leadId, label: label };
		}
		if (mod === 'Potentials' && potentialId > 0) {
			return { module: 'Potentials', record: potentialId, label: label, contactId: contactId };
		}
		if (contactId > 0) {
			return { module: 'Contacts', record: contactId, label: label };
		}
		if (potentialId > 0) {
			return { module: 'Potentials', record: potentialId, label: label, contactId: contactId };
		}
		if (leadId > 0) {
			return { module: 'Leads', record: leadId, label: label };
		}
		if (scId > 0) {
			return { module: 'ServiceContracts', record: scId, label: label };
		}
		// Edit load: only display text + contact without module tag
		if (label && contactId > 0) {
			return { module: 'Contacts', record: contactId, label: label };
		}
		return null;
	}

	function syncCustomerInfoButtonVisibility() {
		var $btn = $form().find('.mk-qt-customer-info');
		if (!$btn.length) {
			return;
		}
		var ref = resolveSelectedCustomerRef();
		var hasDisplay = $.trim($form().find('[name="contact_id_display"]').val() || '') !== '';
		if (ref || hasDisplay) {
			// Prefer real id; if only display (legacy) still show when we can guess Contacts from empty
			if (ref) {
				$btn.removeClass('hide');
			} else {
				$btn.addClass('hide');
			}
		} else {
			$btn.addClass('hide');
		}
	}

	function closeCustomerInfoPopover() {
		$('#mk-qt-customer-info-pop').remove();
		$(document).off('mousedown.mkQtCustomerInfo');
	}

	function fetchRecordDetailsSimple(module, recordId) {
		var d = $.Deferred();
		recordId = parseInt(recordId, 10) || 0;
		if (!module || recordId <= 0) {
			d.resolve({});
			return d.promise();
		}
		$.getJSON('index.php', {
			module: 'Vtiger',
			action: 'GetData',
			source_module: module,
			record: recordId
		})
			.done(function (res) {
				var data =
					(res && res.result && res.result.data) ||
					(res && res.data) ||
					{};
				d.resolve(data || {});
			})
			.fail(function () {
				d.resolve({});
			});
		return d.promise();
	}

	function buildCustomerInfoRows(rows) {
		var html = '';
		(rows || []).forEach(function (row) {
			if (!row || !row.label) {
				return;
			}
			var val = $.trim(row.value || '');
			if (!val) {
				val = '—';
			}
			html +=
				'<div class="mk-qt-customer-info-pop__row">' +
					'<div class="mk-qt-customer-info-pop__k">' +
					escHtml(row.label) +
					'</div>' +
					'<div class="mk-qt-customer-info-pop__v">' +
					escHtml(val) +
					'</div>' +
				'</div>';
		});
		return html;
	}

	function openCustomerInfoPopover($btn) {
		var ref = resolveSelectedCustomerRef();
		if (!ref || !ref.record) {
			return;
		}
		closeCustomerInfoPopover();
		var $pop = $(
			'<div id="mk-qt-customer-info-pop" class="mk-qt-customer-info-pop" role="dialog" aria-label="Thông tin khách hàng">' +
				'<div class="mk-qt-customer-info-pop__hd">' +
					'<span class="mk-qt-customer-info-pop__title">Thông tin khách hàng</span>' +
					'<button type="button" class="mk-qt-customer-info-pop__close" title="Đóng" aria-label="Đóng">&times;</button>' +
				'</div>' +
				'<div class="mk-qt-customer-info-pop__body">' +
					'<div class="mk-qt-customer-info-pop__loading">Đang tải...</div>' +
				'</div>' +
				'<div class="mk-qt-customer-info-pop__ft">' +
					'<a class="mk-qt-customer-info-pop__link" href="#" target="_blank" rel="noopener">Mở chi tiết</a>' +
				'</div>' +
			'</div>'
		);
		$('body').append($pop);

		var place = function () {
			var rect = $btn[0].getBoundingClientRect();
			var top = rect.bottom + window.scrollY + 6;
			var left = rect.left + window.scrollX;
			var w = $pop.outerWidth() || 300;
			if (left + w > window.scrollX + window.innerWidth - 12) {
				left = Math.max(8, window.scrollX + window.innerWidth - w - 12);
			}
			$pop.css({ top: top + 'px', left: left + 'px' });
		};
		place();

		$pop.find('.mk-qt-customer-info-pop__close').on('click', function (e) {
			e.preventDefault();
			closeCustomerInfoPopover();
		});

		var detailUrl =
			'index.php?module=' +
			encodeURIComponent(ref.module) +
			'&view=Detail&record=' +
			encodeURIComponent(ref.record);
		$pop.find('.mk-qt-customer-info-pop__link').attr('href', detailUrl);

		var fillFromData = function (primary, extra) {
			extra = extra || {};
			primary = primary || {};
			var name = $.trim(ref.label || '');
			if (!name) {
				name = $.trim((primary.firstname || '') + ' ' + (primary.lastname || ''));
			}
			if (!name) {
				name = $.trim(
					primary.potentialname || primary.company || primary.accountname || ''
				);
			}
			var phone =
				primary.mobile ||
				primary.phone ||
				extra.mobile ||
				extra.phone ||
				'';
			if (phone && window.MkPhoneFormat && typeof window.MkPhoneFormat.format === 'function') {
				phone = window.MkPhoneFormat.format(phone) || phone;
			}
			var email = primary.email || primary.email1 || extra.email || extra.email1 || '';
			var company = primary.company || extra.accountname || primary.accountname || '';
			var address =
				primary.mailingstreet ||
				primary.bill_street ||
				primary.lane ||
				extra.mailingstreet ||
				extra.bill_street ||
				'';
			var city = primary.mailingcity || primary.bill_city || extra.mailingcity || '';
			if (city && address) {
				address = address + ', ' + city;
			} else if (city) {
				address = city;
			}
			var rows = [
				{ label: 'Tên', value: name },
				{
					label: 'Loại',
					value:
						ref.module === 'Contacts'
							? 'Người liên hệ'
							: ref.module === 'Potentials'
								? 'Cơ hội'
								: 'KH tiềm năng'
				},
				{ label: 'SĐT', value: phone },
				{ label: 'Email', value: email },
				{ label: 'Công ty', value: company },
				{ label: 'Địa chỉ', value: address }
			];
			$pop.find('.mk-qt-customer-info-pop__body').html(buildCustomerInfoRows(rows));
			place();
		};

		if (ref.module === 'Potentials') {
			fetchRecordDetailsSimple('Potentials', ref.record).then(function (pot) {
				var contactId = parseInt(ref.contactId || pot.contact_id, 10) || 0;
				var accountId = parseInt(pot.related_to, 10) || 0;
				$.when(
					contactId ? fetchRecordDetailsSimple('Contacts', contactId) : $.Deferred().resolve({}).promise(),
					accountId ? fetchRecordDetailsSimple('Accounts', accountId) : $.Deferred().resolve({}).promise()
				).done(function (contact, account) {
					var mergedExtra = $.extend({}, account || {}, contact || {});
					fillFromData(pot || {}, mergedExtra);
				});
			});
		} else {
			fetchRecordDetailsSimple(ref.module, ref.record).then(function (data) {
				// Contacts may link to account
				var accountId = parseInt(data.account_id, 10) || 0;
				if (accountId > 0 && ref.module === 'Contacts') {
					fetchRecordDetailsSimple('Accounts', accountId).then(function (acc) {
						fillFromData(data, acc);
					});
				} else {
					fillFromData(data, {});
				}
			});
		}

		setTimeout(function () {
			$(document).on('mousedown.mkQtCustomerInfo', function (e) {
				if (!$(e.target).closest('#mk-qt-customer-info-pop, .mk-qt-customer-info').length) {
					closeCustomerInfoPopover();
				}
			});
		}, 0);
	}

	function registerCustomerInfoButton() {
		var $f = $form();
		if (!$f.length || $f.data('mkCustomerInfoBound')) {
			return;
		}
		$f.data('mkCustomerInfoBound', 1);
		$f.on('click.mkQtCustomerInfo', '.mk-qt-customer-info', function (e) {
			e.preventDefault();
			e.stopPropagation();
			openCustomerInfoPopover($(this));
		});
	}

	function normalizeTierQuoteRow($tierRow) {
		if (!$tierRow || !$tierRow.length) {
			return $();
		}
		$tierRow
			.addClass('mk-inv-quote-field-row mk-qt-tier-row')
			.attr('data-mk-field', 'mk_invoice_price_tier');
		var $labelTd = $tierRow.find('td.fieldLabel').first();
		if (!$labelTd.length) {
			$labelTd = $('<td class="fieldLabel"><label class="muted">Bảng giá</label></td>');
			$tierRow.prepend($labelTd);
		} else {
			$labelTd.removeClass('alignMiddle mk-qt-hide-legacy mk-inv-hide-legacy');
			if (!$labelTd.find('label').length) {
				$labelTd.html('<label class="muted">Bảng giá</label>');
			} else {
				$labelTd.find('label').addClass('muted');
			}
		}
		var $valueTd = $tierRow.find('td.fieldValue').first();
		if (!$valueTd.length) {
			$valueTd = $('<td class="fieldValue"></td>');
			$tierRow.append($valueTd);
		}
		$valueTd.removeAttr('colspan').removeClass('mk-qt-tier-field mk-qt-hide-legacy mk-inv-hide-legacy');
		return $valueTd;
	}

	function ensureTierDropdownVisible($f) {
		if (!$f || !$f.length || isSalesOrder()) {
			return;
		}
		var $customerRow = $f.find('tr.mk-qt-customer-row').first();
		if (!$customerRow.length) {
			return;
		}
		var $tierRow = $f.find('tr.mk-qt-tier-row, tr[data-mk-field="mk_invoice_price_tier"]').first();
		if (!$tierRow.length) {
			$tierRow = $('<tr class="mk-inv-quote-field-row mk-qt-tier-row" data-mk-field="mk_invoice_price_tier"></tr>');
			$customerRow.after($tierRow);
		}
		var $tierTd = normalizeTierQuoteRow($tierRow);
		var $paymentRow = $f.find('tr[data-mk-field="mk_payment_method"]').first();
		if ($paymentRow.length && $tierRow[0] !== $paymentRow[0]) {
			$paymentRow.before($tierRow);
		} else if (!$paymentRow.length) {
			$customerRow.after($tierRow);
		}

		var $existingSelect = $f.find('#mkInvInvoicePriceTierSelect, select[name="mk_invoice_price_tier"]').first();
		if ($existingSelect.length) {
			try {
				if ($existingSelect.data('select2')) {
					$existingSelect.select2('destroy');
				}
			} catch (ignoreSelect2Destroy) {
				/* ignore */
			}
			$existingSelect
				.addClass('inputElement mk-inv-commerce-select')
				.removeClass('select2-offscreen select2-hidden-accessible mk-inv-price-tier__select')
				.removeAttr('style');
			$existingSelect.siblings('.select2-container').remove();
			if ($existingSelect.closest('tr.mk-qt-tier-row, tr[data-mk-field="mk_invoice_price_tier"]').length === 0) {
				$tierTd.empty().append($existingSelect.detach());
			} else if ($existingSelect.closest('td.fieldValue').length === 0) {
				$tierTd.empty().append($existingSelect.detach());
			}
			return;
		}
		if ($tierTd.find('select[name="mk_invoice_price_tier"]').length) {
			return;
		}

		var currentValue = $.trim($f.find('[name="mk_invoice_price_tier"]').first().val() || '');
		if (!currentValue || !/^(auto|lt_1m|gte_1m|gte_3m|gte_5m|gte_7m)$/.test(currentValue)) {
			currentValue = 'lt_1m';
		}

		var options = [
			{ value: 'auto', label: 'Tự động theo tổng đơn' },
			{ value: 'lt_1m', label: 'Giá < 1 triệu' },
			{ value: 'gte_1m', label: 'Giá >= 1 triệu' },
			{ value: 'gte_3m', label: 'Giá >= 3 triệu' },
			{ value: 'gte_5m', label: 'Giá >= 5 triệu' },
			{ value: 'gte_7m', label: 'Giá >= 7 triệu' }
		];
		var html = '<select name="mk_invoice_price_tier" id="mkInvInvoicePriceTierSelect" class="inputElement mk-inv-commerce-select">';
		options.forEach(function (item) {
			html +=
				'<option value="' +
				item.value +
				'"' +
				(item.value === currentValue ? ' selected="selected"' : '') +
				'>' +
				item.label +
				'</option>';
		});
		html += '</select>';
		var $select = $(html);
		$tierTd.empty().append($select);

		$select.on('change.mkQtTierFallback', function () {
			var value = $.trim($(this).val() || '') || 'lt_1m';
			$f.find('input[name="mk_invoice_price_tier"]').val(value);
			if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo === 'function') {
				window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo($f);
			}
			$(this).trigger('input').trigger('change');
		});
	}

	function setHiddenRef($f, field, id, label) {
		id = parseInt(id, 10) || 0;
		label = $.trim(label || '');
		$f.find('[name="' + field + '"]').val(id > 0 ? id : '');
		var $disp = $f.find('[name="' + field + '_display"]');
		if ($disp.length) {
			$disp.val(label);
		}
	}

	function applyUnifiedCustomerSelection(item) {
		var $f = $form();
		if (!item || !item.module) {
			return;
		}
		var label = $.trim(item.label || '');
		var $display = $f.find('[name="contact_id_display"]').first();
		$display.val(label).data('mkCustomerModule', item.module);
		if (item.lead_id) {
			$display.data('mkLeadId', item.lead_id);
		} else {
			$display.removeData('mkLeadId');
		}
		$display.removeData('mkServiceContractId');

		if (item.module === 'ServiceContracts' || item.module === 'Franchise') {
			var scId = parseInt(item.servicecontract_id || item.id, 10) || 0;
			var accountId = parseInt(item.account_id, 10) || 0;
			setHiddenRef($f, 'potential_id', 0, '');
			setHiddenRef($f, 'contact_id', 0, '');
			$display.val(label).data('mkCustomerModule', 'ServiceContracts').data('mkServiceContractId', scId);
			$f.find('[name="subject"]').val(label).trigger('change');
			ensureServiceContractLinkFields($f, scId);
			if (accountId > 0 && $f.find('[name="account_id"]').length) {
				$f.find('[name="account_id"]').val(String(accountId));
				var $accDisp = $f.find('[name="account_id_display"]');
				if ($accDisp.length) {
					$accDisp.val($.trim(item.extra || label));
				}
			}
			if (item.phone && $f.find('[name="mk_customer_phone"]').length) {
				var phone = item.phone;
				if (window.MkPhoneFormat && typeof window.MkPhoneFormat.format === 'function') {
					phone = window.MkPhoneFormat.format(phone) || phone;
				}
				$f.find('[name="mk_customer_phone"]').val(phone);
			}
			if (item.email && $f.find('[name="mk_customer_email"]').length) {
				$f.find('[name="mk_customer_email"]').val(item.email);
			}
			applyQuotePriceChannel('tuibao', {
				scPrefill: {
					id: scId,
					name: label,
					phone: item.phone || '',
					email: item.email || '',
					account_id: accountId
				}
			});
		} else if (item.module === 'Contacts') {
			setHiddenRef($f, 'contact_id', item.contact_id || item.id, label);
			setHiddenRef($f, 'potential_id', 0, '');
			ensureServiceContractLinkFields($f, 0);
			$f.find('[name="subject"]').val(label).trigger('change');
			applyQuotePriceChannel('retail', { clearSc: true });
		} else if (item.module === 'Potentials') {
			setHiddenRef($f, 'potential_id', item.potential_id || item.id, label);
			if (item.contact_id) {
				setHiddenRef($f, 'contact_id', item.contact_id, label);
			} else {
				setHiddenRef($f, 'contact_id', 0, '');
				// Keep display label even without contact_id (subject carries the name).
				$display.val(label);
			}
			ensureServiceContractLinkFields($f, 0);
			$f.find('[name="subject"]').val(label).trigger('change');
			$f.find('[name="potential_id"]').trigger('change');
			applyQuotePriceChannel('retail', { clearSc: true });
		} else if (item.module === 'Leads') {
			setHiddenRef($f, 'potential_id', 0, '');
			setHiddenRef($f, 'contact_id', 0, '');
			ensureServiceContractLinkFields($f, 0);
			$display.val(label);
			$f.find('[name="subject"]').val(label).trigger('change');
			applyQuotePriceChannel('retail', { clearSc: true });
		}

		$f.find('.mk-qt-customer-ref .clearReferenceSelection').removeClass('hide');
		$f.find('.mk-qt-customer-ref').addClass('selected');
		closeCustomerSearchUi();
		syncCustomerInfoButtonVisibility();
	}

	function searchQuoteCustomers(q, scope) {
		var deferred = $.Deferred();
		q = $.trim(q || '');
		scope = scope || 'all';
		if (!(window.app && app.request && app.request.post)) {
			deferred.reject(new Error('No request'));
			return deferred.promise();
		}
		app.request
			.post({
				data: {
					module: 'Quotes',
					action: 'SearchCustomer',
					q: q,
					scope: scope,
					limit: 40
				}
			})
			.then(function (err, res) {
				if (err || !res || res.success === false) {
					deferred.resolve({
						results: [],
						grouped: { Contacts: [], Potentials: [], Leads: [], ServiceContracts: [] },
						counts: {}
					});
					return;
				}
				deferred.resolve({
					results: res.results || (res.result && res.result.results) || [],
					grouped: res.grouped || (res.result && res.result.grouped) || {
						Contacts: [],
						Potentials: [],
						Leads: [],
						ServiceContracts: []
					},
					counts: res.counts || (res.result && res.result.counts) || {}
				});
			});
		return deferred.promise();
	}

	function closeCustomerSearchUi() {
		$('#mk-qt-customer-ac, #mk-qt-customer-modal').remove();
		$('body').removeClass('mk-qt-customer-modal-open');
		$('body > .modal-backdrop').remove();
	}

	function escHtml(s) {
		return $('<div/>').text(s == null ? '' : String(s)).html();
	}

	function customerItemTone(moduleName) {
		if (moduleName === 'Potentials') {
			return 'opp';
		}
		if (moduleName === 'Leads') {
			return 'lead';
		}
		if (moduleName === 'ServiceContracts' || moduleName === 'Franchise') {
			return 'franchise';
		}
		return 'contact';
	}

	function renderCustomerResultList(items) {
		if (!items || !items.length) {
			return (
				'<div class="mk-qt-customer-empty">' +
				'<span class="mk-qt-customer-empty__ico" aria-hidden="true">⌕</span>' +
				'<strong>Không có kết quả</strong>' +
				'<p>Thử từ khóa khác hoặc đổi tab Opp / Leads / Khách hàng / Nhượng quyền</p>' +
				'</div>'
			);
		}
		return items
			.map(function (it, idx) {
				var tone = customerItemTone(it.module);
				return (
					'<button type="button" class="mk-qt-customer-item mk-qt-customer-item--' +
					tone +
					'" data-idx="' +
					idx +
					'">' +
					'<span class="mk-qt-customer-item__avatar" aria-hidden="true">' +
					escHtml((it.label || '?').charAt(0).toUpperCase()) +
					'</span>' +
					'<span class="mk-qt-customer-item__main">' +
					'<span class="mk-qt-customer-item__label">' +
					escHtml(it.label || '') +
					'</span>' +
					(it.subtitle
						? '<span class="mk-qt-customer-item__sub">' + escHtml(it.subtitle) + '</span>'
						: '') +
					'</span>' +
					'<span class="mk-qt-customer-item__badge">' +
					escHtml(it.module_label || it.module || '') +
					'</span>' +
					'</button>'
				);
			})
			.join('');
	}

	function showCustomerAutocomplete($input, items) {
		$('#mk-qt-customer-ac').remove();
		if (!$input || !$input.length || !items || !items.length) {
			return;
		}
		var $box = $('<div id="mk-qt-customer-ac" class="mk-qt-customer-ac" role="listbox"></div>');
		$box.html(renderCustomerResultList(items));
		$('body').append($box);
		var rect = $input[0].getBoundingClientRect();
		$box.css({
			top: rect.bottom + window.scrollY + 4,
			left: Math.max(8, rect.left + window.scrollX),
			width: Math.max(rect.width, 360)
		});
		$box.on('mousedown', '.mk-qt-customer-item', function (e) {
			e.preventDefault();
			var idx = parseInt($(this).attr('data-idx'), 10);
			if (!isNaN(idx) && items[idx]) {
				applyUnifiedCustomerSelection(items[idx]);
			}
		});
	}

	function openCustomerSearchModal(initialQ) {
		closeCustomerSearchUi();
		// Close stock Vtiger popup if it already opened.
		try {
			$('.myModal, .modal-backdrop, #popupModal').remove();
			$('body').removeClass('modal-open');
		} catch (e) {}

		var state = {
			tab: 'Potentials',
			q: $.trim(initialQ || ''),
			grouped: { Contacts: [], Potentials: [], Leads: [], ServiceContracts: [] },
			counts: { Contacts: 0, Potentials: 0, Leads: 0, ServiceContracts: 0 },
			visible: []
		};

		var $modal = $(
			'<div id="mk-qt-customer-modal" class="mk-qt-customer-modal" role="dialog" aria-modal="true">' +
				'<div class="mk-qt-customer-modal__backdrop" data-mk-cust-close="1"></div>' +
				'<div class="mk-qt-customer-modal__panel">' +
				'<header class="mk-qt-customer-modal__head">' +
				'<div class="mk-qt-customer-modal__head-text">' +
				'<span class="mk-qt-customer-modal__eyebrow">Báo giá · chọn nguồn</span>' +
				'<h3>Chọn khách hàng</h3>' +
				'</div>' +
				'<button type="button" class="mk-qt-customer-modal__x" data-mk-cust-close="1" aria-label="Đóng">&times;</button>' +
				'</header>' +
				'<div class="mk-qt-customer-modal__search">' +
				'<span class="mk-qt-customer-modal__search-ico" aria-hidden="true">⌕</span>' +
				'<input type="search" class="mk-qt-customer-modal__input" placeholder="Tìm tên, SĐT, công ty… (áp dụng cả 4 tab)" autocomplete="off" />' +
				'</div>' +
				'<div class="mk-qt-customer-modal__tabs" role="tablist">' +
				'<button type="button" class="mk-qt-customer-tab is-on" data-tab="Potentials" role="tab">' +
				'<span class="mk-qt-customer-tab__dot mk-qt-customer-tab__dot--opp"></span>Opp' +
				'<em class="mk-qt-customer-tab__count" data-count="Potentials">0</em></button>' +
				'<button type="button" class="mk-qt-customer-tab" data-tab="Leads" role="tab">' +
				'<span class="mk-qt-customer-tab__dot mk-qt-customer-tab__dot--lead"></span>Leads' +
				'<em class="mk-qt-customer-tab__count" data-count="Leads">0</em></button>' +
				'<button type="button" class="mk-qt-customer-tab" data-tab="Contacts" role="tab">' +
				'<span class="mk-qt-customer-tab__dot mk-qt-customer-tab__dot--contact"></span>Khách hàng' +
				'<em class="mk-qt-customer-tab__count" data-count="Contacts">0</em></button>' +
				'<button type="button" class="mk-qt-customer-tab" data-tab="ServiceContracts" role="tab" title="Khách nhượng quyền · giá Tuibao">' +
				'<span class="mk-qt-customer-tab__dot mk-qt-customer-tab__dot--franchise"></span>Nhượng quyền' +
				'<em class="mk-qt-customer-tab__count" data-count="ServiceContracts">0</em></button>' +
				'</div>' +
				'<div class="mk-qt-customer-modal__price-hint" id="mk-qt-customer-price-hint">' +
				'Opp / Leads / Khách hàng → <strong>giá lẻ</strong> · Nhượng quyền → <strong>giá Tuibao</strong>' +
				'</div>' +
				'<div class="mk-qt-customer-modal__body">' +
				'<div class="mk-qt-customer-modal__list"></div>' +
				'</div></div></div>'
		);
		$('body').append($modal).addClass('mk-qt-customer-modal-open');
		var $input = $modal.find('.mk-qt-customer-modal__input');
		var $list = $modal.find('.mk-qt-customer-modal__list');
		var timer = null;
		var killStock = setInterval(function () {
			if (!$('#mk-qt-customer-modal').length) {
				clearInterval(killStock);
				return;
			}
			$('.myModal, #popupModal').remove();
			$('body > .modal-backdrop').remove();
		}, 80);

		function updateCounts() {
			['Contacts', 'Potentials', 'Leads', 'ServiceContracts'].forEach(function (key) {
				var n = (state.grouped[key] || []).length;
				state.counts[key] = n;
				$modal.find('[data-count="' + key + '"]').text(String(n));
			});
		}

		function renderActiveTab() {
			state.visible = state.grouped[state.tab] || [];
			$list.html(renderCustomerResultList(state.visible));
			$modal.find('.mk-qt-customer-tab').each(function () {
				$(this).toggleClass('is-on', $(this).attr('data-tab') === state.tab);
			});
			var $hint = $modal.find('#mk-qt-customer-price-hint');
			if (state.tab === 'ServiceContracts') {
				$hint.html('Nguồn <strong>Nhượng quyền</strong> → hàng hoá áp <strong>giá Tuibao</strong>');
			} else {
				$hint.html('Opp / Leads / Khách hàng → <strong>giá lẻ</strong> · Nhượng quyền → <strong>giá Tuibao</strong>');
			}
		}

		function runSearch(q) {
			state.q = $.trim(q || '');
			$list.html('<div class="mk-qt-customer-empty mk-qt-customer-empty--loading">Đang tải...</div>');
			searchQuoteCustomers(state.q, 'all').then(function (payload) {
				state.grouped = payload.grouped || {
					Contacts: [],
					Potentials: [],
					Leads: [],
					ServiceContracts: []
				};
				updateCounts();
				renderActiveTab();
			});
		}

		$modal.on('click', '[data-mk-cust-close]', function () {
			closeCustomerSearchUi();
		});
		$modal.on('click', '.mk-qt-customer-tab', function () {
			state.tab = $(this).attr('data-tab') || 'Contacts';
			renderActiveTab();
		});
		$modal.on('mousedown', '.mk-qt-customer-item', function (e) {
			e.preventDefault();
			var idx = parseInt($(this).attr('data-idx'), 10);
			if (!isNaN(idx) && state.visible[idx]) {
				applyUnifiedCustomerSelection(state.visible[idx]);
			}
		});
		$input.on('input', function () {
			var q = $.trim($input.val() || '');
			clearTimeout(timer);
			timer = setTimeout(function () {
				runSearch(q);
			}, 200);
		});
		$input.val(state.q);
		runSearch(state.q);
		setTimeout(function () {
			$input.trigger('focus');
		}, 40);
	}

	function registerUnifiedCustomerPicker() {
		var $f = $form();
		if (!$f.length || $f.data('mkUnifiedCustomerBound')) {
			return;
		}
		$f.data('mkUnifiedCustomerBound', 1);

		var $display = $f.find('[name="contact_id_display"]').first();
		if (!$display.length) {
			return;
		}

		// Disable stock autocomplete; we use multi-module search instead.
		try {
			if ($display.data('autocomplete')) {
				$display.autocomplete('destroy');
			}
		} catch (e) {}
		$display.removeClass('ui-autocomplete-input');

		var acTimer = null;
		$display.on('input.mkQtCustomer', function () {
			var q = $.trim($display.val() || '');
			clearTimeout(acTimer);
			if (!q) {
				clearQuoteCustomerFields();
				$('#mk-qt-customer-ac').remove();
				return;
			}
			acTimer = setTimeout(function () {
				searchQuoteCustomers(q, 'all').then(function (payload) {
					showCustomerAutocomplete($display, payload.results || []);
				});
			}, 220);
		});

		// Capture phase on document so stock Vtiger popup never opens.
		document.addEventListener(
			'click',
			function (e) {
				var btn = e.target && e.target.closest
					? e.target.closest(
							'.mk-qt-customer-ref .relatedPopup, tr.mk-qt-customer-row .relatedPopup, [data-mk-custom-search="1"]'
					  )
					: null;
				if (!btn) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				if (e.stopImmediatePropagation) {
					e.stopImmediatePropagation();
				}
				openCustomerSearchModal($.trim($display.val() || ''));
			},
			true
		);

		$f.on('click.mkQtCustomerSearch', '.mk-qt-customer-ref .relatedPopup, tr.mk-qt-customer-row .relatedPopup', function (e) {
			e.preventDefault();
			e.stopPropagation();
			openCustomerSearchModal($.trim($display.val() || ''));
			return false;
		});

		$f.on('click.mkQtCustomerClear', '.mk-qt-customer-ref .clearReferenceSelection', function () {
			setTimeout(clearQuoteCustomerFields, 0);
		});

		// + creates Contact via stock Quick Create (popupReferenceModule=Contacts).
		$f.find('.mk-qt-customer-ref input[name="popupReferenceModule"]').val('Contacts');

		$f.on(Vtiger_Edit_Js.referenceSelectionEvent + '.mkQtCustomer', '[name="contact_id"]', function () {
			var id = parseInt($f.find('[name="contact_id"]').val(), 10) || 0;
			var label = $.trim($f.find('[name="contact_id_display"]').val() || '');
			if (id > 0 && label) {
				$f.find('[name="subject"]').val(label).trigger('change');
				$f.find('.mk-qt-customer-ref .clearReferenceSelection').removeClass('hide');
				$f.find('.mk-qt-customer-ref').addClass('selected');
				$f.find('[name="contact_id_display"]').data('mkCustomerModule', 'Contacts');
				ensureServiceContractLinkFields($f, 0);
				applyQuotePriceChannel('retail', { clearSc: true });
			}
			syncCustomerInfoButtonVisibility();
		});

		$(document).on('mousedown.mkQtCustomerOutside', function (e) {
			if (!$(e.target).closest('#mk-qt-customer-ac, #mk-qt-customer-modal, .mk-qt-customer-ref').length) {
				$('#mk-qt-customer-ac').remove();
			}
		});
	}

	function hideQuoteFieldPair(name) {
		$form()
			.find('[name="' + name + '"], [name="' + name + '_display"]')
			.each(function () {
				var $valueTd = $(this).closest('td.fieldValue');
				if ($valueTd.length) {
					$valueTd.addClass('mk-qt-hide-legacy');
					$valueTd.prev('td.fieldLabel').addClass('mk-qt-hide-legacy');
					return;
				}
				$(this).closest('tr').addClass('mk-qt-hide-legacy');
			});
	}

	function ensureSaveModeField() {
		var $f = $form();
		if (!$f.length) {
			return $();
		}
		var $mode = $f.find('input[name="mk_quote_save_mode"]');
		if (!$mode.length) {
			$mode = $('<input type="hidden" name="mk_quote_save_mode" value="draft" />');
			$f.append($mode);
		}
		var $ajax = $f.find('input[name="mk_quote_ajax"]');
		if (!$ajax.length) {
			$ajax = $('<input type="hidden" name="mk_quote_ajax" value="0" />');
			$f.append($ajax);
		}
		return $mode;
	}

	function setQuoteSaveMode(mode) {
		var $mode = ensureSaveModeField();
		$mode.val(mode === 'confirm' ? 'confirm' : 'draft');
		$form().find('input[name="mk_quote_ajax"]').val('0');
	}

	function pickQuoteStageValue($stage, preferred) {
		var pick = '';
		(preferred || []).forEach(function (cand) {
			if (pick) {
				return;
			}
			$stage.find('option').each(function () {
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
		return pick;
	}

	function ensureDraftQuoteStage() {
		var $stage = $form().find('select[name="quotestage"], [name="quotestage"]').first();
		if (!$stage.length) {
			return;
		}
		var preferred = ['Nháp', 'Created', 'Draft', 'Đã tạo'];
		var pick = pickQuoteStageValue($stage, preferred) || 'Nháp';
		$stage.val(pick);
		$('#mkQtRailStage, #mkQtHeadStageBadge').text('Nháp');
	}

	function ensureConfirmedQuoteStage() {
		var $stage = $form().find('select[name="quotestage"], [name="quotestage"]').first();
		if (!$stage.length) {
			return;
		}
		var preferred = ['Báo giá', 'Xác nhận', 'Accepted', 'Confirmed', 'Chấp nhận', 'Delivered'];
		var pick = pickQuoteStageValue($stage, preferred) || 'Accepted';
		$stage.val(pick);
		$('#mkQtRailStage, #mkQtHeadStageBadge').text('Báo giá');
	}

	function quoteCanDraftSave() {
		var $f = $form();
		if (!$f.length || isSalesOrder()) {
			return false;
		}
		var subject = $.trim($f.find('[name="subject"]').val() || '');
		var contactId = $.trim($f.find('[name="contact_id"]').val() || '');
		var potentialId = $.trim($f.find('[name="potential_id"]').val() || '');
		return !!(subject || contactId || potentialId);
	}

	function quoteHasLineItems($f) {
		$f = $f || $form();
		var hasLine = false;
		$f.find('input[name^="hdnProductId"], input.selectedModuleId').each(function () {
			if ($.trim($(this).val() || '') !== '') {
				hasLine = true;
				return false;
			}
		});
		if (hasLine) {
			return true;
		}
		$f.find('#lineItemTab tr.lineItemRow')
			.not('.hide, .lineItemCloneCopy, #row0')
			.each(function () {
				var id = $.trim(
					$(this)
						.find('input.selectedModuleId, input[name^="hdnProductId"]')
						.first()
						.val() || ''
				);
				if (id) {
					hasLine = true;
					return false;
				}
			});
		return hasLine;
	}

	function showQuoteError(message) {
		var msg = $.trim(String(message || '')) || 'Không thể lưu báo giá. Vui lòng kiểm tra lại thông tin bắt buộc.';
		if (window.app && app.helper && app.helper.showErrorNotification) {
			app.helper.showErrorNotification({ message: msg });
			return;
		}
		window.alert(msg);
	}

	function parseQuoteSaveError(res, xhr) {
		if (res && res.error) {
			return res.error.message || res.error.code || String(res.error);
		}
		if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
			return xhr.responseJSON.error.message || xhr.responseJSON.error.code;
		}
		if (xhr && xhr.responseText) {
			try {
				var parsed = JSON.parse(xhr.responseText);
				if (parsed && parsed.error) {
					return parsed.error.message || parsed.error.code;
				}
			} catch (e) {
				/* ignore */
			}
		}
		return 'Không thể lưu báo giá. Vui lòng kiểm tra lại thông tin bắt buộc.';
	}

	function validateQuoteBeforeSave(mode) {
		var $f = $form();
		var subject = $.trim($f.find('[name="subject"]').val() || '');
		var contactId = $.trim($f.find('[name="contact_id"]').val() || '');
		var potentialId = $.trim($f.find('[name="potential_id"]').val() || '');
		if (!subject && !contactId && !potentialId) {
			return 'Vui lòng chọn Khách hàng trước khi lưu báo giá.';
		}
		if (mode === 'confirm' && !quoteHasLineItems($f)) {
			return 'Vui lòng thêm ít nhất một hàng hóa/dịch vụ trước khi lưu báo giá.';
		}
		return '';
	}

	function getQuoteRecordId() {
		return $.trim($form().find('[name="record"], #recordId').first().val() || '');
	}

	function applyDraftRecordId(recordId) {
		recordId = String(recordId || '');
		if (!recordId || !/^\d+$/.test(recordId)) {
			return;
		}
		var $f = $form();
		var $record = $f.find('[name="record"], #recordId').first();
		if ($record.length) {
			$record.val(recordId);
		} else {
			$f.append($('<input type="hidden" name="record" />').val(recordId));
		}
		try {
			var params = new URLSearchParams(window.location.search || '');
			if (params.get('record') !== recordId) {
				params.set('record', recordId);
				params.set('module', 'Quotes');
				params.set('view', 'Edit');
				if (!params.get('app')) {
					params.set('app', 'SALES');
				}
				var next =
					window.location.pathname +
					'?' +
					params.toString() +
					(window.location.hash || '');
				if (window.history && window.history.replaceState) {
					window.history.replaceState({}, document.title, next);
				}
			}
		} catch (e) {
			/* ignore */
		}
	}

	function getInventoryEditInstance() {
		try {
			if (typeof Quotes_Edit_Js !== 'undefined' && Quotes_Edit_Js.getInstance) {
				return Quotes_Edit_Js.getInstance();
			}
			if (typeof SalesOrder_Edit_Js !== 'undefined' && SalesOrder_Edit_Js.getInstance) {
				return SalesOrder_Edit_Js.getInstance();
			}
			if (typeof Inventory_Edit_Js !== 'undefined' && Inventory_Edit_Js.getInstance) {
				return Inventory_Edit_Js.getInstance();
			}
		} catch (e) {
			/* ignore */
		}
		return null;
	}

	function syncAddressRailToForm() {
		var $f = $form();
		if (!$f.length) {
			return;
		}
		if (window.MkQuoteBa && typeof MkQuoteBa.syncAddressRailToForm === 'function') {
			MkQuoteBa.syncAddressRailToForm($f);
		}
	}

	function syncFormBeforeSave() {
		syncTermsToSource();
		syncAddressRailToForm();
		syncInventoryFormBeforeSave();
	}

	function syncInventoryFormBeforeSave() {
		var $f = $form();
		if (!$f.length) {
			return;
		}
		var inst = getInventoryEditInstance();
		if (inst) {
			try {
				if (typeof inst.updateLineItemElementByOrder === 'function') {
					inst.updateLineItemElementByOrder();
				}
				if (typeof inst.saveProductCount === 'function') {
					inst.saveProductCount();
				}
				if (typeof inst.saveSubTotalValue === 'function') {
					inst.saveSubTotalValue();
				}
				if (typeof inst.saveTotalValue === 'function') {
					inst.saveTotalValue();
				}
				if (typeof inst.savePreTaxTotalValue === 'function') {
					inst.savePreTaxTotalValue();
				}
			} catch (e) {
				/* ignore */
			}
			return;
		}
		var rowCount = $f
			.find('#lineItemTab tr.lineItemRow')
			.not('.hide, .lineItemCloneCopy, #row0')
			.length;
		if (rowCount > 0) {
			$f.find('#totalProductCount').val(rowCount);
		}
	}

	function saveQuoteAjax(opts) {
		opts = opts || {};
		if (isSalesOrder()) {
			return $.Deferred().reject().promise();
		}
		var mode = opts.mode === 'confirm' ? 'confirm' : 'draft';
		if (mode === 'draft' && !opts.force && !quoteCanDraftSave()) {
			return $.Deferred().reject().promise();
		}
		var validationErr = validateQuoteBeforeSave(mode);
		if (validationErr) {
			if (!opts.silent) {
				showQuoteError(validationErr);
			}
			return $.Deferred().reject(validationErr).promise();
		}
		if (quoteSaveInFlight) {
			return $.Deferred().reject().promise();
		}

		var $f = $form();
		if (!$f.length || !$f[0]) {
			return $.Deferred().reject().promise();
		}
		syncFormBeforeSave();
		setQuoteSaveMode(mode);
		if (mode === 'confirm') {
			ensureConfirmedQuoteStage();
		} else {
			ensureDraftQuoteStage();
		}

		var openPrint = opts.openPrint === true || $f.find('#mkQtOpenPrint').val() === '1';
		$f.find('input[name="mk_quote_ajax"]').val('1');
		$f.find('#mkQtOpenPrint').val(openPrint ? '1' : '0');

		var deferred = $.Deferred();
		quoteSaveInFlight = true;
		var formData = new FormData($f[0]);
		formData.set('module', 'Quotes');
		formData.set('action', 'Save');
		formData.set('mk_quote_save_mode', mode);
		formData.set('mk_quote_ajax', '1');
		formData.set('mk_open_print', openPrint ? '1' : '0');

		var finish = function (ok, payload) {
			quoteSaveInFlight = false;
			$f.find('input[name="mk_quote_ajax"]').val('0');
			$f.find('#mkQtOpenPrint').val('0');
			if (ok) {
				formDirty = false;
				if (payload && payload.record) {
					applyDraftRecordId(payload.record);
				}
				if (mode === 'draft') {
					var $el = $('#mkQtAutosave');
					$el.removeClass('is-dirty').addClass('is-saved');
					$el.find('.mk-qt-autosave__text').text('Đã lưu nháp');
				}
				deferred.resolve(payload || {});
			} else {
				deferred.reject(payload);
			}
		};

		if (opts.keepalive && window.fetch) {
			try {
				window.fetch('index.php', {
					method: 'POST',
					body: formData,
					credentials: 'same-origin',
					keepalive: true,
				});
				finish(true, {});
			} catch (e) {
				finish(false, e);
			}
			return deferred.promise();
		}

		$('#mkQtSaveTop, #mkQtSaveSendTop').prop('disabled', true);

		$.ajax({
			url: 'index.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
		})
			.done(function (res) {
				var result = res && (res.result || res);
				if (result && (result.success || result.record)) {
					finish(true, result);
					return;
				}
				var errMsg = parseQuoteSaveError(res);
				if (!opts.silent) {
					showQuoteError(errMsg);
				}
				finish(false, errMsg);
			})
			.fail(function (xhr) {
				var errMsg = parseQuoteSaveError(null, xhr);
				if (!opts.silent) {
					showQuoteError(errMsg);
				}
				finish(false, errMsg);
			})
			.always(function () {
				$('#mkQtSaveTop, #mkQtSaveSendTop').prop('disabled', false);
			});

		return deferred.promise();
	}

	function saveQuoteDraftAjax(opts) {
		opts = opts || {};
		if (isSalesOrder() || confirmSaveRequested || draftSaveInFlight) {
			return $.Deferred().reject().promise();
		}
		if (!quoteCanDraftSave()) {
			return $.Deferred().reject().promise();
		}
		draftSaveInFlight = true;
		return saveQuoteAjax(
			$.extend({}, opts, {
				mode: 'draft',
				silent: true,
			})
		)
			.always(function () {
				draftSaveInFlight = false;
			});
	}

	function scheduleDraftAutosave() {
		if (isSalesOrder() || confirmSaveRequested) {
			return;
		}
		clearTimeout(draftAutosaveTimer);
		draftAutosaveTimer = setTimeout(function () {
			if (!formDirty || confirmSaveRequested) {
				return;
			}
			saveQuoteDraftAjax();
		}, 2500);
	}

	function buildContactReferenceHtml() {
		return (
			'<div class="referencefield-wrapper mk-qt-contact-ref">' +
				'<input name="popupReferenceModule" type="hidden" value="Contacts"/>' +
				'<div class="input-group">' +
					'<input name="contact_id" type="hidden" value="" class="sourceField" data-displayvalue=""/>' +
					'<input id="contact_id_display" name="contact_id_display" data-fieldname="contact_id" data-fieldtype="reference" type="text" ' +
						'class="marginLeftZero autoComplete inputElement" value="" placeholder="Nhập để tìm kiếm"/>' +
					'<a href="#" class="clearReferenceSelection hide" tabindex="-1"> x </a>' +
					'<span class="input-group-addon relatedPopup cursorPointer" title="Tìm kiếm"><i class="fa fa-search"></i></span>' +
					'<span class="input-group-addon createReferenceRecord cursorPointer clearfix" title="Tạo mới"><i class="fa fa-plus"></i></span>' +
				'</div>' +
			'</div>'
		);
	}

	function registerInjectedContactEvents($container) {
		if (!$container || !$container.length) {
			return;
		}
		var editInstance = null;
		try {
			if (typeof Quotes_Edit_Js !== 'undefined' && Quotes_Edit_Js.getInstance) {
				editInstance = Quotes_Edit_Js.getInstance();
			} else if (typeof Inventory_Edit_Js !== 'undefined' && Inventory_Edit_Js.getInstance) {
				editInstance = Inventory_Edit_Js.getInstance();
			} else if (typeof Vtiger_Edit_Js !== 'undefined' && Vtiger_Edit_Js.getInstance) {
				editInstance = Vtiger_Edit_Js.getInstance();
			}
		} catch (e) {
			editInstance = null;
		}
		if (!editInstance) {
			return;
		}
		if (typeof editInstance.registerAutoCompleteFields === 'function') {
			editInstance.registerAutoCompleteFields($container);
		}
		if (typeof editInstance.registerClearReferenceSelectionEvent === 'function') {
			editInstance.registerClearReferenceSelectionEvent($container);
		}
		if (typeof editInstance.registerReferenceCreate === 'function') {
			editInstance.registerReferenceCreate($container);
		}
		// Do not register stock relatedPopup for customer field — tabbed modal handles search.
		if (
			!$container.closest('.mk-qt-customer-ref, tr.mk-qt-customer-row').length &&
			typeof editInstance.referenceModulePopupRegisterEvent === 'function'
		) {
			editInstance.referenceModulePopupRegisterEvent($container);
		}
	}

	function injectContactField() {
		var $f = $form();
		if ($f.find('[name="contact_id"]').length || $f.find('.mk-qt-contact-injected').length) {
			return;
		}

		var $html = $(
			'<tr class="mk-qt-contact-injected">' +
				'<td class="fieldLabel alignMiddle">' +
					'<label class="muted">Người liên hệ</label>' +
				'</td>' +
				'<td class="fieldValue">' + buildContactReferenceHtml() + '</td>' +
				'<td class="fieldLabel"></td>' +
				'<td class="fieldValue"></td>' +
			'</tr>'
		);

		var $subjectRow = $f.find('[name="subject"]').closest('tr');
		var $validRow = $f.find('[name="validtill"], #Quotes_editView_fieldName_validtill').closest('tr');
		var $potentialRow = $f.find('[name="potential_id"]').closest('tr');
		var $infoBody = $f
			.find('.fieldBlockContainer[data-block="LBL_QUOTE_INFORMATION"] table.table-borderless > tbody')
			.first();

		// Prefer left column under Title: fill empty/hidden left of validtill row when possible
		if ($validRow.length) {
			var $leftLabel = $validRow.children('td.fieldLabel').first();
			var $leftValue = $validRow.children('td.fieldValue').first();
			var $rightValue = $validRow.children('td.fieldValue').last();
			var leftIsValidtill = $leftValue.find('[name="validtill"]').length > 0;
			var rightIsValidtill = $rightValue.find('[name="validtill"]').length > 0;
			var leftHidden = $leftLabel.hasClass('mk-qt-hide-legacy') || $leftValue.hasClass('mk-qt-hide-legacy');
			var leftEmpty = !$leftValue.children().length;

			if (rightIsValidtill && !leftIsValidtill && (leftHidden || leftEmpty || $leftValue.find('[name="quotestage"]').length)) {
				// Keep quotestage in DOM (hidden) for draft submit
				var $stage = $leftValue.find('[name="quotestage"]').detach();
				$leftLabel.removeClass('mk-qt-hide-legacy mk-inv-hide-legacy');
				$leftValue.removeClass('mk-qt-hide-legacy mk-inv-hide-legacy');
				$leftLabel.html('<label class="muted">Người liên hệ</label>');
				$leftValue.empty().append(buildContactReferenceHtml());
				if ($stage.length) {
					$f.append($stage.addClass('mk-qt-hide-legacy').css({ position: 'absolute', left: '-9999px', width: 1, height: 1, opacity: 0 }));
				}
				$leftValue.addClass('mk-qt-contact-injected');
				registerInjectedContactEvents($leftValue);
				return;
			}
		}

		if ($subjectRow.length) {
			$html.insertAfter($subjectRow);
		} else if ($potentialRow.length) {
			$html.insertAfter($potentialRow);
		} else if ($infoBody.length) {
			$infoBody.append($html);
		} else {
			return;
		}
		registerInjectedContactEvents($html);
	}

	function ensureContactFieldVisible() {
		ensureCustomerFieldVisible();
	}

	function pinTotalsBelowOrderDetails() {
		if (isSalesOrder()) {
			return;
		}
		var $editForm = $form();
		var $items = $editForm.find('#lineItemTab').closest('.fieldBlockContainer').first();
		var $totals = $editForm.find('#lineItemResult').closest('.fieldBlockContainer').first();
		if (!$items.length || !$totals.length) {
			return;
		}
		// Keep totals as the last section inside "Chi tiết đơn hàng".
		if (!$items.find('#lineItemResult').length) {
			$items.append($totals.detach());
		}
		$totals = $items.find('#lineItemResult').closest('.fieldBlockContainer').first();
		$totals.addClass('mk-qt-totals-below mk-inv-totals-odoo mk-qt-block mk-qt-block--totals');

		var $result = $items.find('#lineItemResult');
		if (!$result.length) {
			return;
		}
		// Always keep: Số tiền trước thuế + Thuế GTGT + Tổng cộng
		var $preEl = $result.find('#preTaxTotal').first();
		var $preTax = $preEl.length ? $preEl.closest('tr') : $();
		if (!$preTax.length) {
			var $anchor = $result.find('#group_tax_row').first();
			if (!$anchor.length) {
				$anchor = $result.find('#grandTotal, .grandTotal').closest('tr').first();
			}
			var $injected = $(
				'<tr class="mk-inv-totals-row mk-inv-totals-row--sub" data-mk-totals-row="pre-tax">' +
					'<td><div class="mk-inv-totals-label">Số tiền trước thuế</div></td>' +
					'<td><span class="pull-right mk-inv-vnd-amount" id="preTaxTotal">0</span>' +
					'<input type="hidden" id="pre_tax_total" name="pre_tax_total" value="0" /></td></tr>'
			);
			if ($anchor.length) {
				$injected.insertBefore($anchor);
			} else {
				$result.prepend($injected);
			}
			$preTax = $injected;
		}
		var $net = $result.find('#netTotal, .netTotal').closest('tr');
		var $sub = $preTax.length ? $preTax : $net;
		var $tax = $result.find('#group_tax_row');
		var $grand = $result.find('#grandTotal, .grandTotal').closest('tr');

		if ($sub.length) {
			$sub
				.removeClass('mk-inv-totals-hide hide')
				.addClass('mk-inv-totals-row mk-inv-totals-row--sub')
				.attr('data-mk-totals-row', 'pre-tax')
				.show()
				.css({ display: 'table-row', visibility: 'visible' });
			if (!$sub.find('.mk-inv-totals-label').length) {
				$sub.find('td:first').html('<div class="mk-inv-totals-label">Số tiền trước thuế</div>');
			} else {
				$sub.find('.mk-inv-totals-label').first().text('Số tiền trước thuế');
			}
			$sub.find('#preTaxTotal, #netTotal, .netTotal').addClass('mk-inv-vnd-amount');
			// Hide the duplicate net row when preTax is used.
			if ($preTax.length && $net.length && !$net.is($preTax)) {
				$net.addClass('mk-inv-totals-hide').hide();
			}
		}
		if ($tax.length) {
			$tax
				.removeClass('mk-inv-totals-hide hide')
				.addClass('mk-inv-totals-row mk-inv-totals-row--tax')
				.show();
			if (!$tax.find('.mk-inv-totals-label').length) {
				$tax.find('td:first').html('<div class="mk-inv-totals-label">Thuế GTGT</div>');
			}
		}
		if ($grand.length) {
			$grand
				.removeClass('mk-inv-totals-hide hide')
				.addClass('mk-inv-totals-row mk-inv-totals-row--grand')
				.show();
			if (!$grand.find('.mk-inv-totals-label').length) {
				$grand.find('td:first').html('<div class="mk-inv-totals-label">Tổng cộng</div>');
			}
		}
	}

	function reorderQuoteBlocks() {
		var $editForm = $form();
		var infoBlock = isSalesOrder() ? 'LBL_SO_INFORMATION' : 'LBL_QUOTE_INFORMATION';
		var $info = $editForm.find('.fieldBlockContainer[data-block="' + infoBlock + '"]').first();
		var $items = $editForm.find('#lineItemTab').closest('.fieldBlockContainer').first();
		var $addr = $editForm.find('.fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"]').first();
		var $terms = $editForm.find('.fieldBlockContainer[data-block="LBL_TERMS_INFORMATION"]').first();
		// Main column: line items first (totals pinned inside that card).
		if (!isSalesOrder() && $items.length) {
			var $host = $editForm.find('[name="editContent"]').first();
			if (!$host.length) {
				$host = $editForm.find('.editViewContents').first();
			}
			if ($host.length) {
				var $main = $host.children('.mk-qt-edit-main').first();
				if (!$main.length) {
					$main = $('<div class="mk-qt-edit-main"></div>');
					$host.prepend($main);
				}
				$main.append($items.detach());
			}
			pinTotalsBelowOrderDetails();
		} else if ($info.length && $items.length) {
			$items.insertAfter($info);
		}
		if ($addr.length) {
			$addr.addClass('mk-qt-hide-legacy');
		}
		if ($terms.length) {
			$terms.addClass('mk-qt-hide-legacy');
		}
		if ($terms.length && !isSalesOrder()) {
			var $h = $terms.find('.fieldBlockHeader').first();
			if ($h.length) {
				$h.contents().filter(function () { return this.nodeType === 3; }).each(function () {
					this.nodeValue = String(this.nodeValue || '').replace(/Điều khoản.*$/i, 'Ghi chú');
				});
				if ($.trim($h.text()) === '' || /LBL_TERMS_INFORMATION/.test($h.text())) {
					$h.text('Ghi chú');
				}
			}
		}
	}

	function initBaForm() {
		if (!window.MkQuoteBa) {
			return;
		}
		MkQuoteBa.init($form());
	}

	function triggerSave() {
		if (termsModalOpen) {
			var $modal = $('#' + TERMS_MODAL_ID);
			if ($modal.length && ($modal.hasClass('in') || $modal.is(':visible'))) {
				$modal.find('#mkQtTermsSaveBtn').trigger('click');
			}
		}
		syncFormBeforeSave();
		if (!isSalesOrder()) {
			var mode = confirmSaveRequested ? 'confirm' : 'draft';
			var openPrint = $form().find('#mkQtOpenPrint').val() === '1';
			saveQuoteAjax({
				mode: mode,
				openPrint: openPrint,
			})
				.done(function (result) {
					var recordId = result && result.record ? String(result.record) : getQuoteRecordId();
					if (openPrint && recordId) {
						if (typeof window.__mkQtOpenPrintPreview === 'function') {
							window.__mkQtOpenPrintPreview(recordId);
						} else {
							window.location.href =
								'index.php?module=Quotes&view=Edit&record=' +
								encodeURIComponent(recordId) +
								'&app=SALES&mk_show_print=1';
						}
						confirmSaveRequested = false;
						return;
					}
					// After Lưu / xác nhận: luôn về list (không mở detail).
					window.location.href = 'index.php?module=Quotes&view=List&app=SALES';
				})
				.fail(function () {
					confirmSaveRequested = false;
					$form().find('#mkQtOpenPrint').val('0');
				});
			return;
		}
		var $save = $form().find('.saveButton').first();
		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$form().trigger('submit');
	}

	function bindPreviewPrint() {
		if (isSalesOrder()) {
			return;
		}
		$form()
			.off('submit.mkQtClearPreviewNo')
			.on('submit.mkQtClearPreviewNo', function () {
				var $qn = $form().find('[name="quote_no"]');
				if ($qn.attr('data-mk-preview-no') === '1') {
					$qn.val('');
				}
			});

		function quotePrintReady() {
			var $f = $form();
			var subject = $.trim($f.find('[name="subject"]').val() || '');
			var contactId = $.trim($f.find('[name="contact_id"]').val() || '');
			var potentialId = $.trim($f.find('[name="potential_id"]').val() || '');
			return !!(subject || contactId || potentialId) && quoteHasLineItems($f);
		}

		function syncPrintBtnState() {
			var $btn = $('#mkQtPreviewPrintBtn');
			if (!$btn.length) {
				return;
			}
			var ready = quotePrintReady();
			$btn.prop('disabled', !ready);
			$btn.toggleClass('is-disabled', !ready);
			$btn.attr(
				'title',
				ready
					? 'Xem bản in báo giá'
					: 'Điền khách hàng và ít nhất 1 dòng hàng rồi bấm In'
			);
		}

		function quotePrintPreviewUrl(recordId) {
			return (
				'index.php?module=Quotes&action=ExportPDF&record=' +
				encodeURIComponent(recordId) +
				'&preview=1&app=SALES'
			);
		}

		function quotePrintDownloadUrl(recordId) {
			return (
				'index.php?module=Quotes&action=ExportPDF&record=' +
				encodeURIComponent(recordId) +
				'&app=SALES'
			);
		}

		function closeCreatePrintPreview() {
			var $modal = $('#mk-qt-create-print-preview');
			$modal.removeClass('is-open').attr('aria-hidden', 'true');
			$modal.find('iframe').attr('src', 'about:blank');
			$('body').removeClass('mk-qt-create-print-open');
		}

		function ensureCreatePrintPreviewModal() {
			var $modal = $('#mk-qt-create-print-preview');
			if ($modal.length) {
				return $modal;
			}
			$modal = $(
				'<div id="mk-qt-create-print-preview" class="mk-qt-create-print-preview" aria-hidden="true">' +
					'<div class="mk-qt-create-print-preview__dialog" role="dialog" aria-labelledby="mk-qt-create-print-title">' +
					'<div class="mk-qt-create-print-preview__head">' +
					'<h3 id="mk-qt-create-print-title">Xem trước bản in</h3>' +
					'<button type="button" class="mk-qt-create-print-preview__close" aria-label="Đóng">&times;</button>' +
					'</div>' +
					'<div class="mk-qt-create-print-preview__body">' +
					'<iframe class="mk-qt-create-print-preview__frame" title="Xem trước PDF báo giá"></iframe>' +
					'</div>' +
					'<div class="mk-qt-create-print-preview__foot">' +
					'<button type="button" class="mk-qt-create-print-preview__cancel">Đóng</button>' +
					'<button type="button" class="mk-qt-create-print-preview__print"><i class="fa fa-print" aria-hidden="true"></i> In ngay</button>' +
					'<button type="button" class="mk-qt-create-print-preview__download"><i class="fa fa-download" aria-hidden="true"></i> Tải PDF</button>' +
					'</div></div></div>'
			);
			$('body').append($modal);
			$modal.on(
				'click',
				'.mk-qt-create-print-preview__close, .mk-qt-create-print-preview__cancel',
				function (e) {
					e.preventDefault();
					closeCreatePrintPreview();
				}
			);
			$modal.on('click', function (e) {
				if ($(e.target).is('#mk-qt-create-print-preview')) {
					closeCreatePrintPreview();
				}
			});
			$modal.on('click', '.mk-qt-create-print-preview__print', function (e) {
				e.preventDefault();
				var frame = $modal.find('iframe').get(0);
				try {
					if (frame && frame.contentWindow) {
						frame.contentWindow.focus();
						frame.contentWindow.print();
					}
				} catch (err) {
					/* ignore */
				}
			});
			$modal.on('click', '.mk-qt-create-print-preview__download', function (e) {
				e.preventDefault();
				var recordId = $modal.data('mkPrintRecordId');
				if (!recordId) {
					return;
				}
				var $frame = $('#mk-qt-create-print-download-frame');
				if (!$frame.length) {
					$frame = $(
						'<iframe id="mk-qt-create-print-download-frame" class="mk-qt-create-print-download-frame" title="Tải PDF báo giá"></iframe>'
					);
					$('body').append($frame);
				}
				$frame.attr('src', quotePrintDownloadUrl(recordId));
			});
			return $modal;
		}

		function openCreatePrintPreview(recordId) {
			if (!recordId || !/^\d+$/.test(String(recordId))) {
				return;
			}
			var $modal = ensureCreatePrintPreviewModal();
			$modal.data('mkPrintRecordId', String(recordId));
			$modal.find('iframe').attr('src', quotePrintPreviewUrl(recordId));
			$modal.addClass('is-open').attr('aria-hidden', 'false');
			$('body').addClass('mk-qt-create-print-open');
		}
		window.__mkQtOpenPrintPreview = openCreatePrintPreview;

		function maybeOpenPrintFromQuery() {
			try {
				var params = new URLSearchParams(window.location.search || '');
				if (params.get('mk_show_print') !== '1') {
					return;
				}
				var recordId = getQuoteRecordId() || params.get('record') || '';
				if (!recordId || !/^\d+$/.test(String(recordId))) {
					return;
				}
				params.delete('mk_show_print');
				var next =
					window.location.pathname +
					(params.toString() ? '?' + params.toString() : '') +
					(window.location.hash || '');
				if (window.history && window.history.replaceState) {
					window.history.replaceState({}, document.title, next);
				}
				window.setTimeout(function () {
					openCreatePrintPreview(recordId);
				}, 250);
			} catch (e) {
				/* ignore */
			}
		}

		$(document)
			.off('click.mkQtPreviewPrint', '#mkQtPreviewPrintBtn')
			.on('click.mkQtPreviewPrint', '#mkQtPreviewPrintBtn', function (e) {
				e.preventDefault();
				var $f = $form();
				var recordId = getQuoteRecordId();
				if (!quotePrintReady()) {
					if (window.app && app.helper && app.helper.showErrorNotification) {
						app.helper.showErrorNotification({
							message: 'Vui lòng chọn khách hàng và thêm hàng hóa trước khi in báo giá.',
						});
					} else {
						window.alert('Vui lòng chọn khách hàng và thêm hàng hóa trước khi in báo giá.');
					}
					return;
				}
				if (recordId && /^\d+$/.test(recordId)) {
					syncFormBeforeSave();
					setQuoteSaveMode('draft');
					ensureDraftQuoteStage();
					$f.find('#mkQtOpenPrint').val('1');
					triggerSave();
					return;
				}
				// New quote: save draft first, then return to Edit and open overlay.
				confirmSaveRequested = false;
				setQuoteSaveMode('draft');
				ensureDraftQuoteStage();
				$f.find('#mkQtOpenPrint').val('1');
				triggerSave();
			});

		$form()
			.off('change.mkQtPrintReady input.mkQtPrintReady')
			.on('change.mkQtPrintReady input.mkQtPrintReady', 'input, select, textarea', syncPrintBtnState);
		if (typeof app !== 'undefined' && app.event && typeof app.event.on === 'function') {
			app.event
				.off('post.lineItem.New.mkQtPrintReady')
				.on('post.lineItem.New.mkQtPrintReady', function () {
					syncPrintBtnState();
					markDirty();
				});
		}
		$(document)
			.off('inventory_line_items_changed.mkQtPrintReady')
			.on('inventory_line_items_changed.mkQtPrintReady', syncPrintBtnState);
		var $lineTab = $form().find('#lineItemTab').get(0);
		if ($lineTab && typeof MutationObserver !== 'undefined') {
			var lineObs = new MutationObserver(function () {
				syncPrintBtnState();
			});
			lineObs.observe($lineTab, { childList: true, subtree: true, attributes: true });
		}
		setTimeout(syncPrintBtnState, 0);
		setTimeout(syncPrintBtnState, 400);
		setTimeout(syncPrintBtnState, 1200);
		maybeOpenPrintFromQuery();
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
			.find('input[name="subject"], input[name="mk_client_company"]')
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

	function readFieldDisplay(name) {
		var $f = $form().find('[name="' + name + '"]');
		if (!$f.length) {
			return '';
		}
		var raw = '';
		if ($f.is('select')) {
			raw = $.trim($f.find('option:selected').text());
		} else if ($f.hasClass('sourceField')) {
			raw = $.trim($form().find('[name="' + name + '_display"]').val());
		} else {
			raw = $.trim($f.val());
		}
		return decodeHtmlText(raw);
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
		if (isSalesOrder()) {
			var stage = readFieldDisplay('sostatus') || readFieldDisplay('salesorder_status');
			var due = readFieldDisplay('duedate');
			var account = readFieldDisplay('account_id');
			var opp = readFieldDisplay('potential_id');
			var total = readGrandTotal();
			$('#mkSoRailStage, #mkSoHeadStageBadge').text(stage || 'Draft');
			$('#mkSoRailDueDate').text(due || '—');
			$('#mkSoRailAccount').text(account || '—');
			$('#mkSoRailOpportunity').text(opp || '—');
			$('#mkSoRailTotal').text(total || '—');
			return;
		}
		var stage = 'Nháp';
		var contact = readFieldDisplay('contact_id') || readFieldDisplay('account_id');
		var opp = readFieldDisplay('potential_id');
		var total = readGrandTotal();

		$('#mkQtRailStage, #mkQtHeadStageBadge').text(stage);
		$('#mkQtRailOrganization').text(contact || '—');
		$('#mkQtRailOpportunity').text(opp || '—');
		$('#mkQtRailTotal').text(total || '—');
	}

	function hideRailNoiseCards() {
		var $rail = isSalesOrder() ? $('#mkSoOrderRail') : $('#mkQtQuoteRail');
		if (!$rail.length) {
			return;
		}
		if (isSalesOrder()) {
			$rail.find('.mk-qt-rail-card--muted').addClass('mk-qt-hide-legacy');
			return;
		}
		$rail
			.find('.mk-qt-rail-card--summary, .mk-qt-rail-card--muted, .mk-qt-rail-card--ai, .mk-qt-rail-card--company, .mk-qt-company-ro')
			.addClass('mk-qt-hide-legacy')
			.remove();
	}

	/**
	 * Hide Assigned To UI and lock owner to the logged-in user.
	 * Quote details block moves into the right rail (replacing Assigned To).
	 * Rail is relocated inside #EditView so fields still submit.
	 */
	function lockAssignedAndMoveQuoteInfoToRail() {
		if (isSalesOrder()) {
			return;
		}
		var $editForm = $form();
		var $rail = $('#mkQtQuoteRail');
		if (!$editForm.length || !$rail.length) {
			return;
		}

		var $assigned = $editForm.find('[name="assigned_user_id"]').first();
		if ($assigned.length) {
			$assigned.closest('tr').addClass('mk-qt-hide-legacy');
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
					if ($assigned.data('select2')) {
						$assigned.select2('val', uid);
					}
				} catch (e2) {}
			}
		}

		// Remove Assigned To card from rail.
		$rail
			.find('.mk-qt-rail-card')
			.filter(function () {
				var $card = $(this);
				if ($card.find('#mkQtRailOwner').length) {
					return true;
				}
				return /assigned\s*to|phụ\s*trách/i.test($.trim($card.find('.mk-qt-rail-card__title').text() || ''));
			})
			.remove();

		// Keep rail inside the form so moved fields still POST on save.
		if (!$editForm.find('#mkQtQuoteRail').length) {
			var $mount = $editForm.find('[name="editContent"]').first();
			if (!$mount.length) {
				$mount = $editForm.find('.editViewContents').first();
			}
			if (!$mount.length) {
				$mount = $editForm;
			}
			$mount.addClass('mk-qt-edit-split');
			$rail.addClass('mk-qt-rail--in-form');
			$mount.append($rail.detach());
			// Outer aside slot no longer needed.
			$('.mk-qt-create__grid > .mk-qt-rail').not($rail).remove();
			$('.mk-qt-create__grid').addClass('mk-qt-create__grid--single');
		}

		// Ensure totals sit under order details with all 3 rows.
		pinTotalsBelowOrderDetails();

		var $info = $editForm.find('.fieldBlockContainer[data-block="LBL_QUOTE_INFORMATION"]').first();
		if (!$info.length) {
			return;
		}
		if ($info.closest('#mkQtQuoteRail').length) {
			compactQuoteInfoRail($info);
			return;
		}
		$info.addClass('mk-qt-block mk-qt-rail-quote-info');
		var $addr = $rail.find('.mk-qt-address-rail, .mk-qt-rail-card--address').first();
		if ($addr.length) {
			var $existingInlineAddr = $info.find('.mk-qt-address-inline').first();
			if (!$existingInlineAddr.length) {
				var $addrGrid = $addr.find('.mk-qt-addr-grid').first();
				if ($addrGrid.length) {
					var $inlineAddr = $('<div class="mk-qt-address-inline"></div>');
					$inlineAddr.append('<div class="mk-qt-address-inline__head"><span class="mk-qt-address-inline__icon" aria-hidden="true"><i class="fa fa-map-marker"></i></span><h3 class="mk-qt-address-inline__title">Địa chỉ</h3></div>');
					$inlineAddr.append($addrGrid.detach());
					$info.append($inlineAddr);
				}
			}
			$addr.remove();
			$rail.prepend($info);
		} else {
			$rail.prepend($info);
		}
		compactQuoteInfoRail($info);
	}

	/** Drop leftover empty rows (e.g. Bảng giá) and tighten spacing. */
	function compactQuoteInfoRail($info) {
		if (!$info || !$info.length) {
			return;
		}
		$info.find('tr').each(function () {
			var $tr = $(this);
			if ($tr.hasClass('mk-qt-customer-row') || $tr.hasClass('mk-qt-tier-row')) {
				return;
			}
			if ($tr.find('[name="terms_conditions"], .mk-qt-terms-preview, .mk-qt-terms-source').length) {
				return;
			}
			var hasVisibleControl = $tr.find('input, select, textarea, .referencefield-wrapper, .mk-qt-terms-preview').filter(function () {
				var $el = $(this);
				if ($el.hasClass('mk-qt-hide-legacy') || $el.closest('.mk-qt-hide-legacy, .mk-inv-hide-legacy, .hide').length) {
					return false;
				}
				if ($el.is('[type="hidden"]')) {
					return false;
				}
				return $el.is(':visible') || $el.closest('td.fieldValue').is(':visible');
			}).length;
			if (!hasVisibleControl) {
				$tr.addClass('mk-qt-hide-legacy');
			}
		});
		// Hide only currency leftovers by label text.
		$info.find('td.fieldLabel').each(function () {
			var t = $.trim($(this).text() || '').replace(/\*/g, '');
			if (/^currency$/i.test(t) || /^loại\s*tiền/i.test(t)) {
				$(this).closest('tr').addClass('mk-qt-hide-legacy');
			}
		});
	}

	function moveAssignedToIntoRail() {
		// Legacy name kept for call sites — Quotes now locks owner + moves quote info.
		if (isSalesOrder()) {
			var $editForm = $form();
			var $rail = $('#mkSoOrderRail');
			if (!$editForm.length || !$rail.length) {
				return;
			}
			var $assigned = $editForm.find('[name="assigned_user_id"]').first();
			if ($assigned.length) {
				$assigned.closest('tr').addClass('mk-qt-hide-legacy');
			}
			var $card = $rail.find('.mk-qt-rail-card:has(#mkSoRailOwner)').first();
			if (!$card.length || $card.data('mkQtAssignedReady')) {
				return;
			}
			$card.data('mkQtAssignedReady', true);
			var $host = $('<div class="mk-qt-rail-field"></div>');
			if ($assigned.length) {
				$host.append($assigned.detach());
				$card.find('#mkSoRailOwner').replaceWith($host);
				try {
					if (typeof vtUtils !== 'undefined' && vtUtils.applyFieldElementsView) {
						vtUtils.applyFieldElementsView($card);
					}
				} catch (e) { /* ignore */ }
			}
			return;
		}
		lockAssignedAndMoveQuoteInfoToRail();
	}

	function moveNotesIntoQuoteInfo() {
		var $editForm = $form();
		var $terms = $editForm.find('textarea[name="terms_conditions"]').first();
		if (!$terms.length) {
			return;
		}
		var $termsRow = $terms.closest('tr');
		var $termsBlock = $terms.closest('.fieldBlockContainer[data-block="LBL_TERMS_INFORMATION"]');
		var infoBlock = isSalesOrder() ? 'LBL_SO_INFORMATION' : 'LBL_QUOTE_INFORMATION';
		var $infoBlock = $editForm.find('.fieldBlockContainer[data-block="' + infoBlock + '"]').first();
		if (!$infoBlock.length) {
			return;
		}
		// Move the row up to quote info block (place where "Phụ trách" used to be)
		if ($termsRow.length) {
			$termsRow
				.find('td.fieldLabel label, td.fieldLabel .muted, td.fieldLabel')
				.each(function () {
					var $el = $(this);
					// Only rewrite if it still shows the legacy title
					var t = $.trim($el.text() || '');
					if (!t) {
						return;
					}
					if (/điều\s*kiện|điều\s*khoản/i.test(t)) {
						$el.text('Ghi chú');
					}
				});
			$infoBlock.find('table.table-borderless').first().append($termsRow.detach());
		}
		// Hide the original Terms block entirely (remove the old bottom notes area)
		if ($termsBlock.length) {
			$termsBlock.addClass('mk-qt-hide-legacy');
		}
	}

	function forceRenameTermsToNotes() {
		var $host = $formHost();
		if (!$host.length) {
			return;
		}
		// Some templates render the label as plain text node (not <label>).
		$host.find('.fieldLabel, .fieldBlockHeader, label, h4, span, div').each(function () {
			var $el = $(this);
			var text = $el.text();
			if (!text) {
				return;
			}
			if (/Điều\s*kiện\s*&\s*điều\s*khoản/i.test(text) || /Điều\s*kiện\s*và\s*điều\s*khoản/i.test(text)) {
				$el.text(text.replace(/Điều\s*kiện\s*(?:&|và)\s*điều\s*khoản/gi, 'Ghi chú'));
			}
		});
	}

	function markDirty() {
		formDirty = true;
		var $el = $('#mkQtAutosave');
		$el.addClass('is-dirty').removeClass('is-saved');
		$el.find('.mk-qt-autosave__text').text('Chưa lưu');
		clearTimeout(autosaveTimer);
		autosaveTimer = setTimeout(function () {
			$el.removeClass('is-dirty').addClass('is-saved');
			$el.find('.mk-qt-autosave__text').text('Sẵn sàng lưu');
		}, 1800);
		scheduleDraftAutosave();
	}

	function bindActions() {
		ensureSaveModeField();
		if (!isSalesOrder()) {
			setQuoteSaveMode('draft');
			ensureDraftQuoteStage();
			$form()
				.off('submit.mkQtAjaxSave click.mkQtAjaxSave')
				.on('submit.mkQtAjaxSave', function (e) {
					e.preventDefault();
					e.stopImmediatePropagation();
					if (!confirmSaveRequested) {
						confirmSaveRequested = true;
						setQuoteSaveMode('confirm');
						ensureConfirmedQuoteStage();
					}
					triggerSave();
					return false;
				})
				.on('click.mkQtAjaxSave', '.saveButton', function (e) {
					e.preventDefault();
					e.stopImmediatePropagation();
					if (!confirmSaveRequested) {
						confirmSaveRequested = true;
						setQuoteSaveMode('confirm');
						ensureConfirmedQuoteStage();
					}
					triggerSave();
					return false;
				});
		}

		$('#mkQtSaveTop, #mkSoSaveTop')
			.off('click.mkQtSave')
			.on('click.mkQtSave', function (e) {
				e.preventDefault();
				if (isSalesOrder() && window.__mkSoCreateSave) {
					window.__mkSoCreateSave(e);
					return;
				}
				confirmSaveRequested = true;
				clearTimeout(draftAutosaveTimer);
				setQuoteSaveMode('confirm');
				ensureConfirmedQuoteStage();
				triggerSave();
			});

		$('#mkQtSaveSendTop')
			.off('click.mkQtSaveSend')
			.on('click.mkQtSaveSend', function (e) {
				e.preventDefault();
				confirmSaveRequested = true;
				clearTimeout(draftAutosaveTimer);
				setQuoteSaveMode('confirm');
				ensureConfirmedQuoteStage();
				triggerSave();
			});

		$form()
			.off('change.mkQtRail input.mkQtRail')
			.on('change.mkQtRail input.mkQtRail', 'input, select, textarea', function () {
				fixFormDisplayEncoding();
				markDirty();
				syncRail();
				if (window.MkQuoteBa) {
					MkQuoteBa.syncVatAndWords($form());
				}
			});

		$form()
			.off(Vtiger_Edit_Js.referenceSelectionEvent + '.mkQtDecode')
			.on(Vtiger_Edit_Js.referenceSelectionEvent + '.mkQtDecode', '.sourceField', function () {
				setTimeout(function () {
					fixFormDisplayEncoding();
					syncRail();
					markDirty();
				}, 0);
			});

		$form()
			.off(Vtiger_Edit_Js.postReferenceSelectionEvent + '.mkQtDecode')
			.on(Vtiger_Edit_Js.postReferenceSelectionEvent + '.mkQtDecode', '.sourceField', function () {
				setTimeout(function () {
					fixFormDisplayEncoding();
					syncRail();
					markDirty();
				}, 0);
			});

		$(document)
			.off('keydown.mkQtCreate')
			.on('keydown.mkQtCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkQtFormHost, #mkSoFormHost').length) {
						return;
					}
					e.preventDefault();
					if (isSalesOrder() && window.__mkSoCreateSave) {
						window.__mkSoCreateSave(e);
						return;
					}
					confirmSaveRequested = true;
					clearTimeout(draftAutosaveTimer);
					setQuoteSaveMode('confirm');
					ensureConfirmedQuoteStage();
					triggerSave();
				}
			});

		if (!isSalesOrder()) {
			if (typeof app !== 'undefined' && app.event && typeof app.event.on === 'function') {
				app.event
					.off('post.lineItem.New.mkQtDraft')
					.on('post.lineItem.New.mkQtDraft', function () {
						markDirty();
					});
			}

			$(window)
				.off('pagehide.mkQtDraft beforeunload.mkQtDraft')
				.on('pagehide.mkQtDraft', function () {
					if (confirmSaveRequested || !formDirty) {
						return;
					}
					saveQuoteDraftAjax({ keepalive: true });
				})
				.on('beforeunload.mkQtDraft', function () {
					if (confirmSaveRequested || !formDirty || !quoteCanDraftSave()) {
						return;
					}
					// Best-effort draft save if user leaves without clicking Lưu.
					saveQuoteDraftAjax({ keepalive: true });
				});
		}
	}

	function initStickyHead() {
		var $head = isSalesOrder() ? $('#mkSoStickyHead') : $('#mkQtStickyHead');
		if (!$head.length) {
			return;
		}
		$(window)
			.off('scroll.mkQtSticky')
			.on('scroll.mkQtSticky', function () {
				$head.toggleClass('is-elevated', window.scrollY > 8);
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

	function initOdooInventoryUi() {
		if (window.MkInventoryOdooEdit && window.MkInventoryOdooEdit.init) {
			window.MkInventoryOdooEdit.init($form(), { hideDescriptionBlock: true });
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
		} else {
			$actions.addClass('mk-inv-line-header-actions mk-qt-line-actions');
		}
		if (!$addBtn.closest('.mk-inv-line-header-actions, .mk-qt-line-actions').length) {
			$actions.append($addBtn.detach());
		}
		$addBtn.addClass('mk-inv-add-line-btn--hidden').attr('aria-hidden', 'true');
		if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.initQuickProductSearch === 'function') {
			window.MkInventoryOdooEdit.initQuickProductSearch($editForm);
		}
	}

	function applyServiceContractPrefill() {
		var prefill = window.MK_SC_PREFILL;
		var $f = $form();
		if (!$f.length) {
			return;
		}

		// Always keep SC link fields when present (create from Khách hàng nhượng quyền).
		var scId = 0;
		if (prefill && prefill.id) {
			scId = parseInt(prefill.id, 10) || 0;
		}
		if (!scId) {
			try {
				var qs = new URLSearchParams(window.location.search || "");
				scId =
					parseInt(qs.get("servicecontract_id") || qs.get("mk_servicecontract_id") || "0", 10) ||
					0;
			} catch (eSc) {
				scId = 0;
			}
		}
		if (scId > 0) {
			var $scField = $f.find('[name="mk_servicecontract_id"]');
			if (!$scField.length) {
				$scField = $('<input type="hidden" name="mk_servicecontract_id" id="mk_servicecontract_id" />');
				$f.prepend($scField);
			}
			$scField.val(String(scId));
			var $scAlt = $f.find('[name="servicecontract_id"]');
			if (!$scAlt.length) {
				$scAlt = $('<input type="hidden" name="servicecontract_id" id="servicecontract_id" />');
				$f.prepend($scAlt);
			}
			$scAlt.val(String(scId));
			if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.setPriceChannel === 'function') {
				window.MkInventoryOdooEdit.setPriceChannel('tuibao', {
					scPrefill: prefill || { id: scId }
				});
			} else {
				window.MK_PRICE_CHANNEL = 'tuibao';
			}
		}

		if (!prefill || !prefill.name) {
			return;
		}
		if ($f.data('mkScPrefillApplied')) {
			return;
		}
		$f.data('mkScPrefillApplied', 1);
		var name = $.trim(prefill.name || '');
		var phone = $.trim(prefill.phone || '');
		var email = $.trim(prefill.email || '');
		if (name) {
			$f.find('[name="subject"]').val(name);
			var $disp = $f.find('[name="contact_id_display"]').first();
			if ($disp.length && !$.trim($disp.val() || '')) {
				$disp
					.val(name)
					.data('mkCustomerModule', 'ServiceContracts')
					.data('mkServiceContractId', scId);
				$f.find('.mk-qt-customer-ref .clearReferenceSelection').removeClass('hide');
				$f.find('.mk-qt-customer-ref').addClass('selected');
			}
		}
		if (phone && $f.find('[name="mk_customer_phone"]').length) {
			var formatted =
				window.MkPhoneFormat && typeof window.MkPhoneFormat.format === 'function'
					? window.MkPhoneFormat.format(phone)
					: phone;
			$f.find('[name="mk_customer_phone"]').val(formatted || phone);
		}
		if (email && $f.find('[name="mk_customer_email"]').length) {
			$f.find('[name="mk_customer_email"]').val(email);
		}
		if (prefill.account_id && $f.find('[name="account_id"]').length) {
			$f.find('[name="account_id"]').val(prefill.account_id);
		}
		if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.applyInvoiceTierPricing === 'function') {
			window.MkInventoryOdooEdit.applyInvoiceTierPricing($f, { force: true });
		}
		if (typeof syncCustomerInfoButtonVisibility === 'function') {
			syncCustomerInfoButtonVisibility();
		}
	}

	function runEnhancements() {
		if (!isScoped()) {
			return;
		}
		markCreateEnhanced();
		hideLegacyChrome();
		styleFieldBlocks();
		simplifyQuoteForm();
		ensureDraftQuoteStage();
		reorderQuoteBlocks();
		initOdooInventoryUi();
		pinAddProductToLineHeader();
		if (!isSalesOrder()) {
			pinTotalsBelowOrderDetails();
		}
		if (window.MkInventoryOdooEdit && window.MkInventoryOdooEdit.scheduleLineItemsRestyle) {
			window.MkInventoryOdooEdit.scheduleLineItemsRestyle($form());
		}
		if (window.MkInventoryOdooEdit && window.MkInventoryOdooEdit.syncLineDeleteVisibility) {
			window.MkInventoryOdooEdit.syncLineDeleteVisibility($form());
		}
		hideRailNoiseCards();
		initBaForm();
		moveAssignedToIntoRail();
		moveNotesIntoQuoteInfo();
		// Customer row last so it stays at top of Chi tiết báo giá (above Ghi chú).
		if (!isSalesOrder()) {
			layoutQuoteHeaderFields();
			applyServiceContractPrefill();
		}
		forceRenameTermsToNotes();
		fixFormDisplayEncoding();
		initTermsRichEditor();
		syncRail();
		bindActions();
		bindPreviewPrint();
		observeTotals();
		initStickyHead();
		setTimeout(fixFormDisplayEncoding, 300);
		setTimeout(function () {
			fixFormDisplayEncoding();
			if (!isSalesOrder()) {
				layoutQuoteHeaderFields();
				applyServiceContractPrefill();
				lockAssignedAndMoveQuoteInfoToRail();
				pinTotalsBelowOrderDetails();
				compactQuoteInfoRail(
					$form().find('.mk-qt-rail-quote-info, .fieldBlockContainer[data-block="LBL_QUOTE_INFORMATION"]').first()
				);
			}
			if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo === 'function') {
				window.MkInventoryOdooEdit.integrateCommerceIntoQuoteInfo($form());
				ensureTierDropdownVisible($form());
			} else if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.relocateCommerceToRail === 'function') {
				window.MkInventoryOdooEdit.relocateCommerceToRail($form());
			}
			syncRail();
			if (!document.documentElement.classList.contains('mk-inv-ui-ready')) {
				document.documentElement.classList.add('mk-inv-ui-ready', 'mk-quote-create-enhanced');
			}
		}, 1200);
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
		syncRail: syncRail,
		openTermsEditor: function () {
			openTermsEditor($form().find('textarea[name="terms_conditions"]').first());
		}
	};
})(jQuery);
