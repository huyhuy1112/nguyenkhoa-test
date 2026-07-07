/**
 * Quote / Sales Order — Odoo-style address, line items, payment terms (SALES app).
 */
(function ($) {
	'use strict';

	var REF_SEL = 'Vtiger.Reference.Selection';
	var POST_REF = 'Vtiger.PostReference.Selection';

	var PAYMENT_METHOD_OPTIONS = [
		{ value: 'Tiền mặt', label: 'Tiền mặt' },
		{ value: 'Chuyển khoản', label: 'Chuyển khoản' },
		{ value: 'Thẻ', label: 'Thẻ' },
		{ value: 'Ví', label: 'Ví' }
	];

	var PAYMENT_TERMS_FALLBACK = PAYMENT_METHOD_OPTIONS.map(function (item) {
		return item.label;
	});

	var UNIT_OPTIONS = [
		{ value: 'Đơn vị', label: 'Đơn vị' },
		{ value: 'Dozen', label: 'Dozens' },
		{ value: 'Box', label: 'Box' },
		{ value: 'Each', label: 'Each' }
	];

	var ADDRESS_DETAIL_FIELDS = [
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

	function decodeText(value) {
		if (value === null || value === undefined) {
			return '';
		}
		var text = String(value);
		if (typeof app !== 'undefined' && app.htmlDecode) {
			text = app.htmlDecode(text);
		}
		if (/&(?:#x?[0-9a-f]+|[a-z]+);/i.test(text)) {
			var el = document.createElement('textarea');
			el.innerHTML = text;
			text = el.value;
		}
		return text;
	}

	function getRecordDetails(recordId, sourceModule) {
		var deferred = $.Deferred();
		recordId = parseInt(recordId, 10) || 0;
		if (!recordId || typeof app === 'undefined' || !app.request) {
			deferred.reject();
			return deferred.promise();
		}
		var moduleName = app.getModuleName ? app.getModuleName() : 'Vtiger';
		var url =
			'index.php?module=' +
			moduleName +
			'&action=GetData&record=' +
			recordId +
			'&source_module=' +
			sourceModule;
		app.request.get({ url: url }).then(function (error, data) {
			if (error === null && data) {
				deferred.resolve(data);
			} else {
				deferred.reject(error || data);
			}
		});
		return deferred.promise();
	}

	function formatAccountAddress(row, kind) {
		var prefix = kind === 'ship' ? 'ship' : 'bill';
		var parts = [];
		var street = decodeText(row[prefix + '_street']);
		if (street) {
			parts.push(street);
		}
		var pobox = decodeText(row[prefix + '_pobox']);
		if (pobox) {
			parts.push(pobox);
		}
		var cityLine = [decodeText(row[prefix + '_city']), decodeText(row[prefix + '_state']), decodeText(row[prefix + '_code'])]
			.filter(Boolean)
			.join(', ');
		if (cityLine) {
			parts.push(cityLine);
		}
		var country = decodeText(row[prefix + '_country']);
		if (country) {
			parts.push(country);
		}
		return parts.join('\n');
	}

	function formatContactAddress(row, kind) {
		var map =
			kind === 'ship'
				? {
						street: 'otherstreet',
						pobox: 'otherpobox',
						city: 'othercity',
						state: 'otherstate',
						code: 'otherzip',
						country: 'othercountry'
				  }
				: {
						street: 'mailingstreet',
						pobox: 'mailingpobox',
						city: 'mailingcity',
						state: 'mailingstate',
						code: 'mailingzip',
						country: 'mailingcountry'
				  };
		var parts = [];
		var street = decodeText(row[map.street]);
		if (street) {
			parts.push(street);
		}
		var pobox = decodeText(row[map.pobox]);
		if (pobox) {
			parts.push(pobox);
		}
		var cityLine = [decodeText(row[map.city]), decodeText(row[map.state]), decodeText(row[map.code])].filter(Boolean).join(', ');
		if (cityLine) {
			parts.push(cityLine);
		}
		var country = decodeText(row[map.country]);
		if (country) {
			parts.push(country);
		}
		return parts.join('\n');
	}

	function syncHiddenAddressFields($form, row, module, kind) {
		var maps = {
			Accounts: {
				bill: {
					bill_street: 'bill_street',
					bill_pobox: 'bill_pobox',
					bill_city: 'bill_city',
					bill_state: 'bill_state',
					bill_code: 'bill_code',
					bill_country: 'bill_country'
				},
				ship: {
					ship_street: 'ship_street',
					ship_pobox: 'ship_pobox',
					ship_city: 'ship_city',
					ship_state: 'ship_state',
					ship_code: 'ship_code',
					ship_country: 'ship_country'
				}
			},
			Contacts: {
				bill: {
					bill_street: 'mailingstreet',
					bill_pobox: 'mailingpobox',
					bill_city: 'mailingcity',
					bill_state: 'mailingstate',
					bill_code: 'mailingzip',
					bill_country: 'mailingcountry'
				},
				ship: {
					ship_street: 'otherstreet',
					ship_pobox: 'otherpobox',
					ship_city: 'othercity',
					ship_state: 'otherstate',
					ship_code: 'otherzip',
					ship_country: 'othercountry'
				}
			}
		};
		var fieldMap = maps[module] && maps[module][kind];
		if (!fieldMap) {
			return;
		}
		Object.keys(fieldMap).forEach(function (target) {
			var source = fieldMap[target];
			var val = decodeText(row[source]);
			var $el = $form.find('[name="' + target + '"]');
			if ($el.length) {
				$el.val(val);
			}
		});
	}

	function setFormAddresses($form, billText, shipText, sourceRow, sourceModule) {
		var $bill = $form.find('[name="bill_street"]');
		var $ship = $form.find('[name="ship_street"]');
		if ($bill.length && billText) {
			$bill.val(billText).trigger('change');
		}
		if ($ship.length && shipText) {
			$ship.val(shipText).trigger('change');
		}
		if (sourceRow && sourceModule) {
			syncHiddenAddressFields($form, sourceRow, sourceModule, 'bill');
			syncHiddenAddressFields($form, sourceRow, sourceModule, 'ship');
		}
		syncShipSameAsBill($form);
	}

	function fillAddressFromAccount($form, accountId) {
		accountId = parseInt(accountId, 10) || 0;
		if (!accountId) {
			return $.Deferred().reject().promise();
		}
		return getRecordDetails(accountId, 'Accounts').then(function (data) {
			var row = data && data.data;
			if (!row) {
				return;
			}
			setFormAddresses(
				$form,
				formatAccountAddress(row, 'bill'),
				formatAccountAddress(row, 'ship'),
				row,
				'Accounts'
			);
		});
	}

	function fillAddressFromContact($form, contactId) {
		contactId = parseInt(contactId, 10) || 0;
		if (!contactId) {
			return $.Deferred().reject().promise();
		}
		return getRecordDetails(contactId, 'Contacts').then(function (data) {
			var row = data && data.data;
			if (!row) {
				return;
			}
			setFormAddresses(
				$form,
				formatContactAddress(row, 'bill'),
				formatContactAddress(row, 'ship'),
				row,
				'Contacts'
			);
		});
	}

	function fillAddressFromPotential($form) {
		var potId = parseInt($form.find('[name="potential_id"]').val(), 10) || 0;
		if (!potId) {
			return;
		}
		getRecordDetails(potId, 'Potentials').then(function (data) {
			var row = data && data.data;
			if (!row) {
				return;
			}
			var accountId = parseInt(row.related_to, 10) || 0;
			var contactId = parseInt(row.contact_id, 10) || 0;
			if (accountId) {
				fillAddressFromAccount($form, accountId);
			} else if (contactId) {
				fillAddressFromContact($form, contactId);
			}
		});
	}

	function syncShipSameAsBill($form) {
		var $cb = $form.find('#mkInvShipSameAsBill');
		if (!$cb.length || !$cb.is(':checked')) {
			return;
		}
		var bill = $form.find('[name="bill_street"]').val() || '';
		$form.find('[name="ship_street"]').val(bill).prop('readonly', true);
	}

	function hideAddressDetailRows($form) {
		ADDRESS_DETAIL_FIELDS.forEach(function (name) {
			$form.find('[name="' + name + '"]').closest('tr').addClass('mk-inv-hide-legacy');
		});
		$form.find('.addressBlock > tbody > tr:first-child').addClass('mk-inv-hide-legacy');
	}

	function localizeAddressBlock($form) {
		var $block = $form.find('.mk-inv-address-odoo');
		$block.find('.fieldBlockHeader').text('Địa chỉ');
		var $billLabel = $form.find('[name="bill_street"]').closest('tr').find('td.fieldLabel label').first();
		var $shipLabel = $form.find('[name="ship_street"]').closest('tr').find('td.fieldLabel label').first();
		if ($billLabel.length) {
			$billLabel.html('<span class="redColor">*</span> Địa chỉ');
		}
		if ($shipLabel.length) {
			$shipLabel.html('<span class="redColor">*</span> Địa chỉ vận chuyển');
		}
	}

	function injectShipSameCheckbox($form) {
		if ($form.find('#mkInvShipSameAsBill').length) {
			return;
		}
		var $shipLabel = $form.find('[name="ship_street"]').closest('tr').find('td.fieldLabel').first();
		if (!$shipLabel.length) {
			return;
		}
		$shipLabel.append(
			'<label class="mk-inv-ship-same">' +
				'<input type="checkbox" id="mkInvShipSameAsBill" /> Giống địa chỉ lập hóa đơn' +
				'</label>'
		);
		$form.find('#mkInvShipSameAsBill').on('change', function () {
			var $ship = $form.find('[name="ship_street"]');
			if (this.checked) {
				$ship.val($form.find('[name="bill_street"]').val() || '').prop('readonly', true);
			} else {
				$ship.prop('readonly', false);
			}
		});
		$form.find('[name="bill_street"]').on('input.mkInvShipSame', function () {
			syncShipSameAsBill($form);
		});
	}

	function registerAddressAutofill($form) {
		if ($form.data('mkInvAddrAutofill')) {
			return;
		}
		$form.data('mkInvAddrAutofill', true);

		var onOpp = function () {
			setTimeout(function () {
				fillAddressFromPotential($form);
			}, 120);
		};
		var onAccount = function () {
			var accountId = parseInt($form.find('[name="account_id"]').val(), 10) || 0;
			if (accountId) {
				fillAddressFromAccount($form, accountId);
			}
		};
		var onContact = function () {
			var contactId = parseInt($form.find('[name="contact_id"]').val(), 10) || 0;
			if (contactId) {
				fillAddressFromContact($form, contactId);
			}
		};

		$form.on(REF_SEL, '[name="potential_id"]', onOpp);
		$form.on('change.mkInvAddr', '[name="potential_id"]', onOpp);
		$form.on(POST_REF, '[name="account_id"]', onAccount);
		$form.on(REF_SEL, '[name="account_id"]', onAccount);
		$form.on(POST_REF, '[name="contact_id"]', onContact);
		$form.on(REF_SEL, '[name="contact_id"]', onContact);

		var initialPot = parseInt($form.find('[name="potential_id"]').val(), 10) || 0;
		if (initialPot) {
			setTimeout(function () {
				fillAddressFromPotential($form);
			}, 400);
		}
	}

	function restructureAddressHorizontal($form) {
		var $block = $form.find('.mk-inv-address-odoo');
		if (!$block.length || $block.data('mkInvAddrHoriz')) { return; }

		var $billTa = $form.find('textarea[name="bill_street"]');
		var $shipTa = $form.find('textarea[name="ship_street"]');
		if (!$billTa.length || !$shipTa.length) { return; }
		$block.data('mkInvAddrHoriz', true);

		var $table = $block.find('table.addressBlock');
		if (!$table.length) { $table = $block.find('table').first(); }
		if (!$table.length) { return; }

		$table.addClass('mk-inv-hide-legacy');
		$block.find('> hr').addClass('mk-inv-hide-legacy');

		var $wrap = $('<div class="mk-inv-addr-horiz"></div>');

		var $colLeft = $('<div class="mk-inv-addr-col"></div>');
		$colLeft.append('<div class="mk-inv-addr-label-wrap"><span class="redColor">*</span> Địa chỉ</div>');
		$colLeft.append($billTa.detach());

		var $colRight = $('<div class="mk-inv-addr-col"></div>');
		var $shipLabelHtml = '<div class="mk-inv-addr-label-wrap"><span class="redColor">*</span> Địa chỉ vận chuyển</div>';
		$colRight.append($shipLabelHtml);
		var $shipSame = $form.find('#mkInvShipSameAsBill').closest('label');
		if ($shipSame.length) {
			$colRight.find('.mk-inv-addr-label-wrap').append(' ').append($shipSame.detach());
		}
		$colRight.append($shipTa.detach());

		$wrap.append($colLeft).append($colRight);
		$block.find('.fieldBlockHeader').after($wrap);
	}

	function initAddressOdoo($form) {
		var $block = $form.find('.fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"]');
		if (!$block.length || $block.data('mkInvAddrOdoo')) {
			return;
		}
		$block.data('mkInvAddrOdoo', true);
		$block.addClass('mk-inv-address-odoo');
		hideAddressDetailRows($form);
		localizeAddressBlock($form);
		injectShipSameCheckbox($form);
		restructureAddressHorizontal($form);
		registerAddressAutofill($form);
	}

	function parseMoney(value) {
		if (value === null || value === undefined) {
			return 0;
		}
		if (typeof value === 'number') {
			return isNaN(value) ? 0 : value;
		}

		var text = String(value)
			.replace(/\u00a0/g, ' ')
			.replace(/đ/gi, '')
			.replace(/₫/g, '')
			.trim()
			.replace(/\s/g, '');

		if (text === '' || text === '-') {
			return 0;
		}

		var hasComma = text.indexOf(',') >= 0;
		var hasDot = text.indexOf('.') >= 0;

		if (hasComma && hasDot) {
			var lastComma = text.lastIndexOf(',');
			var lastDot = text.lastIndexOf('.');
			if (lastComma > lastDot) {
				text = text.replace(/\./g, '').replace(',', '.');
			} else {
				text = text.replace(/,/g, '');
			}
		} else if (hasComma) {
			var commaParts = text.split(',');
			if (commaParts.length === 2 && commaParts[1].length <= 2) {
				text = commaParts[0].replace(/\./g, '') + '.' + commaParts[1];
			} else {
				text = text.replace(/,/g, '');
			}
		} else if (hasDot) {
			var dotParts = text.split('.');
			if (dotParts.length > 2 || (dotParts.length === 2 && dotParts[1].length === 3)) {
				text = text.replace(/\./g, '');
			}
		}

		text = text.replace(/[^\d.-]/g, '');
		var n = parseFloat(text);
		return isNaN(n) ? 0 : n;
	}

	function formatVnd(value) {
		var n = Math.round(parseMoney(value));
		return n.toLocaleString('vi-VN') + ' đ';
	}

	function sumLinePreTax($form) {
		var sum = 0;
		$form.find('tr.lineItemRow').each(function () {
			var raw = readAmountRaw($(this).find('.productTotal'));
			sum += raw;
		});
		return sum;
	}

	function ensureGroupTaxMode($form) {
		var $taxType = $form.find('#taxtype');
		if ($taxType.length && $taxType.val() !== 'group') {
			$taxType.val('group').trigger('change');
		}
		var taxPct = getPrimaryTaxPercent($form);
		$form.find('.groupTaxPercentage').each(function (idx) {
			if (idx === 0 && (!$(this).val() || parseFloat($(this).val()) <= 0)) {
				$(this).val(taxPct);
			}
		});
		$form.find('tr.lineItemRow .taxPercentage').each(function () {
			if (!$(this).val() || parseFloat($(this).val()) <= 0) {
				$(this).val(taxPct);
			}
		});
	}

	var productCatalogPromise = null;

	function loadProductCatalog() {
		if (productCatalogPromise) {
			return productCatalogPromise;
		}
		productCatalogPromise = $.Deferred();
		if (typeof window !== 'undefined' && window.MK_WH_PRODUCT_CATALOG && window.MK_WH_PRODUCT_CATALOG.length) {
			productCatalogPromise.resolve(window.MK_WH_PRODUCT_CATALOG);
			return productCatalogPromise.promise();
		}
		if (typeof app === 'undefined' || !app.request) {
			productCatalogPromise.resolve([]);
			return productCatalogPromise.promise();
		}
		app.request
			.post({ data: { module: 'Warehouse', action: 'WhMgmtApi', mode: 'product_catalog' } })
			.then(function (err, res) {
				var list = !err && res && res.products ? res.products : [];
				productCatalogPromise.resolve(list);
			});
		return productCatalogPromise.promise();
	}

	function triggerLineRecalc($row, $form) {
		$row.find('.qty, .listPrice').first().trigger('focusout');
		if (typeof Inventory_Edit_Js !== 'undefined') {
			try {
				var inst = Inventory_Edit_Js.getInstanceByModuleName($form.find('[name="module"]').val());
				if (inst && inst.lineItemRowHolder) {
					inst.lineItemRowHolder.trigger('focusout');
				}
			} catch (e) { /* ignore */ }
		}
		setTimeout(function () {
			syncRowAmounts($row);
			syncTotalsDisplay($form);
		}, 80);
	}

	function applySelect2ToProductDropdown($sel) {
		if (!$sel.length || $sel.data('mkSelect2Applied')) {
			return;
		}
		$sel.data('mkSelect2Applied', true);
		try {
			if ($.fn.select2) {
				$sel.select2({
					placeholder: '— Chọn sản phẩm —',
					allowClear: true,
					width: '100%',
					minimumInputLength: 0,
					// Always show search box (product lists can be long)
					minimumResultsForSearch: 0,
					formatNoMatches: function () { return 'Không tìm thấy sản phẩm'; },
					formatSearching: function () { return 'Đang tìm...'; },
					dropCssClass: 'mk-inv-s2-drop'
				});
				$sel.data('select2').container.addClass('mk-inv-product-select-s2');
			}
		} catch (e) { /* select2 not available, plain select is fine */ }
	}

	function cleanupLegacyProductCell($row, $nameInput, $productTd) {
		if ($nameInput.data('ui-autocomplete')) {
			try {
				$nameInput.autocomplete('destroy');
			} catch (e) { /* ignore */ }
		}
		$nameInput.removeClass('autoComplete').addClass('mk-inv-hide-legacy').attr({ type: 'hidden', tabindex: '-1' });
		// Some vtiger templates wrap the whole product UI inside `.col-lg-10`.
		// If we hide that wrapper, our injected dropdown disappears too.
		$productTd
			.find('.itemNameDiv .col-lg-10')
			.filter(function () {
				return $(this).find('.mk-inv-product-select, .mk-inv-product-select-s2').length === 0;
			})
			.addClass('mk-inv-hide-legacy');
		$productTd.find('.lineItemCommentBox').closest('div').addClass('mk-inv-hide-legacy');
		// Keep our new product dropdown Select2 visible
		$productTd
			.find('.itemNameDiv > .select2-container')
			.not('.mk-inv-product-select-s2')
			.addClass('mk-inv-hide-legacy');
	}

	function injectProductDropdown($row, $form) {
		var $productTd = $row.find('input.productName').closest('td');
		if (!$productTd.length || $productTd.find('.mk-inv-product-select').length) {
			return;
		}
		var $nameInput = $row.find('input.productName');
		var $hiddenId = $row.find('input.selectedModuleId');
		var $listPrice = $row.find('input.listPrice');
		var $entityType = $row.find('input[name^="entityType"]');

		var $sel = $('<select class="mk-inv-product-select inputElement" title="Sản phẩm" disabled="disabled"></select>');
		$sel.append('<option value="">— Đang tải sản phẩm… —</option>');

		loadProductCatalog().then(function (products) {
			$sel.prop('disabled', false);
			$sel.empty().append('<option value="">— Chọn sản phẩm —</option>');
			products.forEach(function (p) {
				var id = String(p.id || '');
				var displayName = decodeText(p.name || id);
				$sel.append(
					$('<option></option>')
						.attr('value', id)
						.attr('data-name', displayName)
						.attr('data-price', p.price || 0)
						.attr('data-sku', p.sku || '')
						.text(displayName)
				);
			});
			var currentId = ($hiddenId.val() || '').trim();
			if (currentId) {
				$sel.val(currentId);
			}
			applySelect2ToProductDropdown($sel);
		});

		$sel.on('change.mkInvProduct', function () {
			var $opt = $(this).find('option:selected');
			var id = $(this).val();
			var name = decodeText($opt.attr('data-name') || $opt.text());
			var price = parseMoney($opt.attr('data-price') || 0);
			$hiddenId.val(id || '');
			$nameInput.val(name);
			if ($entityType.length) {
				$entityType.val('ProductsServices');
			}
			if ($listPrice.length && price > 0) {
				$listPrice.val(price);
			}
			triggerLineRecalc($row, $form);
			syncRowStockHint($row, $form);
		});

		cleanupLegacyProductCell($row, $nameInput, $productTd);
		$row.find('.lineItemPopup').addClass('mk-inv-hide-legacy');
		$productTd.find('.itemNameDiv').prepend($sel);
	}

	function readAmountRaw($el, $hiddenFallback) {
		if (!$el || !$el.length) {
			return 0;
		}
		var text = ($el.text() || '').trim();
		if (!/đ/i.test(text)) {
			var plain = parseMoney(text);
			if (plain > 0 || text === '0') {
				return plain;
			}
		}
		if ($hiddenFallback && $hiddenFallback.length) {
			var hiddenVal = parseMoney($hiddenFallback.val());
			if (hiddenVal > 0) {
				return hiddenVal;
			}
		}
		return parseMoney(text);
	}

	function writeAmountDisplay($el, raw) {
		if (!$el || !$el.length) {
			return;
		}
		$el.data('mkRawAmount', raw);
		var formatted = formatVnd(raw);
		if ($el.text() !== formatted) {
			$el.text(formatted);
		}
	}

	function getPrimaryTaxPercent($form) {
		var $taxSel = $form.find('.mk-inv-tax-select').first();
		if ($taxSel.length) {
			var selVal = $taxSel.val();
			if (selVal === 'exempt') { return 0; }
			var parsed = parseFloat(selVal);
			if (!isNaN(parsed)) { return parsed; }
		}
		var pct = 0;
		$form.find('.groupTaxPercentage').each(function () {
			var v = parseFloat($(this).val());
			if (!isNaN(v) && v > 0) {
				pct = v;
				return false;
			}
		});
		if (!pct) {
			$form.find('tr.lineItemRow .taxPercentage').each(function () {
				var v = parseFloat($(this).val());
				if (!isNaN(v) && v > 0) {
					pct = v;
					return false;
				}
			});
		}
		return pct;
	}

	function syncProductDesc($row) {
		var $productTd = $row.find('input.productName').closest('td');
		var desc = ($row.find('.lineItemCommentBox').val() || '').trim();
		var $desc = $productTd.find('.mk-inv-product-desc');
		if (!$desc.length) {
			$desc = $('<div class="mk-inv-product-desc" aria-hidden="true"></div>');
			$productTd.find('.itemNameDiv').after($desc);
		}
		if (desc) {
			$desc.html(desc.replace(/\n/g, '<br>')).show();
			$row.addClass('mk-inv-has-desc');
		} else {
			$desc.empty().hide();
			$row.removeClass('mk-inv-has-desc');
		}
	}

	var TAX_RATE_OPTIONS = [
		{ value: '10', label: '10%' },
		{ value: '8', label: '8%' },
		{ value: '5', label: '5%' },
		{ value: '0', label: '0%' },
		{ value: 'exempt', label: 'VAT EXEMPTION' }
	];

	function syncRowTaxPill($row, $form) {
		var $sel = $row.find('.mk-inv-tax-select');
		if (!$sel.length) {
			return;
		}
		var pct = 0;
		$row.find('.taxPercentage').each(function () {
			var v = parseFloat($(this).val());
			if (!isNaN(v) && v > 0) {
				pct = v;
				return false;
			}
		});
		if (!pct && !$sel.data('mkUserChanged')) {
			pct = getPrimaryTaxPercent($form);
		}
		var strVal = String(pct);
		if ($sel.val() !== strVal && !$sel.data('mkUserChanged')) {
			$sel.val(strVal);
		}
	}

	function injectTaxDropdown($row, $form) {
		var $taxTd = $row.find('.mk-inv-col-tax');
		if (!$taxTd.length || $taxTd.find('.mk-inv-tax-select').length) {
			return;
		}
		$taxTd.find('.mk-inv-tax-pill').remove();

		var $sel = $('<select class="mk-inv-tax-select inputElement" title="Thuế"></select>');
		TAX_RATE_OPTIONS.forEach(function (opt) {
			$sel.append($('<option></option>').attr('value', opt.value).text(opt.label));
		});

		var currentPct = 0;
		$row.find('.taxPercentage').each(function () {
			var v = parseFloat($(this).val());
			if (!isNaN(v) && v > 0) {
				currentPct = v;
				return false;
			}
		});
		if (!currentPct) {
			currentPct = getPrimaryTaxPercent($form);
		}
		$sel.val(String(currentPct));

		$sel.on('change.mkInvTax', function () {
			$sel.data('mkUserChanged', true);
			var val = $(this).val();
			var pct = val === 'exempt' ? 0 : parseFloat(val) || 0;
			$row.find('.taxPercentage').each(function () {
				$(this).val(pct);
			});
			var $groupTax = $form.find('.groupTaxPercentage').first();
			if ($groupTax.length) {
				$groupTax.val(pct);
			}
			triggerLineRecalc($row, $form);
			setTimeout(function () {
				$form.data('mkInvSyncingTotals', false);
				var fn = $form.data('mkScheduleRealtimeSync');
				if (fn) { fn(); }
			}, 150);
		});

		$taxTd.empty().append($sel);
	}

	function syncRowAmounts($row) {
		var $total = $row.find('.productTotal');
		if (!$total.length) {
			return;
		}
		var raw = readAmountRaw($total);
		writeAmountDisplay($total, raw);
		var $amountTd = $total.closest('td');
		$amountTd.addClass('mk-inv-col-amount');
		$amountTd.children().not('.productTotal, .mk-inv-line-del, .mk-inv-amount-wrap').addClass('mk-inv-hide-legacy');
	}

	function headerLabelForCell($sampleRow, index) {
		var $cell = $sampleRow.find('> td').eq(index);
		if (!$cell.length) {
			return '';
		}
		if (index === 0) {
			return '';
		}
		if ($cell.hasClass('mk-inv-col-net-hide')) {
			return '__hide__';
		}
		if ($cell.find('input.productName').length) {
			return 'Sản phẩm';
		}
		if ($cell.hasClass('mk-inv-col-unit')) {
			return 'ĐVT';
		}
		if ($cell.find('input.qty, .qty').length) {
			return 'Số lượng';
		}
		if ($cell.find('input.listPrice').length) {
			return 'Đơn giá';
		}
		if ($cell.hasClass('mk-inv-col-tax') || $cell.find('.mk-inv-tax-select').length) {
			return 'Thuế';
		}
		if ($cell.find('.productTotal').length) {
			return 'Số tiền';
		}
		return '';
	}

	function ensureOdooHeaderColumns($table) {
		var $header = $table.find('> tr').first();
		var $sample = $table.find('tr.lineItemRow').first();
		if (!$header.length || !$sample.length) {
			return;
		}

		if (!$header.data('mkOdooColsInjected')) {
			var $qtyCell = $sample.find('input.qty, .qty').first().closest('td');
			if ($qtyCell.length && !$header.find('.mk-inv-col-unit-head').length) {
				var qtyIdx = $qtyCell.index();
				$header.find('> td').eq(qtyIdx).after('<td class="mk-inv-col-unit-head"><strong>ĐVT</strong></td>');
			}
			var $priceCell = $sample.find('input.listPrice').first().closest('td');
			if ($priceCell.length && !$header.find('.mk-inv-col-tax-head').length) {
				var priceIdx = $priceCell.index();
				$header.find('> td').eq(priceIdx).after('<td class="mk-inv-col-tax-head"><strong>Thuế</strong></td>');
			}
			$header.data('mkOdooColsInjected', true);
		}

		$header.find('> td').each(function (idx) {
			var $td = $(this);
			var label = headerLabelForCell($sample, idx);
			if (label === '__hide__') {
				$td.addClass('mk-inv-col-net-hide').empty();
				return;
			}
			if (idx === 0) {
				$td.empty().addClass('mk-inv-col-drag');
				return;
			}
			if (label) {
				$td.html('<strong>' + label + '</strong>');
			}
		});
		$header.data('mkOdooHeader', true);
	}

	function injectUnitSelect($row, $unitTd) {
		if ($unitTd.find('.mk-inv-unit-select').length) {
			return;
		}
		var $sel = $('<select class="mk-inv-unit-select inputElement" title="ĐVT"></select>');
		UNIT_OPTIONS.forEach(function (opt) {
			$sel.append($('<option></option>').attr('value', opt.value).text(opt.label));
		});
		$sel.append($('<option></option>').attr('value', '__search__').text('Tìm kiếm thêm...'));
		$unitTd.empty().append($sel);
		$sel.on('change.mkInvUnit', function () {
			if (this.value !== '__search__') {
				return;
			}
			var custom = window.prompt('Nhập đơn vị:', 'Đơn vị');
			if (custom && String(custom).trim()) {
				custom = String(custom).trim();
				if (!$sel.find('option[value="' + custom.replace(/"/g, '') + '"]').length) {
					$sel.find('option[value="__search__"]').before(
						$('<option></option>').attr('value', custom).text(custom)
					);
				}
				$sel.val(custom);
			} else {
				$sel.val('Đơn vị');
			}
		});
	}

	function syncRowStockHint($row, $form) {
		var warehouseId = $form.find('input[name="mk_warehouse_id"]').val() || $form.data('mkWarehouseId');
		if (!warehouseId || typeof app === 'undefined' || !app.request) {
			$row.find('.mk-inv-stock-hint').remove();
			return;
		}
		var productId = parseInt($row.find('input.selectedModuleId').val(), 10) || 0;
		var productName = ($row.find('input.productName').val() || '').trim();
		var qty = parseMoney($row.find('.qty').val());
		if (!productId && !productName) {
			return;
		}
		var cacheKey = warehouseId + ':' + productId + ':' + productName;
		var $hint = $row.find('.mk-inv-stock-hint');
		if (!$hint.length) {
			$hint = $('<div class="mk-inv-stock-hint"></div>');
			$row.find('input.qty').closest('td').append($hint);
		}
		if ($row.data('mkStockCacheKey') === cacheKey && $row.data('mkStockCachedHtml')) {
			$hint.html($row.data('mkStockCachedHtml'));
			return;
		}
		app.request
			.post({
				data: {
					module: 'SalesOrder',
					action: 'CheckWarehouseStock',
					warehouse_id: warehouseId,
					product_id: [productId],
					product_name: [productName],
					quantity: [qty || 1]
				}
			})
			.then(function (err, res) {
				if (err || !res || !res.lines || !res.lines.length) {
					return;
				}
				var line = res.lines[0];
				var available = parseMoney(line.available);
				var ok = line.ok;
				var html =
					'Tồn kho: <strong>' +
					available.toLocaleString('vi-VN') +
					'</strong>' +
					(ok ? '' : ' <span class="mk-inv-stock-warn">(không đủ)</span>');
				$row.data('mkStockCacheKey', cacheKey);
				$row.data('mkStockCachedHtml', html);
				$hint.html(html);
				$row.toggleClass('mk-inv-row--stock-warn', !ok);
			});
	}

	function ensureOdooRowColumns($row, $form) {
		if ($row.hasClass('mk-inv-section-row')) {
			return;
		}
		var $cells = $row.find('> td');
		if (!$row.find('.mk-inv-col-unit').length && $cells.length >= 3) {
			var $unitTd = $('<td class="mk-inv-col-unit"></td>');
			$cells.eq(2).after($unitTd);
			injectUnitSelect($row, $unitTd);
		} else if ($row.find('.mk-inv-col-unit').length && !$row.find('.mk-inv-unit-select').length) {
			injectUnitSelect($row, $row.find('.mk-inv-col-unit').first());
		}
		var $priceTd = $row.find('input.listPrice').closest('td');
		if ($priceTd.length && !$row.find('.mk-inv-col-tax').length) {
			$priceTd.after('<td class="mk-inv-col-tax"></td>');
		}
		injectTaxDropdown($row, $form);
		syncRowTaxPill($row, $form);
		syncProductDesc($row);
		syncRowAmounts($row);
		injectProductDropdown($row, $form);

		var $tools = $row.find('> td:first-child');
		$tools.addClass('mk-inv-col-drag');
		$tools.find('img[src*="drag"]').closest('a, span').removeClass('mk-inv-hide-legacy');

		var $amountTd = $row.find('.productTotal').closest('td');
		var $del = $tools.find('.deleteRow').first();
		if ($del.length && $amountTd.length && !$amountTd.find('.mk-inv-line-del').length) {
			$amountTd.append(
				$('<span class="mk-inv-line-del" title="Xóa dòng"></span>').append($del.detach())
			);
		}
		$tools.find('.deleteRow').addClass('mk-inv-hide-legacy');

		$row.find('input.productName').attr('placeholder', 'Chọn sản phẩm từ dropdown');
		$row.find('.priceBookPopup').addClass('mk-inv-hide-legacy');
		$row.find('.individualDiscount').closest('div').addClass('mk-inv-hide-legacy');
		$row.find('.totalAfterDiscount').addClass('mk-inv-hide-legacy');
		$row.find('.itemNameDiv .lineItemPopup').addClass('mk-inv-hide-legacy');
		$row.find('> td:last-child').addClass('mk-inv-col-net-hide');
		syncRowStockHint($row, $form);
	}

	function restyleLineItemRows($form) {
		var $table = $form.find('#lineItemTab');
		if (!$table.length) {
			return;
		}
		$table.addClass('mk-inv-odoo-lines-table');
		$table.find('tr.lineItemRow').each(function () {
			ensureOdooRowColumns($(this), $form);
		});
		ensureOdooHeaderColumns($table);
	}

	function setFormattedText($el, formatted) {
		if ($el.length && $el.text() !== formatted) {
			$el.text(formatted);
		}
	}

	function syncTotalsDisplay($form) {
		if ($form.data('mkInvSyncingTotals')) {
			return;
		}
		$form.data('mkInvSyncingTotals', true);

		var $result = $form.find('#lineItemResult');
		if (!$result.length) {
			$form.data('mkInvSyncingTotals', false);
			return;
		}

		ensureGroupTaxMode($form);

		var preTax = readAmountRaw($result.find('#preTaxTotal'), $result.find('#pre_tax_total'));
		if (preTax <= 0) {
			preTax = sumLinePreTax($form);
		}

		var taxPct = getPrimaryTaxPercent($form);
		var taxAmt = readAmountRaw($result.find('#tax_final'));
		if (taxAmt <= 0 && preTax > 0 && taxPct > 0) {
			taxAmt = Math.round((preTax * taxPct) / 100);
		}

		var grand = readAmountRaw($result.find('#grandTotal, .grandTotal'), $result.find('#total'));
		if (grand <= preTax && taxAmt > 0) {
			grand = preTax + taxAmt;
		} else if (grand <= 0 && preTax > 0) {
			grand = preTax + taxAmt;
		}

		writeAmountDisplay($result.find('#preTaxTotal'), preTax);
		writeAmountDisplay($result.find('#tax_final'), taxAmt);
		writeAmountDisplay($result.find('#grandTotal, .grandTotal'), grand);

		$result.find('#pre_tax_total').val(preTax);
		$form.find('#total, input[name="total"]').val(grand);
		$form.find('.groupTaxTotal').first().val(taxAmt);
		$result.find('#tax_final').attr('data-raw', taxAmt);

		var $taxRow = $result.find('#group_tax_row');
		if ($taxRow.length) {
			if (taxAmt > 0 || taxPct > 0) {
				$taxRow.removeClass('mk-inv-totals-hide hide').addClass('mk-inv-totals-row mk-inv-totals-row--tax');
				$taxRow
					.find('td:first')
					.html('<div class="pull-right"><strong>Thuế GTGT ' + taxPct + '%</strong></div>');
			} else {
				$taxRow.addClass('mk-inv-totals-hide');
			}
		}

		$form.data('mkInvSyncingTotals', false);
	}

	function watchTotalsAndLines($form) {
		if ($form.data('mkInvTotalsWatch')) {
			return;
		}
		$form.data('mkInvTotalsWatch', true);

		var $result = $form.find('#lineItemResult');
		['#preTaxTotal', '#tax_final', '#grandTotal'].forEach(function (sel) {
			var el = $result.find(sel)[0];
			if (!el || typeof MutationObserver === 'undefined') {
				return;
			}
			var obs = new MutationObserver(function () {
				syncTotalsDisplay($form);
				$form.find('tr.lineItemRow').each(function () {
					syncRowAmounts($(this));
					syncRowTaxPill($(this), $form);
				});
			});
			obs.observe(el, { childList: true, characterData: true, subtree: true });
		});

		var _realtimeTimer = null;
		function scheduleRealtimeSync() {
			if (_realtimeTimer) { clearTimeout(_realtimeTimer); }
			_realtimeTimer = setTimeout(function () {
				_realtimeTimer = null;
				$form.data('mkInvSyncingTotals', true);
				var lineSum = 0;
				$form.find('tr.lineItemRow').each(function () {
					var $r = $(this);
					var qty = parseMoney($r.find('.qty').val());
					var price = parseMoney($r.find('.listPrice').val());
					var total = qty * price;
					var $pt = $r.find('.productTotal');
					if ($pt.length) {
						$pt.data('mkRawAmount', total);
						writeAmountDisplay($pt, total);
					}
					lineSum += total;
				});
				var taxPct = getPrimaryTaxPercent($form);
				var taxAmt = Math.round((lineSum * taxPct) / 100);
				var grand = lineSum + taxAmt;
				var $result = $form.find('#lineItemResult');
				if ($result.length) {
					writeAmountDisplay($result.find('#netTotal, .netTotal'), lineSum);
					$result.find('#subtotal, input[name="subtotal"]').val(lineSum);
					writeAmountDisplay($result.find('#preTaxTotal'), lineSum);
					$result.find('#pre_tax_total').val(lineSum);
					writeAmountDisplay($result.find('#tax_final'), taxAmt);
					$form.find('.groupTaxTotal').first().val(taxAmt);
					writeAmountDisplay($result.find('#grandTotal, .grandTotal'), grand);
					$form.find('#total, input[name="total"]').val(grand);
					var $taxRow = $result.find('#group_tax_row');
					if ($taxRow.length) {
						$taxRow.removeClass('mk-inv-totals-hide hide').addClass('mk-inv-totals-row mk-inv-totals-row--tax');
						$taxRow.find('td:first').html('<div class="pull-right"><strong>Thuế GTGT ' + taxPct + '%</strong></div>');
					}
				}
				setTimeout(function () { $form.data('mkInvSyncingTotals', false); }, 50);
			}, 100);
		}

		$form.on('focusout.mkInvTot change.mkInvTot', '.qty, .listPrice, .taxPercentage, .groupTaxPercentage, .mk-inv-tax-select', function () {
			setTimeout(function () {
				restyleLineItemRows($form);
				syncTotalsDisplay($form);
			}, 60);
		});

		$form.on('input.mkInvTotRealtime keyup.mkInvTotRealtime change.mkInvTotRealtime', '.qty, .listPrice', function () {
			scheduleRealtimeSync();
		});

		function bindDirectPriceEvents() {
			$form.find('input.listPrice').each(function () {
				var $el = $(this);
				if ($el.data('mkDirectPriceBound')) { return; }
				$el.data('mkDirectPriceBound', true);
				$el.on('input.mkPriceDirect keyup.mkPriceDirect change.mkPriceDirect', function () {
					scheduleRealtimeSync();
				});
				$el.on('focusout.mkPriceDirect', function () {
					var $row = $el.closest('tr.lineItemRow');
					if ($row.length) {
						triggerLineRecalc($row, $form);
						setTimeout(function () { scheduleRealtimeSync(); }, 200);
					}
				});
			});
		}
		bindDirectPriceEvents();
		$form.data('mkBindDirectPriceEvents', bindDirectPriceEvents);
		$form.data('mkScheduleRealtimeSync', scheduleRealtimeSync);

		var _lastPriceSnapshot = {};
		setInterval(function () {
			var changed = false;
			$form.find('tr.lineItemRow').each(function () {
				var $r = $(this);
				var rowId = $r.attr('id') || $r.index();
				var curPrice = $r.find('.listPrice').val() || '';
				var curQty = $r.find('.qty').val() || '';
				var key = curQty + '|' + curPrice;
				if (_lastPriceSnapshot[rowId] !== key) {
					_lastPriceSnapshot[rowId] = key;
					changed = true;
				}
			});
			if (changed) {
				scheduleRealtimeSync();
			}
		}, 500);

		$form.on('mkSoWarehouseSelected.mkInv', function (_e, warehouse) {
			if (warehouse && warehouse.id) {
				$form.data('mkWarehouseId', warehouse.id);
			}
			$form.find('tr.lineItemRow').each(function () {
				$(this).removeData('mkStockCacheKey mkStockCachedHtml');
				syncRowStockHint($(this), $form);
			});
		});

		$form.on('input.mkInvDesc', '.lineItemCommentBox', function () {
			syncProductDesc($(this).closest('tr'));
		});

		$form.on('change.mkInvTaxDd', '.mk-inv-tax-select', function () {
			setTimeout(function () {
				$form.data('mkInvSyncingTotals', false);
				restyleLineItemRows($form);
				scheduleRealtimeSync();
			}, 80);
		});

		if (typeof app !== 'undefined' && app.event) {
			app.event.on('post.LineItemPopupSelection.click', function () {
				setTimeout(function () {
					restyleLineItemRows($form);
					syncTotalsDisplay($form);
					var fn = $form.data('mkBindDirectPriceEvents');
					if (fn) { fn(); }
				}, 200);
			});
		}
	}

	function polishLineItemsShell($form) {
		var $lineBlock = $form.find('.mk-inv-lineitems-odoo');
		var $container = $lineBlock.find('.lineitemTableContainer');
		if ($container.length && !$container.parent().hasClass('mk-inv-lines-card')) {
			$container.wrap('<div class="mk-inv-lines-card"></div>');
		}
		$form.find('.mk-inv-line-actions').remove();
	}

	function initOdooTabs($lineBlock) {
		if ($lineBlock.find('.mk-inv-odoo-tabs').length) {
			return;
		}
		var $tabs = $(
			'<div class="mk-inv-odoo-tabs" role="tablist">' +
				'<button type="button" class="mk-inv-odoo-tab is-active" role="tab">Chi tiết đơn hàng</button>' +
				'</div>'
		);
		$lineBlock.find('.lineitemTableContainer').before($tabs);
	}

	function initLineActionLinks() {
		/* Footer links removed per UX request — only "Thêm sản phẩm" button remains. */
	}

	function initAddLineButton($form) {
		var $addBtn = $form.find('#addProductsServices');
		if (!$addBtn.length || $addBtn.data('mkInvOdooAdd')) {
			return;
		}
		$addBtn.data('mkInvOdooAdd', true);
		$addBtn.removeClass('btn btn-default').addClass('mk-inv-add-line-btn');
		$addBtn.empty().append('<span class="mk-inv-add-line-btn__plus" aria-hidden="true">+</span> Thêm sản phẩm');
	}

	function rebuildPaymentMethodSelect($select, currentVal) {
		$select.empty().append('<option value="">— Chọn hình thức —</option>');
		PAYMENT_METHOD_OPTIONS.forEach(function (item) {
			$select.append($('<option></option>').attr('value', item.value).text(item.label));
		});
		if (currentVal) {
			var hasOption = false;
			$select.find('option').each(function () {
				if ($(this).val() === currentVal) {
					hasOption = true;
					return false;
				}
			});
			if (!hasOption) {
				$select.append($('<option></option>').attr('value', currentVal).text(currentVal));
			}
			$select.val(currentVal);
		}
	}

	function initPaymentTerms($form) {
		var $lineBlock = $form.find('.mk-inv-lineitems-odoo');
		if (!$lineBlock.length || $lineBlock.data('mkInvPaymentTerms')) {
			return;
		}
		$lineBlock.data('mkInvPaymentTerms', true);

		$lineBlock.find('.mk-inv-line-toolbar').addClass('mk-inv-hide-legacy');
		$form.find('#currency_id').closest('.col-sm-4').addClass('mk-inv-hide-legacy');
		$form.find('#taxtype').closest('.col-sm-4').addClass('mk-inv-hide-legacy');
		$form.find('[name="region_id"]').closest('.col-sm-4').addClass('mk-inv-hide-legacy');

		var $container = $lineBlock.find('.lineitemTableContainer');
		if ($container.find('.mk-inv-payment-terms').length) {
			return;
		}

		var $existing = $form.find('[name="mk_payment_terms"]');
		if ($existing.length) {
			$existing.closest('tr').addClass('mk-inv-hide-legacy');
		}

		var $wrap = $('<div class="mk-inv-payment-terms mk-inv-payment-terms--card"></div>');
		$wrap.append(
			'<div class="mk-inv-payment-terms__row">' +
				'<span class="mk-inv-payment-terms__icon" aria-hidden="true"></span>' +
				'<div class="mk-inv-payment-terms__content">' +
				'<label class="mk-inv-payment-terms__label" for="mkInvPaymentTermsSelect">Hình thức thanh toán</label>' +
				'</div></div>'
		);

		var $select;
		var currentVal = '';
		if ($existing.length && $existing.is('select')) {
			currentVal = ($existing.val() || '').trim();
			$select = $existing.detach().attr('id', 'mkInvPaymentTermsSelect');
			rebuildPaymentMethodSelect($select, currentVal);
		} else {
			$select = $('<select class="inputElement" name="mk_payment_terms" id="mkInvPaymentTermsSelect"></select>');
			currentVal = $existing.length ? ($existing.val() || '').trim() : '';
			rebuildPaymentMethodSelect($select, currentVal);
		}
		$wrap.find('.mk-inv-payment-terms__content').append($('<div class="mk-inv-payment-terms__field"></div>').append($select));
		$container.prepend($wrap);

		if (typeof vtUtils !== 'undefined' && vtUtils.applyFieldElementsView) {
			vtUtils.applyFieldElementsView($wrap);
		}
	}

	function initTotalsOdoo($form) {
		var $result = $form.find('#lineItemResult');
		if (!$result.length || $result.data('mkInvTotalsOdoo')) {
			return;
		}
		$result.data('mkInvTotalsOdoo', true);
		var $block = $result.closest('.fieldBlockContainer');
		$block.addClass('mk-inv-totals-odoo');

		$result.find('tr').addClass('mk-inv-totals-hide');

		var $grand = $result.find('#grandTotal, .grandTotal').closest('tr');
		var $preTax = $result.find('#preTaxTotal').closest('tr');
		var $net = $result.find('#netTotal, .netTotal').closest('tr');
		var $sub = $preTax.length ? $preTax : $net;

		if ($sub.length) {
			$sub.removeClass('mk-inv-totals-hide').addClass('mk-inv-totals-row mk-inv-totals-row--sub');
			$sub.find('td:first').html('<div class="pull-right"><strong>Số tiền trước thuế</strong></div>');
		}
		var $taxRow = $result.find('#group_tax_row');
		if ($taxRow.length) {
			$taxRow.removeClass('hide mk-inv-totals-hide').addClass('mk-inv-totals-row mk-inv-totals-row--tax');
		}
		if ($grand.length) {
			$grand.removeClass('mk-inv-totals-hide').addClass('mk-inv-totals-row mk-inv-totals-row--grand');
			$grand.find('td:first').html('<div class="pull-right"><strong>Tổng</strong></div>');
		}

		syncTotalsDisplay($form);
		watchTotalsAndLines($form);
	}

	function initLineItemsOdoo($form) {
		var $lineBlock = $form.find('#lineItemTab').closest('.fieldBlockContainer');
		if (!$lineBlock.length || $lineBlock.data('mkInvLineOdoo')) {
			return;
		}
		$lineBlock.data('mkInvLineOdoo', true).attr('data-block', 'LBL_ITEM_DETAILS').addClass('mk-inv-lineitems-odoo');

		$lineBlock.find('> .row').first().addClass('mk-inv-hide-legacy');
		$lineBlock.find('> .row > .col-sm-3 h4.fieldBlockHeader').addClass('mk-inv-hide-legacy');
		$lineBlock.find('> br').addClass('mk-inv-hide-legacy');

		initOdooTabs($lineBlock);
		initPaymentTerms($form);
		initAddLineButton($form);
		initLineActionLinks();
		polishLineItemsShell($form);
		restyleLineItemRows($form);
		initTotalsOdoo($form);

		$form.off('post.lineItem.New.mkInvOdoo').on('post.lineItem.New.mkInvOdoo', function () {
			setTimeout(function () {
				restyleLineItemRows($form);
				syncTotalsDisplay($form);
				var fn = $form.data('mkBindDirectPriceEvents');
				if (fn) { fn(); }
			}, 50);
		});

		if (typeof app !== 'undefined' && app.event) {
			app.event.on('post.lineItem.New', function () {
				setTimeout(function () {
					restyleLineItemRows($form);
					syncTotalsDisplay($form);
					var fn = $form.data('mkBindDirectPriceEvents');
					if (fn) { fn(); }
				}, 80);
			});
		}

		setTimeout(function () {
			restyleLineItemRows($form);
			syncTotalsDisplay($form);
		}, 350);
	}

	function hideDescriptionBlock($form) {
		$form.find('.fieldBlockContainer[data-block="LBL_DESCRIPTION_INFORMATION"]').addClass('mk-inv-hide-legacy');
	}

	function init($form, options) {
		if (!$form || !$form.length) {
			return;
		}
		options = options || {};
		$form.addClass('mk-inv-form-odoo');
		if (typeof document !== 'undefined' && document.documentElement) {
			document.documentElement.classList.add('mk-inv-odoo-active');
		}
		initAddressOdoo($form);
		initLineItemsOdoo($form);
		if (options.hideDescriptionBlock !== false) {
			hideDescriptionBlock($form);
		}
	}

	window.MkInventoryOdooEdit = {
		init: init,
		fillAddressFromPotential: fillAddressFromPotential,
		fillAddressFromAccount: fillAddressFromAccount,
		restyleLineItemRows: restyleLineItemRows,
		syncTotalsDisplay: syncTotalsDisplay,
		refreshTotals: function ($form) {
			initTotalsOdoo($form);
			syncTotalsDisplay($form);
		}
	};
})(jQuery);
