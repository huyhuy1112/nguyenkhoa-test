/**
 * Quotes Create (SALES) — premium shell; stock Inventory #EditView unchanged.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260622_quote_ba_v7';
	var autosaveTimer;
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
				'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
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
		return !recordId || isDuplicate === 'true';
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
		}
		hideFields.forEach(function (name) {
			$form()
				.find('[name="' + name + '"]')
				.closest('tr')
				.addClass('mk-qt-hide-legacy');
		});

		$form()
			.find('.fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"]')
			.addClass('mk-qt-address-simplified mk-qt-hide-legacy');
	}

	function reorderQuoteBlocks() {
		var $editForm = $form();
		var infoBlock = isSalesOrder() ? 'LBL_SO_INFORMATION' : 'LBL_QUOTE_INFORMATION';
		var $info = $editForm.find('.fieldBlockContainer[data-block="' + infoBlock + '"]').first();
		var $items = $editForm.find('#lineItemTab').closest('.fieldBlockContainer').first();
		var $totals = $editForm.find('#lineItemResult').closest('.fieldBlockContainer').first();
		var $addr = $editForm.find('.fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"]').first();
		var $terms = $editForm.find('.fieldBlockContainer[data-block="LBL_TERMS_INFORMATION"]').first();
		if ($info.length && $items.length) {
			$items.insertAfter($info);
		}
		if ($totals.length && $items.length) {
			$totals.insertAfter($items);
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
		syncTermsToSource();
		var $save = $form().find('.saveButton').first();
		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$form().trigger('submit');
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
			.find('.mk-qt-rail-card--summary, .mk-qt-rail-card--muted, .mk-qt-rail-card--ai')
			.addClass('mk-qt-hide-legacy');
	}

	function moveAssignedToIntoRail() {
		var $editForm = $form();
		var $rail = isSalesOrder() ? $('#mkSoOrderRail') : $('#mkQtQuoteRail');
		if (!$editForm.length || !$rail.length) {
			return;
		}
		var $assigned = $editForm.find('[name="assigned_user_id"]').first();
		if ($assigned.length) {
			$assigned.closest('tr').addClass('mk-qt-hide-legacy');
		}
		var ownerSel = isSalesOrder() ? '#mkSoRailOwner' : '#mkQtRailOwner';
		var $card = $rail.find('.mk-qt-rail-card:has(' + ownerSel + ')').first();
		if (!$card.length || $card.data('mkQtAssignedReady')) {
			return;
		}
		$card.data('mkQtAssignedReady', true);
		var $host = $('<div class="mk-qt-rail-field"></div>');
		if ($assigned.length) {
			$host.append($assigned.detach());
			$card.find(ownerSel).replaceWith($host);
			try {
				if (typeof vtUtils !== 'undefined' && vtUtils.applyFieldElementsView) {
					vtUtils.applyFieldElementsView($card);
				}
			} catch (e) { /* ignore */ }
		}
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
		$('#mkQtSaveTop, #mkSoSaveTop')
			.off('click.mkQtSave')
			.on('click.mkQtSave', function (e) {
				e.preventDefault();
				if (isSalesOrder() && window.__mkSoCreateSave) {
					window.__mkSoCreateSave(e);
					return;
				}
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
				}, 0);
			});

		$form()
			.off(Vtiger_Edit_Js.postReferenceSelectionEvent + '.mkQtDecode')
			.on(Vtiger_Edit_Js.postReferenceSelectionEvent + '.mkQtDecode', '.sourceField', function () {
				setTimeout(function () {
					fixFormDisplayEncoding();
					syncRail();
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
					triggerSave();
				}
			});
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
		if ($tabs.find('.mk-qt-line-actions').length) {
			return;
		}
		var $actions = $('<div class="mk-qt-line-actions" aria-label="Thao tác dòng sản phẩm"></div>');
		$actions.append($addBtn.detach());
		$tabs.append($actions);
	}

	function runEnhancements() {
		if (!isScoped()) {
			return;
		}
		markCreateEnhanced();
		hideLegacyChrome();
		styleFieldBlocks();
		simplifyQuoteForm();
		reorderQuoteBlocks();
		initOdooInventoryUi();
		pinAddProductToLineHeader();
		if (window.MkInventoryOdooEdit && window.MkInventoryOdooEdit.scheduleLineItemsRestyle) {
			window.MkInventoryOdooEdit.scheduleLineItemsRestyle($form());
		}
		hideRailNoiseCards();
		moveAssignedToIntoRail();
		moveNotesIntoQuoteInfo();
		forceRenameTermsToNotes();
		fixFormDisplayEncoding();
		initBaForm();
		initTermsRichEditor();
		syncRail();
		bindActions();
		observeTotals();
		initStickyHead();
		setTimeout(fixFormDisplayEncoding, 300);
		setTimeout(function () {
			fixFormDisplayEncoding();
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
