/**
 * Quote / Sales Order — Odoo-style address, line items, payment terms (SALES app).
 */
(function ($) {
	'use strict';

	if (typeof document !== 'undefined' && document.documentElement) {
		document.documentElement.classList.add('mk-inv-odoo-active');
	}

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

	var MODERN_LINE_HEADER_COLUMNS = [
		{ className: 'mk-inv-col-drag', label: '' },
		{ className: 'mk-inv-col-product', label: 'Tên mục', required: true },
		{ className: 'mk-inv-col-qty', label: 'Số lượng' },
		{ className: 'mk-inv-col-unit-head mk-inv-col-unit', label: 'Đơn vị tính' },
		{ className: 'mk-inv-col-tax-head mk-inv-col-tax', label: 'Thuế' },
		{ className: 'mk-inv-col-price', label: 'Bảng giá' },
		{ className: 'mk-inv-col-amount', label: 'Tổng giá trị' }
	];

	var MODERN_LINE_COLGROUP_WIDTHS = ['52px', '24%', '92px', '132px', '104px', '148px', '156px'];

	var UNIT_OPTIONS = [
		{ value: 'Cái', label: 'Cái (Each)' },
		{ value: 'Hộp', label: 'Hộp (Box)' },
		{ value: 'Tá', label: 'Tá (Dozen)' },
		{ value: 'Thùng', label: 'Thùng' },
		{ value: 'Kg', label: 'Kg' },
		{ value: 'Mét', label: 'Mét' }
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

	function getLineItemsTableBody($table) {
		if (!$table || !$table.length) {
			return $();
		}
		var $tbody = $table.children('tbody');
		return $tbody.length ? $tbody.first() : $table;
	}

	function getLineItemHeaderRow($table) {
		return getLineItemsTableBody($table).children('tr').first();
	}

	function getLineItemTemplateRow($table) {
		return $table.find('#row0.lineItemCloneCopy, tr.lineItemCloneCopy').first();
	}

	function getLineItemSampleRow($table) {
		var $row = $table.find('tr.lineItemRow').not('.hide, .lineItemCloneCopy').first();
		if (!$row.length) {
			$row = getLineItemTemplateRow($table);
		}
		return $row;
	}

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
		return 'đ ' + n.toLocaleString('vi-VN');
	}

	function formatVndNumber(value) {
		var n = Math.round(parseMoney(value));
		return n.toLocaleString('vi-VN');
	}

	function sumLinePreTax($form) {
		var sum = 0;
		$form.find('tr.lineItemRow').each(function () {
			var $r = $(this);
			var qty = parseMoney($r.find('.qty').val());
			var price = parseMoney($r.find('.listPrice').val());
			sum += qty * price;
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
	var productCatalogCache = null;

	if (typeof window !== 'undefined' && window.MK_PRODUCT_CATALOG && window.MK_PRODUCT_CATALOG.length) {
		productCatalogCache = window.MK_PRODUCT_CATALOG;
	} else if (typeof window !== 'undefined' && window.MK_WH_PRODUCT_CATALOG && window.MK_WH_PRODUCT_CATALOG.length) {
		productCatalogCache = window.MK_WH_PRODUCT_CATALOG;
	}

	function loadProductCatalog(forceReload) {
		if (!forceReload && productCatalogCache) {
			return $.Deferred().resolve(productCatalogCache).promise();
		}
		if (!forceReload && productCatalogPromise) {
			return productCatalogPromise;
		}
		productCatalogPromise = $.Deferred();
		if (typeof window !== 'undefined' && window.MK_PRODUCT_CATALOG && window.MK_PRODUCT_CATALOG.length) {
			productCatalogCache = window.MK_PRODUCT_CATALOG;
			productCatalogPromise.resolve(productCatalogCache);
			return productCatalogPromise.promise();
		}
		if (typeof window !== 'undefined' && window.MK_WH_PRODUCT_CATALOG && window.MK_WH_PRODUCT_CATALOG.length) {
			productCatalogCache = window.MK_WH_PRODUCT_CATALOG;
			productCatalogPromise.resolve(productCatalogCache);
			return productCatalogPromise.promise();
		}
		if (typeof app === 'undefined' || !app.request) {
			productCatalogCache = [];
			productCatalogPromise.resolve([]);
			return productCatalogPromise.promise();
		}
		app.request
			.post({ data: { module: 'Inventory', action: 'ProductCatalog' } })
			.then(function (err, res) {
				var list = !err && res && res.products ? res.products : [];
				if (list.length) {
					productCatalogCache = list;
					productCatalogPromise.resolve(list);
					return;
				}
				app.request
					.post({ data: { module: 'Warehouse', action: 'WhMgmtApi', mode: 'product_catalog' } })
					.then(function (err2, res2) {
						var fallback = !err2 && res2 && res2.products ? res2.products : [];
						productCatalogCache = fallback;
						productCatalogPromise.resolve(fallback);
					});
			});
		setTimeout(function () {
			if (productCatalogPromise && productCatalogPromise.state() === 'pending') {
				productCatalogCache = productCatalogCache || [];
				productCatalogPromise.resolve(productCatalogCache);
			}
		}, 8000);
		return productCatalogPromise.promise();
	}

	function fillProductSelect($sel, products) {
		buildProductSelectOptions($sel, products || []);
		$sel.prop('disabled', false);
		$sel.data('mkCatalogReady', true);
		$sel.removeData('mkLoading');
	}

	function getInventoryEditInstance($form) {
		if (typeof Inventory_Edit_Js === 'undefined') {
			return null;
		}
		try {
			var moduleName = $form.find('[name="module"]').val();
			return Inventory_Edit_Js.getInstanceByModuleName(moduleName);
		} catch (ignore) {
			return null;
		}
	}

	function applyProductSelection($row, $form, productId) {
		var $nameInput = $row.find('input.productName').first();
		var $hiddenId = $row.find('input.selectedModuleId').first();
		var $listPrice = $row.find('input.listPrice').first();
		var $entityType = $row.find('input[name^="lineItemType"], input.lineItemType').first();
		var $opt = $row.find('.mk-inv-product-select option:selected');

		if (!productId) {
			$hiddenId.val('');
			$nameInput.val('').removeAttr('disabled');
			if ($listPrice.length) {
				$listPrice.val(0);
			}
			triggerLineRecalc($row, $form);
			return;
		}

		var moduleName = $form.find('[name="module"]').val() || 'Quotes';
		var currencyId = $form.find('#currency_id').val() || '';
		var fallbackName = decodeText($opt.attr('data-name') || $opt.text());
		var fallbackPrice = parseMoney($opt.attr('data-price') || 0);

		if ($entityType.length) {
			$entityType.val('ProductsServices');
		}

		if (typeof app === 'undefined' || !app.request) {
			$hiddenId.val(productId);
			$nameInput.val(fallbackName).attr('disabled', 'disabled');
			if ($listPrice.length && fallbackPrice > 0) {
				$listPrice.val(fallbackPrice);
			}
			triggerLineRecalc($row, $form);
			return;
		}

		var url =
			'index.php?module=Inventory&action=GetTaxes&record=' +
			encodeURIComponent(productId) +
			'&currency_id=' +
			encodeURIComponent(currencyId) +
			'&sourceModule=' +
			encodeURIComponent(moduleName);

		app.request.get({ url: url }).then(function (err, data) {
			var inst = getInventoryEditInstance($form);
			if (!err && data && data[0] && inst && inst.mapResultsToFields) {
				$row.find('input.lineItemType, input[name^="lineItemType"]').val('ProductsServices');
				inst.mapResultsToFields($row, data[0]);
				syncRowTaxPill($row, $form);
				syncRowAmounts($row, $form);
				triggerLineRecalc($row, $form);
				syncRowStockHint($row, $form);
				return;
			}
			$hiddenId.val(productId);
			$nameInput.val(fallbackName).attr('disabled', 'disabled');
			if ($listPrice.length) {
				$listPrice.val(fallbackPrice > 0 ? fallbackPrice : $listPrice.val());
			}
			triggerLineRecalc($row, $form);
			syncRowStockHint($row, $form);
		});
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
			syncRowAmounts($row, $form);
			syncTotalsDisplay($form);
		}, 80);
	}

	function isTemplateLineItemRow($row) {
		return (
			!$row ||
			!$row.length ||
			$row.hasClass('hide') ||
			$row.hasClass('lineItemCloneCopy') ||
			$row.is('#row0')
		);
	}

	function isProductDropdownHealthy($sel) {
		if (
			!$sel ||
			!$sel.length ||
			!$sel.hasClass('mk-inv-product-native') ||
			!$.contains(document.documentElement, $sel[0])
		) {
			return false;
		}
		if (typeof $.fn.select2 === 'function') {
			return !!$sel.data('select2') && $sel.siblings('.select2-container').filter(':visible').length > 0;
		}
		return !!$sel.data('mkCatalogReady');
	}

	function destroyProductSelect2($sel) {
		if (!$sel || !$sel.length) {
			return;
		}
		if ($sel.data('select2')) {
			try {
				$sel.select2('close');
				$sel.select2('destroy');
			} catch (ignore) { /* ignore */ }
		}
		$sel.siblings('.select2-container').remove();
		$sel.removeClass('select2-offscreen');
	}

	function escapeHtml(text) {
		return String(text == null ? '' : text)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function normalizeSearchText(text) {
		var s = String(text || '').toLowerCase();
		if (typeof s.normalize === 'function') {
			s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
		}
		return s.replace(/đ/g, 'd').replace(/\s+/g, ' ').trim();
	}

	function isAnyProductSelectOpen($scope) {
		var open = false;
		var $root = $scope && $scope.length ? $scope : $(document);
		$root.find('select.mk-inv-product-select').each(function () {
			var $sel = $(this);
			if (!$sel.data('select2')) {
				return true;
			}
			try {
				if ($sel.select2('opened')) {
					open = true;
					return false;
				}
			} catch (ignore) { /* ignore */ }
			return true;
		});
		return open;
	}

	function productSelectMatcher(term, text, option) {
		var q = normalizeSearchText(term);
		if (!q) {
			return true;
		}
		var hay = normalizeSearchText(text);
		if (hay.indexOf(q) >= 0) {
			return true;
		}
		if (option) {
			var name = normalizeSearchText($(option).attr('data-name') || '');
			var sku = normalizeSearchText($(option).attr('data-sku') || '');
			if (name.indexOf(q) >= 0 || sku.indexOf(q) >= 0) {
				return true;
			}
		}
		return false;
	}

	function formatProductSelectResult(item) {
		if (!item || !item.id) {
			return item && item.text ? escapeHtml(item.text) : '';
		}
		var $opt = $(item.element);
		var name = $opt.attr('data-name') || item.text || '';
		var sku = ($opt.attr('data-sku') || '').trim();
		var skuHtml = sku
			? '<span class="mk-inv-s2-sku">' + escapeHtml(sku) + '</span>'
			: '<span class="mk-inv-s2-sku mk-inv-s2-sku--empty">chưa có SKU</span>';
		return (
			'<span class="mk-inv-s2-result">' +
			'<span class="mk-inv-s2-name">' +
			escapeHtml(name) +
			'</span>' +
			skuHtml +
			'</span>'
		);
	}

	function formatProductSelectSelection(item) {
		if (!item || !item.id) {
			return item && item.text ? item.text : '';
		}
		var $opt = $(item.element);
		return $opt.attr('data-name') || item.text || '';
	}

	function initOrRefreshProductSelect2($sel) {
		if (!$sel || !$sel.length || typeof $.fn.select2 !== 'function') {
			return;
		}
		if ($sel.prop('disabled') || $sel.data('mkLoading')) {
			return;
		}
		// Already healthy — do NOT destroy/recreate (causes flicker / double UI).
		if ($sel.data('select2') && $sel.siblings('.select2-container').length) {
			$sel.siblings('.select2-container').removeClass('mk-inv-hide-legacy').css({ display: '', visibility: '' });
			$sel.addClass('select2-offscreen');
			return;
		}
		var currentVal = $sel.val() || '';
		destroyProductSelect2($sel);
		$sel.select2({
			placeholder: '— Tìm / chọn sản phẩm —',
			allowClear: true,
			width: '100%',
			dropdownCssClass: 'mk-inv-s2-drop mk-inv-s2-search',
			minimumResultsForSearch: 0,
			matcher: function (term, text, opt) {
				return productSelectMatcher(term, text, opt);
			},
			formatResult: formatProductSelectResult,
			formatSelection: formatProductSelectSelection,
			formatNoMatches: function () {
				return 'Không tìm thấy sản phẩm';
			},
			formatSearching: function () {
				return 'Đang tìm…';
			},
			escapeMarkup: function (m) {
				return m;
			}
		});
		if (currentVal) {
			$sel.select2('val', currentVal);
		} else {
			$sel.select2('val', '');
		}
		$sel.addClass('select2-offscreen');
		$sel
			.off('select2-open.mkInvS2 select2-close.mkInvS2')
			.on('select2-open.mkInvS2', function () {
				var $drop = $('.select2-drop.mk-inv-s2-drop.select2-drop-active');
				$drop.css('z-index', 2147483640);
				var $search = $drop.find('.select2-search input.select2-input');
				if ($search.length) {
					$search.attr('placeholder', 'Nhập tên hoặc SKU…');
					// Focus search immediately so typing feels instant.
					setTimeout(function () {
						$search.focus();
					}, 0);
				}
			});
	}

	function destroyProductDropdownInRow($row) {
		var $productTd = $row.find('input.productName').closest('td');
		if (!$productTd.length) {
			return;
		}
		var $sel = $productTd.find('.mk-inv-product-select');
		destroyProductSelect2($sel);
		$sel.remove();
		$productTd.find('.itemNameDiv .select2-container').remove();
	}

	function neutralizeLegacyProductInput($row) {
		var $nameInput = $row.find('input.productName').first();
		if (!$nameInput.length) {
			return;
		}
		if ($nameInput.data('ui-autocomplete')) {
			try {
				$nameInput.autocomplete('destroy');
			} catch (ignore) { /* ignore */ }
		}
		$nameInput.removeClass('autoComplete').off('.autocomplete');
	}

	function cleanupLegacyProductCell($row, $nameInput, $productTd) {
		neutralizeLegacyProductInput($row);
		$nameInput.removeClass('autoComplete').addClass('mk-inv-hide-legacy').attr({ type: 'hidden', tabindex: '-1' });
		$productTd.find('.lineItemCommentBox').closest('div').addClass('mk-inv-hide-legacy');
		$productTd.find('.itemNameDiv .col-lg-10 > .input-group').addClass('mk-inv-hide-legacy');
		$productTd.find('.itemNameDiv .col-lg-2').addClass('mk-inv-hide-legacy');
		// Hide only legacy Select2 widgets — never our product picker.
		$productTd
			.find('.itemNameDiv .select2-container')
			.not('.mk-inv-product-select + .select2-container')
			.addClass('mk-inv-hide-legacy');
	}

	function buildProductSelectOptions($sel, products) {
		// Empty label required for Select2 placeholder (avoids double "—" text flicker).
		$sel.empty().append('<option value=""></option>');
		(products || []).forEach(function (p) {
			var id = String(p.id || '');
			var displayName = decodeText(p.name || id);
			var label = displayName;
			if (p.sku) {
				label += ' (' + decodeText(p.sku) + ')';
			} else {
				label += ' (chưa có SKU)';
			}
			$sel.append(
				$('<option></option>')
					.attr('value', id)
					.attr('data-name', displayName)
					.attr('data-price', p.price || 0)
					.attr('data-sku', p.sku || '')
					.text(label)
			);
		});
	}

	function injectProductDropdown($row, $form) {
		if (isTemplateLineItemRow($row)) {
			return;
		}
		var $productTd = $row.find('input.productName').closest('td');
		if (!$productTd.length) {
			return;
		}
		var $existing = $productTd.find('.mk-inv-product-select').first();
		if ($existing.length && typeof $.fn.select2 === 'function' && $existing.data('select2')) {
			try {
				if ($existing.select2('opened')) {
					return;
				}
			} catch (ignore) { /* ignore */ }
		}
		if ($existing.length && isProductDropdownHealthy($existing)) {
			$existing.siblings('.select2-container').removeClass('mk-inv-hide-legacy').css({ display: '', visibility: '' });
			$existing.addClass('select2-offscreen');
			// Backfill options quietly without destroying Select2 UI.
			if (
				$existing.find('option').length <= 1 &&
				productCatalogCache &&
				productCatalogCache.length
			) {
				var keepVal = $existing.val();
				buildProductSelectOptions($existing, productCatalogCache);
				$existing.data('mkCatalogReady', true);
				if (keepVal) {
					$existing.val(keepVal);
					try {
						$existing.select2('val', keepVal);
					} catch (ignore2) { /* ignore */ }
				}
			}
			return;
		}
		if ($existing.length && $existing.data('mkLoading')) {
			return;
		}
		if ($existing.length) {
			destroyProductDropdownInRow($row);
		}
		var $nameInput = $row.find('input.productName');
		var $hiddenId = $row.find('input.selectedModuleId');

		var $sel = $(
			'<select class="mk-inv-product-select mk-inv-product-native" title="Hàng hoá" data-mk-inv-product="1"></select>'
		);
		$sel.prop('disabled', true);
		$sel.data('mkLoading', true);
		$sel.append('<option value="">Đang tải hàng hoá…</option>');

		cleanupLegacyProductCell($row, $nameInput, $productTd);
		$row.find('.lineItemPopup').addClass('mk-inv-hide-legacy');
		var $host = $productTd.find('.itemNameDiv .col-lg-10').first();
		if (!$host.length) {
			$host = $productTd.find('.itemNameDiv').first();
		}
		if (!$host.length) {
			$host = $productTd;
		}
		$host.prepend($sel);

		function applyCurrentSelection() {
			var currentId = ($hiddenId.val() || '').trim();
			if (!currentId) {
				return;
			}
			if (!$sel.find('option[value="' + currentId.replace(/"/g, '') + '"]').length) {
				var currentName = decodeText($nameInput.val() || currentId);
				$sel.append(
					$('<option></option>')
						.attr('value', currentId)
						.attr('data-name', currentName)
						.text(currentName)
				);
			}
			$sel.val(currentId);
		}

		if (productCatalogCache && productCatalogCache.length) {
			fillProductSelect($sel, productCatalogCache);
			applyCurrentSelection();
			initOrRefreshProductSelect2($sel);
		} else {
			loadProductCatalog().then(function (products) {
				if (!$sel.closest('tr').length) {
					return;
				}
				fillProductSelect($sel, products || []);
				applyCurrentSelection();
				initOrRefreshProductSelect2($sel);
			});
		}

		$sel.off('change.mkInvProduct').on('change.mkInvProduct', function () {
			applyProductSelection($row, $form, $(this).val());
		});
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
		// Inside money wrap (price/total cells): prefix "đ" is a sibling — only write the number.
		if ($el.closest('.mk-inv-money-wrap').length) {
			var numOnly = formatVndNumber(raw);
			if ($el.text() !== numOnly) {
				$el.text(numOnly);
			}
			return;
		}
		if ($el.hasClass('mk-inv-vnd-amount') || $el.closest('.mk-inv-totals-odoo').length) {
			var html =
				'<span class="mk-inv-vnd" aria-hidden="false">' +
				'<span class="mk-inv-vnd__cur">đ</span>' +
				'<span class="mk-inv-vnd__num">' +
				formatVndNumber(raw) +
				'</span>' +
				'</span>';
			if ($el.html() !== html) {
				$el.html(html);
			}
			return;
		}
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
		var pct = parseFloat($row.data('mkTaxPct')) || 0;
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
		$row.find('> td.mk-inv-col-tax').not(':last-child').remove();

		var $taxTd = $row.find('> td.mk-inv-col-net-slot').first();
		if (!$taxTd.length) {
			$taxTd = $row.find('> td').has('.netPrice').first();
		}
		if (!$taxTd.length) {
			$taxTd = $row.find('> td:last-child');
		}
		if (!$taxTd.length) {
			return;
		}
		$taxTd
			.addClass('mk-inv-col-tax')
			.removeClass('mk-inv-col-net-hide mk-inv-hide-legacy mk-inv-col-net-slot');
		$taxTd
			.find('.netPrice, span.netPrice, .individualTax, .taxDivContainer')
			.addClass('mk-inv-hide-legacy');

		if ($taxTd.find('.mk-inv-tax-select').length) {
			return;
		}
		$taxTd.find('.mk-inv-tax-pill').remove();

		var $sel = $('<select class="mk-inv-tax-select inputElement" title="Thuế"></select>');
		TAX_RATE_OPTIONS.forEach(function (opt) {
			$sel.append($('<option></option>').attr('value', opt.value).text(opt.label));
		});

		var currentPct = parseFloat($row.data('mkTaxPct')) || 0;
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
			$row.data('mkTaxPct', pct);
			$row.find('.taxPercentage').each(function () {
				$(this).val(pct);
			});
			var $groupTax = $form.find('.groupTaxPercentage').first();
			if ($groupTax.length) {
				$groupTax.val(pct);
			}
			// Avoid triggering Inventory core tax recalcs (they can overwrite our selection back to 0).
			setTimeout(function () {
				syncRowAmounts($row, $form);
				syncTotalsDisplay($form);
			}, 20);
			setTimeout(function () {
				$form.data('mkInvSyncingTotals', false);
				var fn = $form.data('mkScheduleRealtimeSync');
				if (fn) { fn(); }
			}, 150);
		});

		$taxTd.empty().append($sel);
	}

	function getRowTaxPercent($row, $form) {
		var pct = 0;
		var $sel = $row.find('.mk-inv-tax-select');
		if ($sel.length) {
			var val = $sel.val();
			pct = val === 'exempt' ? 0 : parseFloat(val) || 0;
		}
		if (!pct) {
			$row.find('.taxPercentage').each(function () {
				var v = parseFloat($(this).val());
				if (!isNaN(v) && v > 0) {
					pct = v;
					return false;
				}
			});
		}
		if (!pct && $form && $form.length) {
			pct = getPrimaryTaxPercent($form);
		}
		return pct;
	}

	function calcLineRowTotal($row, $form) {
		var qty = parseMoney($row.find('.qty').val());
		var price = parseMoney($row.find('.listPrice').val());
		var preTax = qty * price;
		var taxPct = getRowTaxPercent($row, $form);
		var taxAmt = Math.round((preTax * taxPct) / 100);
		return preTax + taxAmt;
	}

	function reorderTaxBeforePriceColumns($row) {
		if (!$row || !$row.length) {
			return;
		}
		var $priceTd = $row.find('> td.mk-inv-col-price').first();
		var $taxTd = $row.find('> td.mk-inv-col-tax').last();
		if (!$priceTd.length || !$taxTd.length || $priceTd[0] === $taxTd[0]) {
			return;
		}
		if ($taxTd.index() > $priceTd.index()) {
			$taxTd.insertBefore($priceTd);
		}
	}

	function reorderTaxBeforeAmountColumns($row) {
		reorderTaxBeforePriceColumns($row);
		if (!$row || !$row.length) {
			return;
		}
		var $amountTd = $row.find('> td.mk-inv-col-amount').first();
		var $taxTd = $row.find('> td.mk-inv-col-tax').last();
		if (!$amountTd.length || !$taxTd.length || $amountTd[0] === $taxTd[0]) {
			return;
		}
		if ($amountTd.index() < $taxTd.index()) {
			$taxTd.insertBefore($amountTd);
		}
	}

	function syncRowAmounts($row, $form) {
		var $total = $row.find('.productTotal');
		if (!$total.length) {
			return;
		}
		if (!$form || !$form.length) {
			$form = $row.closest('form');
		}
		var lineTotal = calcLineRowTotal($row, $form);
		writeAmountDisplay($total, lineTotal);
		$total.data('mkRawAmount', lineTotal);
		var $amountTd = $total.closest('td');
		$amountTd.addClass('mk-inv-col-amount');
		$amountTd.children().not('.productTotal, .mk-inv-money-wrap, .mk-inv-line-del, .mk-inv-amount-wrap').addClass('mk-inv-hide-legacy');
	}

	function syncAllRowAmounts($form) {
		if (!$form || !$form.length) {
			return;
		}
		$form.find('tr.lineItemRow').each(function () {
			syncRowAmounts($(this), $form);
		});
	}

	function headerLabelForCell($sampleRow, index) {
		var $cell = $sampleRow.children('td').eq(index);
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
			return 'Tên mục';
		}
		if ($cell.hasClass('mk-inv-col-unit')) {
			return 'Đơn vị tính';
		}
		if ($cell.find('input.qty, .qty').length) {
			return 'Số lượng';
		}
		if ($cell.find('input.listPrice').length) {
			return 'Bảng giá';
		}
		if ($cell.find('.productTotal').length) {
			return 'Tổng giá trị';
		}
		if ($cell.hasClass('mk-inv-col-tax') || $cell.find('.mk-inv-tax-select').length) {
			return 'Thuế';
		}
		return '';
	}

	function syncTaxHeaderLabel($table) {
		var $header = getLineItemHeaderRow($table);
		var $sample = getLineItemSampleRow($table);
		if (!$header.length || !$sample.length) {
			return;
		}

		$header.children('td').each(function () {
			var $td = $(this);
			var text = $.trim($td.text());
			if (/giảm|chiết khấu|net price/i.test(text)) {
				$td.html('<span class="mk-inv-th-label">Thuế</span>')
					.removeClass('mk-inv-col-net-hide mk-inv-hide-legacy')
					.addClass('mk-inv-col-tax-head mk-inv-col-tax');
			}
		});

		var lastIdx = -1;
		$sample.children('td').each(function (idx) {
			if ($(this).hasClass('mk-inv-col-tax') || $(this).find('.mk-inv-tax-select').length) {
				lastIdx = idx;
			}
		});
		if (lastIdx >= 0) {
			$header
				.children('td')
				.eq(lastIdx)
				.removeClass('mk-inv-col-net-hide mk-inv-hide-legacy mk-inv-col-amount')
				.addClass('mk-inv-col-tax-head mk-inv-col-tax')
				.html('<span class="mk-inv-th-label">Thuế</span>');
		}
	}

	function applyHeaderCellClasses($header, $sample) {
		$header.children('td').each(function (idx) {
			var $h = $(this);
			var $b = $sample.children('td').eq(idx);
			$h.removeClass(
				'mk-inv-col-product mk-inv-col-qty mk-inv-col-unit mk-inv-col-unit-head mk-inv-col-price mk-inv-col-amount mk-inv-col-tax mk-inv-col-tax-head mk-inv-col-drag'
			);
			if (idx === 0) {
				$h.addClass('mk-inv-col-drag');
				return;
			}
			if (!$b.length) {
				return;
			}
			if ($b.hasClass('mk-inv-col-unit') || $b.find('.mk-inv-unit-select').length) {
				$h.addClass('mk-inv-col-unit-head mk-inv-col-unit');
			}
			if ($b.hasClass('mk-inv-col-product') || $b.find('input.productName, select.mk-inv-product-native').length) {
				$h.addClass('mk-inv-col-product');
			}
			if ($b.hasClass('mk-inv-col-qty') || $b.find('input.qty, .qty').length) {
				$h.addClass('mk-inv-col-qty');
			}
			if ($b.hasClass('mk-inv-col-price') || $b.find('input.listPrice').length) {
				$h.addClass('mk-inv-col-price');
			}
			if ($b.hasClass('mk-inv-col-amount') || $b.find('.productTotal').length) {
				$h.addClass('mk-inv-col-amount');
			}
			if ($b.hasClass('mk-inv-col-tax') || $b.find('.mk-inv-tax-select').length) {
				$h.addClass('mk-inv-col-tax-head mk-inv-col-tax');
			}
		});
	}

	function applyModernLineItemColgroup($table) {
		if (!$table || !$table.length) {
			return;
		}
		$table.find('colgroup.mk-inv-colgroup').remove();
		var $colgroup = $('<colgroup class="mk-inv-colgroup"></colgroup>');
		MODERN_LINE_COLGROUP_WIDTHS.forEach(function (w) {
			$colgroup.append($('<col>').attr('style', 'width:' + w));
		});
		$table.prepend($colgroup);
	}

	function buildModernLineItemHeader($header) {
		if (!$header || !$header.length) {
			return false;
		}
		$header.empty().addClass('mk-inv-header-row');
		MODERN_LINE_HEADER_COLUMNS.forEach(function (spec) {
			var $td = $('<td></td>').addClass(spec.className);
			if (spec.label) {
				$td.html(renderHeaderLabelHtml(spec.label));
			}
			$header.append($td);
		});
		$header.data('mkOdooHeader', true);
		return true;
	}

	function applyLineItemColgroup($table) {
		if ($table.hasClass('mk-inv-luxury-lines') || $table.hasClass('mk-inv-odoo-lines-table')) {
			applyModernLineItemColgroup($table);
			return;
		}
		var $sample = getLineItemSampleRow($table);
		if (!$sample.length) {
			applyModernLineItemColgroup($table);
			return;
		}
		var widths = [];
		$sample.children('td').each(function (idx) {
			if (idx === 0) {
				widths.push('52px');
			} else if ($(this).hasClass('mk-inv-col-product') || $(this).find('select.mk-inv-product-native').length) {
				widths.push('24%');
			} else if ($(this).hasClass('mk-inv-col-qty')) {
				widths.push('92px');
			} else if ($(this).hasClass('mk-inv-col-unit')) {
				widths.push('132px');
			} else if ($(this).hasClass('mk-inv-col-price')) {
				widths.push('148px');
			} else if ($(this).hasClass('mk-inv-col-amount')) {
				widths.push('156px');
			} else if ($(this).hasClass('mk-inv-col-tax')) {
				widths.push('104px');
			} else {
				widths.push('auto');
			}
		});
		$table.find('colgroup.mk-inv-colgroup').remove();
		var $colgroup = $('<colgroup class="mk-inv-colgroup"></colgroup>');
		widths.forEach(function (w) {
			$colgroup.append($('<col>').attr('style', 'width:' + w));
		});
		$table.prepend($colgroup);
	}

	function renderHeaderLabelHtml(label) {
		if (!label || label === '__hide__') {
			return '';
		}
		var required = label === 'Tên mục' ? '<span class="mk-inv-required" aria-hidden="true">*</span>' : '';
		return '<span class="mk-inv-th-label">' + required + label + '</span>';
	}

	function rebuildLineItemHeaderRow($table, $form) {
		var $header = getLineItemHeaderRow($table);
		if (!$header.length) {
			return false;
		}
		buildModernLineItemHeader($header);
		applyLineItemColgroup($table);
		return true;
	}

	function ensureOdooHeaderColumns($table) {
		rebuildLineItemHeaderRow($table);
	}

	function ensureModernLineItemsTable($form) {
		var $table = $form.find('#lineItemTab');
		if (!$table.length) {
			return;
		}
		$table.addClass('mk-inv-odoo-lines-table mk-inv-luxury-lines');
		ensureOdooHeaderColumns($table);
		applyModernLineItemColgroup($table);
	}

	function wrapMoneyInput($input) {
		if (!$input || !$input.length || $input.closest('.mk-inv-money-wrap').length) {
			return;
		}
		$input.addClass('mk-inv-money-input');
		$input.wrap('<div class="mk-inv-money-wrap"></div>');
		var $wrap = $input.parent();
		$wrap.find('.mk-inv-money-suffix').remove();
		if (!$wrap.find('.mk-inv-money-prefix').length) {
			$input.before('<span class="mk-inv-money-prefix" aria-hidden="true">đ</span>');
		}
	}

	function enhanceMoneyCells($row) {
		wrapMoneyInput($row.find('input.listPrice').first());
		var $total = $row.find('.productTotal').first();
		if ($total.length && !$total.closest('.mk-inv-money-wrap').length) {
			var $wrap = $('<div class="mk-inv-money-wrap mk-inv-money-wrap--total"></div>');
			$total.wrap($wrap);
			$wrap = $total.parent();
			$wrap.find('.mk-inv-money-suffix').remove();
			if (!$wrap.find('.mk-inv-money-prefix').length) {
				$total.before('<span class="mk-inv-money-prefix" aria-hidden="true">đ</span>');
			}
		} else if ($total.length) {
			var $existing = $total.closest('.mk-inv-money-wrap');
			$existing.find('.mk-inv-money-suffix').remove();
			if (!$existing.find('.mk-inv-money-prefix').length) {
				$total.before('<span class="mk-inv-money-prefix" aria-hidden="true">đ</span>');
			}
		}
	}

	function injectUnitSelect($row, $unitTd) {
		if ($unitTd.find('.mk-inv-unit-select').length) {
			return;
		}
		var $sel = $('<select class="mk-inv-unit-select inputElement" title="Đơn vị tính"></select>');
		$sel.append($('<option></option>').attr('value', '').text('— ĐVT —'));
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

	function injectPriceColumn($row) {
		var $existing = $row.find('input.listPrice').first();
		if ($existing.length) {
			return $existing.closest('td');
		}
		var $amountTd = $row.find('.productTotal').closest('td');
		if (!$amountTd.length) {
			return $();
		}
		var rowNo = $row.find('.rowNumber').val() || $row.attr('data-row-num') || '';
		var $priceTd = $('<td class="mk-inv-col-price"></td>');
		var $input = $(
			'<input type="text" class="listPrice smallInputBox inputElement" data-rule-required="true" data-rule-positive="true" value="0" />'
		);
		if (rowNo !== '') {
			$input.attr('id', 'listPrice' + rowNo).attr('name', 'listPrice' + rowNo);
		}
		$priceTd.append($input);
		$priceTd.insertBefore($amountTd);
		return $priceTd;
	}

	function ensureModernPriceColumn($row) {
		var $priceTd = injectPriceColumn($row);
		if ($priceTd.length) {
			$priceTd.removeClass('mk-inv-hide-legacy mk-inv-col-net-hide');
		}
		return $priceTd;
	}

	function getRowNumberValue($row) {
		return String($row.find('.rowNumber').val() || $row.attr('data-row-num') || '').trim();
	}

	function paintPriceCell($row) {
		var $priceTd = $row.find('input.listPrice').closest('td').first();
		if (!$priceTd.length) {
			$priceTd = ensureModernPriceColumn($row);
		}
		if (!$priceTd.length) {
			return $();
		}
		$priceTd
			.addClass('mk-inv-col-price')
			.removeClass('mk-inv-hide-legacy mk-inv-col-net-hide')
			.css({ display: '', visibility: '', opacity: '' });

		var $input = $priceTd.find('input.listPrice').first();
		if (!$input.length) {
			var rowNo = getRowNumberValue($row);
			$input = $(
				'<input type="text" class="listPrice smallInputBox inputElement mk-inv-money-input" data-rule-required="true" data-rule-positive="true" value="0" />'
			);
			if (rowNo) {
				$input.attr('id', 'listPrice' + rowNo).attr('name', 'listPrice' + rowNo);
			}
			$priceTd.empty().append($input);
		}
		$input.removeClass('mk-inv-hide-legacy').css({ display: '', visibility: '', opacity: '' });
		$priceTd.find('.individualTaxContainer, .taxDivContainer, .individualDiscount').addClass('mk-inv-hide-legacy');
		return $priceTd;
	}

	function paintAmountCell($row, $form) {
		var $amountTd = $row.find('.productTotal').closest('td').first();
		if (!$amountTd.length) {
			var rowNo = getRowNumberValue($row);
			$amountTd = $('<td class="mk-inv-col-amount"></td>');
			var $total = $('<div class="productTotal">0</div>');
			if (rowNo) {
				$total.attr('id', 'productTotal' + rowNo);
			}
			$amountTd.append($total);
			var $taxTd = $row.find('.mk-inv-tax-select').closest('td').first();
			if ($taxTd.length) {
				$amountTd.insertAfter($taxTd);
			} else {
				$row.append($amountTd);
			}
		}
		$amountTd
			.addClass('mk-inv-col-amount')
			.removeClass('mk-inv-hide-legacy mk-inv-col-net-hide')
			.css({ display: '', visibility: '', opacity: '' });
		$amountTd
			.children()
			.not('.productTotal, .mk-inv-money-wrap, .mk-inv-line-del, .mk-inv-amount-wrap')
			.addClass('mk-inv-hide-legacy');

		var $total = $amountTd.find('.productTotal').first();
		if (!$total.length) {
			var rowNo = getRowNumberValue($row);
			$total = $('<div class="productTotal">0</div>');
			if (rowNo) {
				$total.attr('id', 'productTotal' + rowNo);
			}
			$amountTd.prepend($total);
		}
		$total.removeClass('mk-inv-hide-legacy').css({ display: '', visibility: '', opacity: '' });
		syncRowAmounts($row, $form);
		return $amountTd;
	}

	function normalizeModernLineItemRow($row, $form) {
		if (isTemplateLineItemRow($row)) {
			return;
		}

		var $drag = $row.children('td').first();
		var $product = $row.find('input.productName, select.mk-inv-product-native').closest('td').first();
		var $qty = $row.find('input.qty, .qty').closest('td').first();
		var $unit = $row.find('> td.mk-inv-col-unit').first();
		if (!$unit.length) {
			$unit = $row.find('.mk-inv-unit-select').closest('td').first();
		}
		var $tax = $row.find('.mk-inv-tax-select').closest('td').first();
		var $price = paintPriceCell($row);
		var $amount = paintAmountCell($row, $form);

		var ordered = [];
		[$drag, $product, $qty, $unit, $tax, $price, $amount].forEach(function ($td) {
			if (!$td || !$td.length) {
				return;
			}
			var el = $td[0];
			if (ordered.indexOf(el) === -1) {
				ordered.push(el);
			}
		});

		ordered.forEach(function (el) {
			$row.append(el);
		});

		$row.children('td').each(function () {
			var $td = $(this);
			if (ordered.indexOf(this) === -1) {
				$td.addClass('mk-inv-col-net-hide mk-inv-hide-legacy');
			}
		});

		tagLineItemColumnClasses($row);
		enhanceMoneyCells($row);
		syncRowAmounts($row, $form);
	}

	function tagLineItemColumnClasses($row) {
		$row.find('input.productName').closest('td').addClass('mk-inv-col-product');
		$row.find('input.qty, .qty').closest('td').addClass('mk-inv-col-qty');
		$row.find('input.listPrice').closest('td').addClass('mk-inv-col-price');
		$row.find('.productTotal').closest('td').addClass('mk-inv-col-amount');
	}

	function ensureOdooRowColumns($row, $form) {
		if (isTemplateLineItemRow($row) || $row.hasClass('mk-inv-section-row')) {
			return;
		}

		$row.removeData('mkTaxAmountReordered');

		// Remove legacy extra tax column between total and net.
		$row.find('> td.mk-inv-col-tax').not(':last-child').remove();

		var $qtyTd = $row.find('input.qty, .qty').first().closest('td');
		var $unitTd = $row.find('> td.mk-inv-col-unit').first();
		if ($qtyTd.length && !$unitTd.length) {
			$qtyTd.after('<td class="mk-inv-col-unit"></td>');
			$unitTd = $row.find('> td.mk-inv-col-unit').first();
		}
		if ($unitTd.length) {
			$unitTd.removeClass('mk-inv-hide-legacy');
			injectUnitSelect($row, $unitTd);
		}

		ensureModernPriceColumn($row);

		injectTaxDropdown($row, $form);
		syncRowTaxPill($row, $form);
		syncProductDesc($row);
		normalizeModernLineItemRow($row, $form);
		injectProductDropdown($row, $form);

		var $tools = $row.find('> td:first-child');
		$tools.addClass('mk-inv-col-drag');
		$tools.find('img[src*="drag"]').closest('a, span').removeClass('mk-inv-hide-legacy');

		var $amountTd = $row.find('.productTotal').closest('td');
		var $del = $tools.find('.deleteRow').first();
		$amountTd.find('.mk-inv-line-del').remove();
		if ($del.length) {
			$del.removeClass('mk-inv-hide-legacy');
			if (!$del.closest('.mk-inv-del-btn').length) {
				$del.wrap('<span class="mk-inv-del-btn" title="Xóa dòng"></span>');
			}
		}

		$row.find('input.productName').attr('placeholder', 'Chọn sản phẩm từ dropdown');
		$row.find('.priceBookPopup').addClass('mk-inv-hide-legacy');
		$row.find('.itemNameDiv .lineItemPopup').addClass('mk-inv-hide-legacy');
		syncRowStockHint($row, $form);
	}

	function refreshLineItemRow($row, $form) {
		if (!$row || !$row.length || isTemplateLineItemRow($row)) {
			return;
		}
		neutralizeLegacyProductInput($row);
		ensureOdooRowColumns($row, $form);
	}

	function markInventoryUiReady() {
		if (typeof document === 'undefined' || !document.documentElement) {
			return;
		}
		var root = document.documentElement;
		if (root.classList.contains('mk-inv-ui-ready')) {
			return;
		}
		root.classList.add('mk-inv-ui-ready');
		root.classList.add('mk-quote-create-enhanced');
		root.classList.add('mk-so-create-styled');
	}

	function restyleLineItemRows($form) {
		if (!$form || !$form.length) {
			return;
		}
		// Never restyle while user is searching/selecting a product — prevents stutter.
		if (isAnyProductSelectOpen($form) || isAnyProductSelectOpen($(document.body))) {
			return;
		}
		var $table = $form.find('#lineItemTab');
		if (!$table.length) {
			return;
		}
		$table.addClass('mk-inv-odoo-lines-table mk-inv-luxury-lines');
		$table.find('tr.lineItemRow').each(function () {
			refreshLineItemRow($(this), $form);
		});
		ensureOdooHeaderColumns($table);
		applyLineItemColgroup($table);
		initTotalsOdoo($form);
		syncTotalsDisplay($form);
		syncAllRowAmounts($form);
		markInventoryUiReady();
	}

	function scheduleLineItemsRestyle($form, delays) {
		if (!$form || !$form.length) {
			return;
		}
		if (!delays) {
			delays = [0, 120, 320, 650, 1100, 1800, 2800];
		}
		delays.forEach(function (ms) {
			setTimeout(function () {
				if ($form.closest('body').length && $form.find('#lineItemTab').length) {
					restyleLineItemRows($form);
				}
			}, ms);
		});
	}

	function bindInventoryRestyleHooks($form) {
		if (typeof app === 'undefined' || !app.event) {
			return;
		}

		registerPostEditViewRestyleHook();

		if ($form.data('mkInvRestyleHooks')) {
			return;
		}
		$form.data('mkInvRestyleHooks', true);

		app.event.on('post.lineItem.New', function (event, newLineItem) {
			var $targetForm = $form;
			if (!$targetForm.find('#lineItemTab').length) {
				$targetForm = detectInventoryEditForm();
			}
			if (!$targetForm.length || !$targetForm.find('#lineItemTab').length) {
				return;
			}
			if ($targetForm.data('mkInvSkipPostLineHook') || $targetForm.data('mkInvAddingLine')) {
				return;
			}
			handleNewLineItemRow($targetForm, newLineItem);
		});
	}

	function registerPostEditViewRestyleHook() {
		if (window.__mkInvRestyleHooksBound || typeof app === 'undefined' || !app.event) {
			return;
		}
		window.__mkInvRestyleHooksBound = true;
		app.event.on('post.editView.load', function (event, container) {
			var $targetForm = $(container).closest('form');
			if (!$targetForm.length) {
				$targetForm = $(container).find('form#EditView, form[name="EditView"]').first();
			}
			if (!$targetForm.length && $('#mkQtFormHost').length) {
				$targetForm = $('#mkQtFormHost').find('form#EditView, form[name="EditView"]').first();
			}
			if (!$targetForm.length && $('#mkSoFormHost').length) {
				$targetForm = $('#mkSoFormHost').find('form#EditView, form[name="EditView"]').first();
			}
			if ($targetForm.length && $targetForm.find('#lineItemTab').length) {
				scheduleLineItemsRestyle($targetForm, [0, 150, 450, 900]);
			}
		});
	}

	function detectInventoryEditForm() {
		var module = $('body').attr('data-module');
		if (module !== 'Quotes' && module !== 'SalesOrder') {
			return $();
		}
		var $form = $('#mkQtFormHost, #mkSoFormHost')
			.find('form#EditView, form[name="EditView"]')
			.first();
		if (!$form.length) {
			$form = $('form#EditView.recordEditView, form[name="edit"].recordEditView').first();
		}
		if ($form.length && $form.find('#lineItemTab').length) {
			return $form;
		}
		return $();
	}

	function autoBootstrapInventoryOdooUi() {
		var $form = detectInventoryEditForm();
		if (!$form.length) {
			return;
		}
		if (!$form.hasClass('mk-inv-form-odoo')) {
			init($form, { hideDescriptionBlock: true });
		} else {
			scheduleLineItemsRestyle($form);
		}
	}

	$(function () {
		registerPostEditViewRestyleHook();
		autoBootstrapInventoryOdooUi();
		scheduleLineItemsRestyle(detectInventoryEditForm(), [80, 250, 600, 1200, 2000, 3200]);
		setTimeout(markInventoryUiReady, 2000);
		if (!window.__mkInvAddLineDocBound) {
			window.__mkInvAddLineDocBound = true;
			$(document).on('click.mkInvAddLine', '#addProductsServices', function (e) {
				var $btn = $(this);
				// Button-level handler owns creation once initialized.
				if ($btn.data('mkInvOdooAddBound')) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				var $f = detectInventoryEditForm();
				if (!$f.length) {
					$f = $btn.closest('form');
				}
				createInventoryLineItemRow($f, $btn);
			});
		}
	});

	function createInventoryLineItemRow($form, $btn) {
		if (!$form || !$form.length) {
			return null;
		}
		if ($form.data('mkInvAddingLine')) {
			return null;
		}
		$form.data('mkInvAddingLine', true);
		if ($btn && $btn.length) {
			$btn.addClass('is-busy').prop('disabled', true);
		}
		var newLineItem = null;
		try {
			var moduleName = $form.find('[name="module"]').val() || $('body').attr('data-module') || 'SalesOrder';
			var inst = null;
			if (typeof Inventory_Edit_Js !== 'undefined') {
				try {
					inst = Inventory_Edit_Js.getInstanceByModuleName(moduleName);
				} catch (ignore) {
					inst = null;
				}
			}
			if (!inst || typeof inst.getNewLineItem !== 'function') {
				var $legacy = $form.find('#addProduct').first();
				if ($legacy.length) {
					$legacy.trigger('click');
					setTimeout(function () {
						var $last = $form.find('#lineItemTab tr.lineItemRow').not('.hide, .lineItemCloneCopy').last();
						styleNewLineItemFast($form, $last);
						unlockAddLineButton($form, $btn);
					}, 40);
					return null;
				}
				unlockAddLineButton($form, $btn);
				return null;
			}
			if (!$btn || !$btn.length) {
				$btn = $form.find('#addProductsServices').first();
			}
			if (!$btn.attr('data-module-name')) {
				$btn.attr('data-module-name', 'ProductsServices');
			}
			newLineItem = inst.getNewLineItem({ currentTarget: $btn });
			if (!newLineItem || !newLineItem.length) {
				unlockAddLineButton($form, $btn);
				return null;
			}
			var $holder = inst.lineItemsHolder && inst.lineItemsHolder.length
				? inst.lineItemsHolder
				: $form.find('#lineItemTab');
			newLineItem.appendTo($holder);
			newLineItem.find('input.productName').addClass('autoComplete');
			newLineItem.find('.ignore-ui-registration').removeClass('ignore-ui-registration');

			// Style the new row immediately (snappy). Skip full-table restyle loops.
			styleNewLineItemFast($form, newLineItem);

			// Let other listeners know, but skip our own heavy post.lineItem.New handler.
			$form.data('mkInvSkipPostLineHook', true);
			if (typeof app !== 'undefined' && app.event) {
				app.event.trigger('post.lineItem.New', newLineItem);
			}
			$form.removeData('mkInvSkipPostLineHook');

			if (typeof inst.checkLineItemRow === 'function') {
				inst.checkLineItemRow();
			}
			// Do NOT registerLineItemAutoComplete — product picker is Select2; autocomplete adds lag.
		} catch (err) {
			if (window.console && console.warn) {
				console.warn('[MkInventoryOdooEdit] add line failed', err);
			}
		}
		unlockAddLineButton($form, $btn);
		return newLineItem;
	}

	function unlockAddLineButton($form, $btn) {
		setTimeout(function () {
			if ($form && $form.length) {
				$form.removeData('mkInvAddingLine');
			}
			if ($btn && $btn.length) {
				$btn.removeClass('is-busy').prop('disabled', false);
			}
		}, 120);
	}

	/** Fast path: only touch the new row + totals once (no multi-timeout full restyle). */
	function styleNewLineItemFast($form, newLineItem) {
		if (!$form || !$form.length) {
			return;
		}
		var $row = newLineItem && $(newLineItem).length
			? $(newLineItem)
			: $form.find('#lineItemTab tr.lineItemRow').not('.hide, .lineItemCloneCopy').last();
		if (!$row.length || isTemplateLineItemRow($row)) {
			return;
		}
		var $table = $form.find('#lineItemTab');
		$table.addClass('mk-inv-odoo-lines-table mk-inv-luxury-lines');
		refreshLineItemRow($row, $form);
		syncTotalsDisplay($form);
		// One short follow-up only if product Select2 did not mount yet.
		setTimeout(function () {
			if (!$row.closest('body').length) {
				return;
			}
			var $sel = $row.find('select.mk-inv-product-select').first();
			if (!$sel.length || !$sel.data('select2')) {
				refreshLineItemRow($row, $form);
			}
		}, 80);
	}

	function handleNewLineItemRow($form, newLineItem) {
		if (!$form || !$form.length) {
			return;
		}
		if ($form.data('mkInvSkipPostLineHook')) {
			return;
		}
		// Debounce overlapping handlers from Inventory + our add button.
		var token = ($form.data('mkInvNewLineToken') || 0) + 1;
		$form.data('mkInvNewLineToken', token);
		styleNewLineItemFast($form, newLineItem);
		setTimeout(function () {
			if ($form.data('mkInvNewLineToken') !== token) {
				return;
			}
			var $row = newLineItem && $(newLineItem).length
				? $(newLineItem)
				: $form.find('#lineItemTab tr.lineItemRow').not('.hide, .lineItemCloneCopy').last();
			if ($row.length && !$row.find('select.mk-inv-product-select').data('select2')) {
				refreshLineItemRow($row, $form);
			}
		}, 100);
	}

	function setFormattedText($el, formatted) {
		if ($el.length && $el.text() !== formatted) {
			$el.text(formatted);
		}
	}

	function ensureTaxTotalsRowVisible($result, taxPct) {
		if (!$result || !$result.length) {
			return;
		}
		var safePct = parseFloat(taxPct);
		if (isNaN(safePct) || safePct < 0) {
			safePct = 0;
		}
		var $taxRow = $result.find('#group_tax_row');
		if (!$taxRow.length) {
			return;
		}
		$taxRow
			.removeClass('mk-inv-totals-hide hide')
			.addClass('mk-inv-totals-row mk-inv-totals-row--tax');
		$taxRow.find('td:first').html('<div class="mk-inv-totals-label">Thuế GTGT ' + safePct + '%</div>');
		$taxRow.find('#tax_final').addClass('mk-inv-vnd-amount');
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

		ensureTaxTotalsRowVisible($result, taxPct);

		$form.data('mkInvSyncingTotals', false);
	}

	function watchTotalsAndLines($form) {
		if ($form.data('mkInvTotalsWatch')) {
			return;
		}
		$form.data('mkInvTotalsWatch', true);

		var $result = $form.find('#lineItemResult');
		var _mutTimer = null;
		['#preTaxTotal', '#tax_final', '#grandTotal'].forEach(function (sel) {
			var el = $result.find(sel)[0];
			if (!el || typeof MutationObserver === 'undefined') {
				return;
			}
			var obs = new MutationObserver(function () {
				if (_mutTimer) {
					clearTimeout(_mutTimer);
				}
				_mutTimer = setTimeout(function () {
					_mutTimer = null;
					syncTotalsDisplay($form);
					$form.find('tr.lineItemRow').each(function () {
						var $r = $(this);
						syncRowAmounts($r, $form);
						// Keep dropdown stable even if legacy DOM updates happen.
						syncRowTaxPill($r, $form);
					});
				}, 120);
			});
			obs.observe(el, { childList: true, characterData: true, subtree: true });
		});

		var _realtimeTimer = null;
		function scheduleRealtimeSync() {
			if (_realtimeTimer) { clearTimeout(_realtimeTimer); }
			_realtimeTimer = setTimeout(function () {
				_realtimeTimer = null;
				$form.data('mkInvSyncingTotals', true);
				var preTaxSum = 0;
				var taxSum = 0;
				$form.find('tr.lineItemRow').each(function () {
					var $r = $(this);
					var qty = parseMoney($r.find('.qty').val());
					var price = parseMoney($r.find('.listPrice').val());
					var preTax = qty * price;
					var taxPct = getRowTaxPercent($r, $form);
					var lineTax = Math.round((preTax * taxPct) / 100);
					var lineTotal = preTax + lineTax;
					preTaxSum += preTax;
					taxSum += lineTax;
					var $pt = $r.find('.productTotal');
					if ($pt.length) {
						$pt.data('mkRawAmount', lineTotal);
						writeAmountDisplay($pt, lineTotal);
					}
				});
				var grand = preTaxSum + taxSum;
				var $result = $form.find('#lineItemResult');
				if ($result.length) {
					writeAmountDisplay($result.find('#netTotal, .netTotal'), preTaxSum);
					$result.find('#subtotal, input[name="subtotal"]').val(preTaxSum);
					writeAmountDisplay($result.find('#preTaxTotal'), preTaxSum);
					$result.find('#pre_tax_total').val(preTaxSum);
					writeAmountDisplay($result.find('#tax_final'), taxSum);
					$form.find('.groupTaxTotal').first().val(taxSum);
					writeAmountDisplay($result.find('#grandTotal, .grandTotal'), grand);
					$form.find('#total, input[name="total"]').val(grand);
					var taxPct = getPrimaryTaxPercent($form);
					ensureTaxTotalsRowVisible($result, taxPct);
				}
				setTimeout(function () { $form.data('mkInvSyncingTotals', false); }, 50);
			}, 100);
		}

		$form.on('focusout.mkInvTot change.mkInvTot', '.qty, .listPrice, .taxPercentage, .groupTaxPercentage, .mk-inv-tax-select', function () {
			var $row = $(this).closest('tr.lineItemRow');
			if ($row.length) {
				syncRowAmounts($row, $form);
			}
			setTimeout(function () {
				restyleLineItemRows($form);
				syncTotalsDisplay($form);
			}, 60);
		});

		$form.on('input.mkInvTotRealtime keyup.mkInvTotRealtime change.mkInvTotRealtime', '.qty, .listPrice, .mk-inv-tax-select', function () {
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
		// Keep header actions (pinned add button); only remove legacy footer action bars.
		$form.find('.mk-inv-line-actions').not('.mk-inv-line-header-actions').remove();
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
		if (!$addBtn.length) {
			return;
		}
		if (!$addBtn.attr('data-module-name')) {
			$addBtn.attr('data-module-name', 'ProductsServices');
		}
		$addBtn.removeClass('btn btn-default').addClass('mk-inv-add-line-btn');
		if (!$addBtn.data('mkInvOdooAddStyled')) {
			$addBtn.data('mkInvOdooAddStyled', true);
			$addBtn.empty().append('<span class="mk-inv-add-line-btn__plus" aria-hidden="true">+</span> Thêm hàng hoá');
		}
		if (!$addBtn.data('mkInvOdooAddBound')) {
			$addBtn.data('mkInvOdooAddBound', true);
			// Replace legacy direct binds (may be missing after shell move) with reliable create.
			$addBtn.off('click');
			$addBtn.on('click.mkInvOdooAdd', function (e) {
				e.preventDefault();
				e.stopImmediatePropagation();
				createInventoryLineItemRow($form, $(this));
			});
		}
		pinAddLineButtonToTabs($form);
	}

	function pinAddLineButtonToTabs($form) {
		var $lineBlock = $form.find('.mk-inv-lineitems-odoo, #lineItemTab').first().closest('.fieldBlockContainer');
		if (!$lineBlock.length) {
			$lineBlock = $form.find('#lineItemTab').closest('.fieldBlockContainer');
		}
		var $tabs = $lineBlock.find('.mk-inv-odoo-tabs').first();
		var $addBtn = $form.find('#addProductsServices').first();
		if (!$tabs.length || !$addBtn.length) {
			return;
		}
		var $actions = $tabs.find('.mk-inv-line-header-actions').first();
		if (!$actions.length) {
			$actions = $('<div class="mk-inv-line-header-actions mk-qt-line-actions" aria-label="Thao tác dòng sản phẩm"></div>');
			$tabs.append($actions);
		}
		if (!$addBtn.closest('.mk-inv-line-header-actions').length) {
			$actions.append($addBtn.detach());
		}
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
			$sub.find('td:first').html('<div class="mk-inv-totals-label">Số tiền trước thuế</div>');
			$sub.find('#preTaxTotal, #netTotal, .netTotal').addClass('mk-inv-vnd-amount');
		}
		var $taxRow = $result.find('#group_tax_row');
		if ($taxRow.length) {
			$taxRow.removeClass('hide mk-inv-totals-hide').addClass('mk-inv-totals-row mk-inv-totals-row--tax');
			$taxRow.find('#tax_final').addClass('mk-inv-vnd-amount');
		}
		if ($grand.length) {
			$grand.removeClass('mk-inv-totals-hide').addClass('mk-inv-totals-row mk-inv-totals-row--grand');
			$grand.find('td:first').html('<div class="mk-inv-totals-label">Tổng cộng</div>');
			$grand.find('#grandTotal, .grandTotal').addClass('mk-inv-vnd-amount');
		}

		syncTotalsDisplay($form);
		watchTotalsAndLines($form);
	}

	function initLineItemsOdoo($form) {
		var $lineBlock = $form.find('#lineItemTab').closest('.fieldBlockContainer');
		if (!$lineBlock.length) {
			scheduleLineItemsRestyle($form);
			return;
		}

		if (!$lineBlock.data('mkInvLineOdoo')) {
			$lineBlock.data('mkInvLineOdoo', true).attr('data-block', 'LBL_ITEM_DETAILS').addClass('mk-inv-lineitems-odoo');

			$lineBlock.find('> .row').first().addClass('mk-inv-hide-legacy');
			$lineBlock.find('#region_id, #currency_id, #taxtype').closest('.row').addClass('mk-inv-hide-legacy');
			$lineBlock.find('.well').closest('.row').addClass('mk-inv-hide-legacy');
			$lineBlock.find('> .row > .col-sm-3 h4.fieldBlockHeader').addClass('mk-inv-hide-legacy');
			$lineBlock.find('> br').addClass('mk-inv-hide-legacy');

			initOdooTabs($lineBlock);
			initPaymentTerms($form);
			initAddLineButton($form);
			initLineActionLinks();
			polishLineItemsShell($form);
			ensureModernLineItemsTable($form);
			bindInventoryRestyleHooks($form);

			loadProductCatalog().always(function () {
				scheduleLineItemsRestyle($form, [0, 200, 500]);
			});

			$form.off('post.lineItem.New.mkInvOdoo').on('post.lineItem.New.mkInvOdoo', function (e, newLineItem) {
				handleNewLineItemRow($form, newLineItem);
			});
		}

		scheduleLineItemsRestyle($form);
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
		var $body = $('body');
		var moduleName = $body.attr('data-module');
		if ((moduleName === 'Quotes' || moduleName === 'SalesOrder') && $body.attr('data-app') !== 'SALES') {
			$body.attr('data-app', 'SALES');
		}
		initAddressOdoo($form);
		initLineItemsOdoo($form);
		if (options.hideDescriptionBlock !== false) {
			hideDescriptionBlock($form);
		}
	}

	window.MkInventoryOdooEdit = {
		init: init,
		autoBootstrap: autoBootstrapInventoryOdooUi,
		fillAddressFromPotential: fillAddressFromPotential,
		fillAddressFromAccount: fillAddressFromAccount,
		restyleLineItemRows: restyleLineItemRows,
		scheduleLineItemsRestyle: scheduleLineItemsRestyle,
		syncTotalsDisplay: syncTotalsDisplay,
		refreshTotals: function ($form) {
			initTotalsOdoo($form);
			syncTotalsDisplay($form);
		}
	};
})(jQuery);
