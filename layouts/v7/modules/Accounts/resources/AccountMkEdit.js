/**
 * Accounts Create/Edit (SALES) — dashboard shell + slim franchise create fields.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260729_ac_sc_link1';
	var scCustomersCache = null;
	var scCustomersLoading = null;

	var ORDER_DEFAULTS = {
		tb_order_min_free: '10000000',
		tb_order_min_ship: '10000000',
		tb_order_ship_fee: '50000',
		tb_order_min_pickup: '5000000'
	};

	var BLOCK_ICONS = {
		LBL_ACCOUNT_INFORMATION: 'fa-user',
		LBL_TB_FRANCHISE_CONTRACT: 'fa-file-text-o'
	};

	var BLOCK_TITLES = {
		LBL_ACCOUNT_INFORMATION: 'Thông tin Bên B',
		LBL_TB_FRANCHISE_CONTRACT: 'Hợp đồng nhượng quyền'
	};

	var KEEP_FIELDS = {
		accountname: true,
		phone: true,
		email1: true,
		assigned_user_id: true,
		tb_contract_no: true,
		tb_sign_date: true,
		tb_party_b_cccd: true,
		tb_party_b_cccd_date: true,
		tb_party_b_cccd_place: true,
		tb_party_b_permanent_addr: true,
		tb_store_address: true,
		tb_fee_franchise: true,
		tb_fee_marketing: true,
		tb_fee_consult: true,
		tb_fee_opening: true,
		tb_fee_deposit: true,
		tb_order_min_free: true,
		tb_order_min_ship: true,
		tb_order_ship_fee: true,
		tb_order_min_pickup: true
	};

	var FIELD_LABELS = {
		accountname: 'Họ tên Bên B',
		phone: 'Điện thoại',
		email1: 'Email',
		assigned_user_id: 'Phụ trách',
		tb_contract_no: 'Số hợp đồng',
		tb_sign_date: 'Ngày ký',
		tb_party_b_cccd: 'CCCD / CMND',
		tb_party_b_cccd_date: 'Ngày cấp',
		tb_party_b_cccd_place: 'Nơi cấp',
		tb_party_b_permanent_addr: 'Địa chỉ thường trú / liên hệ',
		tb_store_address: 'Địa chỉ cửa hàng',
		tb_fee_franchise: 'Phí nhượng quyền',
		tb_fee_marketing: 'Phí marketing thương hiệu',
		tb_fee_consult: 'Phí tư vấn / hỗ trợ vận hành',
		tb_fee_opening: 'Phí marketing khai trương',
		tb_fee_deposit: 'Tiền ký quỹ bảo đảm (Đợt 1)',
		tb_order_min_free: 'Đơn hàng tối thiểu miễn ship',
		tb_order_min_ship: 'Đơn hàng dưới mức (có ship)',
		tb_order_ship_fee: 'Phí ship nội thành',
		tb_order_min_pickup: 'Đơn hàng tự đến kho lấy'
	};

	var BLOCK_ROW_LAYOUT = {
		LBL_ACCOUNT_INFORMATION: [
			['accountname'],
			['phone', 'email1'],
			['assigned_user_id']
		],
		LBL_TB_FRANCHISE_CONTRACT: [
			['tb_contract_no', 'tb_sign_date'],
			['tb_party_b_cccd', 'tb_party_b_cccd_date'],
			['tb_party_b_cccd_place'],
			['tb_party_b_permanent_addr'],
			['tb_store_address'],
			['tb_fee_franchise', 'tb_fee_marketing'],
			['tb_fee_consult', 'tb_fee_opening'],
			['tb_fee_deposit'],
			['tb_order_min_free', 'tb_order_min_ship'],
			['tb_order_ship_fee', 'tb_order_min_pickup']
		]
	};

	var HIDE_BLOCKS = {
		LBL_CUSTOM_INFORMATION: true,
		LBL_ADDRESS_INFORMATION: true,
		LBL_DESCRIPTION_INFORMATION: true
	};

	function isScoped() {
		return (
			$('body').data('module') === 'Accounts' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'SALES' ||
				$('body').data('app') === 'SUPPORT' ||
				$('body').data('app') === 'MARKETING' ||
				!$('body').data('app')) &&
			$('#mkAcCreateWorkspace').length
		);
	}

	function markReady() {
		document.documentElement.classList.add('mk-ac-create-ready');
	}

	function revealPage() {
		requestAnimationFrame(function () {
			requestAnimationFrame(markReady);
		});
	}

	function $form() {
		return $('#mkAcFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function applyFieldDefaults() {
		var $f = $form();
		if (!$f.length || $.trim($f.find('input[name="record"]').val() || '')) {
			return;
		}
		Object.keys(ORDER_DEFAULTS).forEach(function (name) {
			var $el = $f.find('[name="' + name + '"]').first();
			if ($el.length && !$.trim($el.val())) {
				$el.val(ORDER_DEFAULTS[name]).trigger('change');
			}
		});
	}

	function hideLegacyChrome() {
		var $host = $('#mkAcFormHost');
		$host.find('#modnavigator, .editViewModNavigator, .module-nav').addClass('mk-ac-hide-legacy');
		$host.find('.editViewHeader').addClass('mk-ac-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-ac-form-footer');
		$host.find('.main-container').first().addClass('mk-ac-form-container');
	}

	function normalizeFieldName(name) {
		name = String(name || '').replace(/\[\]$/, '');
		if (/_display$/.test(name)) {
			name = name.replace(/_display$/, '');
		}
		return name;
	}

	function fieldNameFromCell($td) {
		var dataName = $td.attr('data-fieldname');
		if (dataName) {
			return normalizeFieldName(dataName);
		}

		var $field = $td.find('[data-fieldname]').first();
		if ($field.length) {
			return normalizeFieldName($field.attr('data-fieldname'));
		}

		var name = '';
		$td.find('[name]').each(function () {
			var raw = this.getAttribute('name') || '';
			if (!raw || /_display$/.test(raw)) {
				return;
			}
			name = normalizeFieldName(raw);
			return false;
		});

		if (!name) {
			name = normalizeFieldName(
				$td.attr('data-name') ||
					$td.find('[name]').first().attr('name') ||
					$td.find('input, select, textarea').first().attr('name') ||
					''
			);
		}

		return name;
	}

	function isFieldVisible($label, $value) {
		if (!$label || !$label.length || !$value || !$value.length) {
			return false;
		}
		return !$label.hasClass('mk-ac-hide-legacy') && !$value.hasClass('mk-ac-hide-legacy');
	}

	function hideFieldPair($label, $value) {
		if ($label && $label.length) {
			$label.addClass('mk-ac-hide-legacy').hide();
		}
		if ($value && $value.length) {
			$value.addClass('mk-ac-hide-legacy').hide();
		}
	}

	function setFieldLabel($label, text) {
		var required =
			$label.find('.redColor, .required, .red').length > 0 ||
			($label.text() || '').indexOf('*') >= 0;
		var star = required ? ' <span class="redColor">*</span>' : '';
		var $lbl = $label.find('label').first();
		if ($lbl.length) {
			$lbl.html(text + star);
			return;
		}
		$label.html('<label class="muted">' + text + star + '</label>');
	}

	function slimCreateFields() {
		var $f = $form();
		if (!$f.length) {
			return;
		}

		$f.find('.fieldBlockContainer[data-block]').each(function () {
			var $block = $(this);
			var blockKey = $block.attr('data-block') || '';

			if (HIDE_BLOCKS[blockKey]) {
				$block.addClass('mk-ac-hide-legacy').hide();
				return;
			}

			$block.find('td.fieldLabel').each(function () {
				var $label = $(this);
				var $value = $label.next('td.fieldValue');
				var name = fieldNameFromCell($value.length ? $value : $label);
				if (name && !KEEP_FIELDS[name]) {
					hideFieldPair($label, $value);
				}
			});
		});
	}

	function applyFieldLabels() {
		var $f = $form();
		$f.find('td.fieldValue').each(function () {
			var $value = $(this);
			var name = fieldNameFromCell($value);
			if (!FIELD_LABELS[name]) {
				return;
			}
			var $label = $value.prev('td.fieldLabel');
			if ($label.length) {
				setFieldLabel($label, FIELD_LABELS[name]);
			}
		});
	}

	function collectVisiblePairs($block) {
		var pairs = {};
		$block.find('td.fieldLabel').each(function () {
			var $label = $(this);
			var $value = $label.next('td.fieldValue');
			if (!isFieldVisible($label, $value)) {
				return;
			}
			var name = fieldNameFromCell($value);
			if (!name || !KEEP_FIELDS[name] || pairs[name]) {
				return;
			}
			pairs[name] = { name: name, label: $label, value: $value };
		});
		return pairs;
	}

	function reflowBlockLayout($block) {
		if ($block.data('mk-reflowed')) {
			return;
		}

		var blockKey = $block.attr('data-block') || '';
		var layout = BLOCK_ROW_LAYOUT[blockKey];
		if (!layout) {
			return;
		}

		var pairMap = collectVisiblePairs($block);
		var $tbody = $block.find('table > tbody').first();
		if (!$tbody.length) {
			return;
		}

		$tbody.empty();

		layout.forEach(function (rowFields) {
			var $tr = $('<tr>', { class: 'mk-ac-field-row' });
			var count = 0;

			rowFields.forEach(function (fname) {
				var pair = pairMap[fname];
				if (!pair) {
					return;
				}
				$tr.append(pair.label.show()).append(pair.value.show());
				count += 1;
			});

		if (!count) {
			return;
		}

		$tr.addClass(count > 2 ? 'mk-ac-row--triple' : count > 1 ? 'mk-ac-row--pair' : 'mk-ac-row--full');
		$tbody.append($tr);
	});

	if ($tbody.children('tr.mk-ac-field-row').length) {
		$block.data('mk-reflowed', 1);
	}
}

	function reflowAllBlocks() {
		var reflowed = false;
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-ac-hide-legacy') || !$block.is(':visible')) {
					return;
				}
				if (!$block.data('mk-reflowed')) {
					reflowBlockLayout($block);
				}
				if ($block.data('mk-reflowed')) {
					reflowed = true;
				}
			});
		return reflowed;
	}

	function destroyDatePickers($scope) {
		$scope.find('.dateField').each(function () {
			var $input = $(this);
			var $group = $input.closest('.input-group');
			$group.find('.input-group-addon').off('click');
			if ($input.data('datepicker')) {
				try {
					$input.datepicker('destroy');
				} catch (e) {
					try {
						$input.datepicker('remove');
					} catch (e2) {}
				}
			}
		});
	}

	function reinitDateFields() {
		if (typeof vtUtils === 'undefined' || typeof vtUtils.registerEventForDateFields !== 'function') {
			return;
		}
		var $host = $('#mkAcFormHost');
		if (!$host.length || !$host.find('.dateField').length) {
			return;
		}
		destroyDatePickers($host);
		vtUtils.registerEventForDateFields($host.find('.dateField').not('.ignore-ui-registration'));
	}

	function bindDateFieldHelpers() {
		var $host = $('#mkAcFormHost');
		$host
			.off('click.mkAcDate', '.input-group-addon')
			.on('click.mkAcDate', '.input-group-addon', function (e) {
				var $group = $(this).closest('.input-group');
				var $input = $group.find('.dateField').first();
				if (!$input.length) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				$input.trigger('focus');
				if ($input.data('datepicker')) {
					try {
						$input.datepicker('show');
					} catch (err) {}
				}
			});
	}

	function maybeReinitDateFields() {
		var $f = $form();
		if ($f.data('mk-dates-reinited') || !$f.find('.mk-ac-field-row').length) {
			return;
		}
		reinitDateFields();
		bindDateFieldHelpers();
		$f.data('mk-dates-reinited', 1);
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-ac-hide-legacy')) {
					return;
				}

				var blockKey = $block.attr('data-block') || '';
				$block.addClass('mk-ac-block');

				var $header = $block.find('.fieldBlockHeader').first();
				$header.addClass('mk-ac-block__header');

				if (BLOCK_TITLES[blockKey]) {
					var $title = $header.find('.mk-ac-block__title-text');
					if (!$title.length) {
						$header.children().not('.mk-ac-block__icon').remove();
						$title = $('<span>', { class: 'mk-ac-block__title-text', text: BLOCK_TITLES[blockKey] });
						$header.append($title);
					} else {
						$title.text(BLOCK_TITLES[blockKey]);
					}
				}

				if (!$header.find('.mk-ac-block__icon').length && BLOCK_ICONS[blockKey]) {
					$header.prepend(
						$('<span>', { class: 'mk-ac-block__icon', 'aria-hidden': 'true' }).append(
							$('<i>', { class: 'fa ' + BLOCK_ICONS[blockKey] })
						)
					);
				}

				if (blockKey === 'LBL_TB_FRANCHISE_CONTRACT' && !$block.find('.mk-ac-franchise-hint').length) {
					$header.after(
						$('<p>', {
							class: 'mk-ac-franchise-hint',
								text: 'Đợt 1 / tiền ký quỹ = ô Tiền ký quỹ; Đợt 2 = phí nhượng quyền; Đợt 3 = phần còn lại — tự điền khi in PDF.'
							})
					);
				}

				$block.find('> hr').addClass('mk-ac-hide-legacy');
				$block.find('table.table-borderless, table.table').addClass('mk-ac-fields-table');
			});

		var $franchise = $form().find('.fieldBlockContainer[data-block="LBL_TB_FRANCHISE_CONTRACT"]').first();
		var $account = $form().find('.fieldBlockContainer[data-block="LBL_ACCOUNT_INFORMATION"]').first();
		if ($franchise.length && $account.length && !$franchise.data('mk-moved')) {
			$franchise.insertAfter($account);
			$franchise.data('mk-moved', 1);
		}
	}

	function fieldByName(name) {
		return $form().find('[name="' + name + '"]').filter(':input').first();
	}

	function digitsOnly(value) {
		return String(value || '').replace(/\D/g, '');
	}

	function showFieldError($input, message) {
		var $cell = $input.closest('td.fieldValue');
		$cell.find('.mk-ac-field-error').remove();
		$input.addClass('mk-ac-input-invalid');
		$cell.append($('<div>', { class: 'mk-ac-field-error', text: message }));
	}

	function clearFieldError($input) {
		$input.removeClass('mk-ac-input-invalid');
		$input.closest('td.fieldValue').find('.mk-ac-field-error').remove();
	}

	function bindDigitFieldLimits() {
		var $phone = fieldByName('phone');
		var $cccd = fieldByName('tb_party_b_cccd');

		if ($phone.length && !$phone.data('mk-digit-bound')) {
			$phone.attr({ maxlength: 10, inputmode: 'numeric', pattern: '[0-9]*', autocomplete: 'tel' });
			$phone
				.off('input.mkAcPhone keypress.mkAcPhone')
				.on('input.mkAcPhone', function () {
					var next = digitsOnly(this.value).slice(0, 10);
					if (this.value !== next) {
						this.value = next;
					}
					clearFieldError($phone);
				})
				.on('keypress.mkAcPhone', function (e) {
					if (e.ctrlKey || e.metaKey || e.which < 32) {
						return;
					}
					if (!/[0-9]/.test(String.fromCharCode(e.which))) {
						e.preventDefault();
					}
				});
			$phone.data('mk-digit-bound', 1);
		}

		if ($cccd.length && !$cccd.data('mk-digit-bound')) {
			$cccd.attr({ maxlength: 12, inputmode: 'numeric', pattern: '[0-9]*', autocomplete: 'off' });
			$cccd
				.off('input.mkAcCccd keypress.mkAcCccd')
				.on('input.mkAcCccd', function () {
					var next = digitsOnly(this.value).slice(0, 12);
					if (this.value !== next) {
						this.value = next;
					}
					clearFieldError($cccd);
				})
				.on('keypress.mkAcCccd', function (e) {
					if (e.ctrlKey || e.metaKey || e.which < 32) {
						return;
					}
					if (!/[0-9]/.test(String.fromCharCode(e.which))) {
						e.preventDefault();
					}
				});
			$cccd.data('mk-digit-bound', 1);
		}
	}

	function validatePartyFields() {
		var $phone = fieldByName('phone');
		var $cccd = fieldByName('tb_party_b_cccd');
		var ok = true;

		if ($phone.length) {
			clearFieldError($phone);
			var phoneDigits = digitsOnly($phone.val());
			$phone.val(phoneDigits);
			if (phoneDigits.length > 10) {
				showFieldError($phone, 'Số điện thoại tối đa 10 số.');
				ok = false;
			}
		}

		if ($cccd.length) {
			clearFieldError($cccd);
			var cccdDigits = digitsOnly($cccd.val());
			$cccd.val(cccdDigits);
			if (cccdDigits.length !== 12) {
				showFieldError($cccd, 'CCCD phải đủ đúng 12 số (không dư, không thiếu).');
				ok = false;
			}
		}

		if (!ok) {
			var $first = $form().find('.mk-ac-input-invalid').first();
			if ($first.length) {
				$('html, body').animate({ scrollTop: Math.max(0, $first.offset().top - 120) }, 200);
				$first.trigger('focus');
			}
		}
		return ok;
	}

	function triggerSave() {
		if (!validatePartyFields()) {
			return;
		}
		var $save = $form().find('.saveButton').first();
		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$form().trigger('submit');
	}

	function escapeHtml(s) {
		return String(s || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function setInputValue(name, value) {
		var $el = fieldByName(name);
		if (!$el.length) {
			return;
		}
		$el.val(value == null ? '' : String(value)).trigger('change');
		clearFieldError($el);
	}

	function applyScCustomer(c) {
		if (!c) {
			return;
		}
		var name = String(c.name || c.full_name || '').trim();
		var phone = digitsOnly(c.phone).slice(0, 10);
		var email = String(c.email || '').trim();
		var note = String(c.business_note || c.address || '').trim();
		if (name) {
			setInputValue('accountname', name);
			setInputValue('tb_party_b_name', name);
		}
		if (phone) {
			setInputValue('phone', phone);
			setInputValue('tb_party_b_phone', phone);
		}
		if (email) {
			setInputValue('email1', email);
			setInputValue('tb_party_b_email', email);
		}
		if (note) {
			setInputValue('tb_party_b_permanent_addr', note);
			setInputValue('tb_party_b_contact_addr', note);
		}
		var scId = String(c.crmid || c.id || '').trim();
		setInputValue('tb_sc_customer_id', scId);
		var $hint = $('#mkAcScCustomerHint');
		if ($hint.length) {
			var bits = [];
			if (c.affiliate_code) {
				bits.push(c.affiliate_code);
			}
			if (phone) {
				bits.push(phone);
			}
			$hint.text(bits.length ? bits.join(' · ') : '').prop('hidden', !bits.length);
		}
	}

	function fillScCustomerSelect(list) {
		var $sel = $('#mkAcScCustomerPick');
		if (!$sel.length) {
			return;
		}
		var current = String(fieldByName('tb_sc_customer_id').val() || $sel.val() || '').trim();
		var html = '<option value="">— Chọn khách hàng nhượng quyền —</option>';
		(list || []).forEach(function (c) {
			var id = String(c.crmid || c.id || '');
			if (!id) {
				return;
			}
			var label =
				(c.affiliate_code ? c.affiliate_code + ' — ' : '') +
				(c.name || '#' + id) +
				(c.phone ? ' (' + c.phone + ')' : '');
			html +=
				'<option value="' +
				escapeHtml(id) +
				'"' +
				(id === current ? ' selected' : '') +
				'>' +
				escapeHtml(label) +
				'</option>';
		});
		$sel.html(html);
		if (current) {
			$sel.val(current);
		}
	}

	function loadScCustomers() {
		if (scCustomersCache) {
			return $.Deferred().resolve(scCustomersCache).promise();
		}
		if (scCustomersLoading) {
			return scCustomersLoading;
		}
		var deferred = $.Deferred();
		scCustomersLoading = deferred.promise();
		var postData = {
			module: 'ServiceContracts',
			action: 'ModernApi',
			mode: 'list'
		};
		function done(err, res) {
			scCustomersLoading = null;
			if (err || !res || res.success === false) {
				deferred.reject(err || res);
				return;
			}
			scCustomersCache = Array.isArray(res.contracts) ? res.contracts : [];
			deferred.resolve(scCustomersCache);
		}
		if (window.app && app.request && app.request.post) {
			app.request.post({ data: postData }).then(done);
		} else {
			$.ajax({ url: 'index.php', type: 'POST', dataType: 'json', data: postData })
				.done(function (r) {
					done(null, r && r.result ? r.result : r);
				})
				.fail(function () {
					done({ message: 'Không tải được danh sách khách.' }, null);
				});
		}
		return scCustomersLoading;
	}

	function mountScCustomerPicker() {
		var $block = $form().find('.fieldBlockContainer[data-block="LBL_ACCOUNT_INFORMATION"]').first();
		if (!$block.length) return;
		var $tbody = $block.find('table > tbody').first();
		if (!$tbody.length) return;
		if ($('#mkAcScSearchWrap').length) return;

		var $row = $(
			'<tr class="mk-ac-field-row mk-ac-sc-pick-row">' +
				'<td class="fieldLabel alignMiddle" colspan="1">' +
				'<label class="muted">Khách hàng nhượng quyền</label>' +
				'</td>' +
				'<td class="fieldValue" colspan="3">' +
				'<div id="mkAcScSearchWrap" class="mk-ac-sc-search-wrap">' +
				'<div class="mk-ac-sc-select-box" id="mkAcScSelectBox">' +
				'<span class="mk-ac-sc-select-label" id="mkAcScSelectLabel">— Chọn khách hàng nhượng quyền —</span>' +
				'<span class="mk-ac-sc-select-arrow">&#9662;</span>' +
				'</div>' +
				'<input type="hidden" id="mkAcScCustomerPick" name="tb_sc_customer_id_pick" value="" />' +
				'<div id="mkAcScDropPanel" class="mk-ac-sc-drop-panel" hidden>' +
				'<input type="text" id="mkAcScSearchInput" class="mk-ac-sc-search-input" placeholder="Tìm theo tên, SĐT, mã AFF…" autocomplete="off" />' +
				'<div id="mkAcScSearchResults" class="mk-ac-sc-search-results"></div>' +
				'</div>' +
				'</div>' +
				'<div class="mk-ac-sc-pick-hint" id="mkAcScCustomerHint" hidden></div>' +
				'</td>' +
				'</tr>'
		);
		$tbody.prepend($row);

		var $selectBox = $('#mkAcScSelectBox');
		var $label = $('#mkAcScSelectLabel');
		var $panel = $('#mkAcScDropPanel');
		var $input = $('#mkAcScSearchInput');
		var $results = $('#mkAcScSearchResults');
		var $hidden = $('#mkAcScCustomerPick');

		function buildOptionHtml(c) {
			var id = String(c.crmid || c.id || '');
			var label =
				(c.affiliate_code ? '<strong>' + escapeHtml(c.affiliate_code) + '</strong> — ' : '') +
				escapeHtml(c.name || c.full_name || '#' + id) +
				(c.phone ? ' <span class="mk-ac-sc-search-phone">(' + escapeHtml(c.phone) + ')</span>' : '');
			return '<div class="mk-ac-sc-search-item" data-id="' + escapeHtml(id) + '" tabindex="0">' + label + '</div>';
		}

		function renderList(query) {
			var q = String(query || '').trim().toLowerCase();
			var list = (scCustomersCache || []);
			if (q) {
				list = list.filter(function (c) {
					var haystack = [c.name, c.full_name, c.phone, c.affiliate_code, c.email, c.business_note].join(' ').toLowerCase();
					return haystack.indexOf(q) >= 0;
				});
			}
			if (!list.length) {
				$results.html('<div class="mk-ac-sc-search-empty">Không tìm thấy</div>');
				return;
			}
			$results.html(list.map(buildOptionHtml).join(''));
		}

		function openPanel() {
			$panel.prop('hidden', false);
			$input.val('').focus();
			renderList('');
		}

		function closePanel() {
			$panel.prop('hidden', true);
		}

		$selectBox.on('click.mkAcScToggle', function () {
			if ($panel.prop('hidden')) {
				openPanel();
			} else {
				closePanel();
			}
		});

		$input.on('input.mkAcScSearch', function () {
			renderList(this.value);
		});

		$(document).on('click.mkAcScSearchOut', function (e) {
			if (!$(e.target).closest('#mkAcScSearchWrap').length) {
				closePanel();
			}
		});

		$results.on('click', '.mk-ac-sc-search-item', function () {
			var id = $(this).attr('data-id');
			$hidden.val(id);
			setInputValue('tb_sc_customer_id', id);
			var match = null;
			(scCustomersCache || []).forEach(function (c) {
				if (String(c.crmid || c.id) === id) match = c;
			});
			if (match) {
				var displayText =
					(match.affiliate_code ? match.affiliate_code + ' — ' : '') +
					(match.name || match.full_name || '');
				$label.text(displayText).addClass('mk-ac-sc-select-label--chosen');
				applyScCustomer(match);
			}
			closePanel();
		});

		$results.on('keydown', '.mk-ac-sc-search-item', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				$(this).trigger('click');
			}
		});

		loadScCustomers().then(function () {
			var currentId = String(fieldByName('tb_sc_customer_id').val() || '').trim();
			if (currentId) {
				(scCustomersCache || []).forEach(function (c) {
					if (String(c.crmid || c.id) === currentId) {
						$label.text(
							(c.affiliate_code ? c.affiliate_code + ' — ' : '') +
							(c.name || c.full_name || '')
						).addClass('mk-ac-sc-select-label--chosen');
					}
				});
			}
		});
	}

	function bindActions() {
		$('#mkAcSaveTop')
			.off('click.mkAcSave')
			.on('click.mkAcSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$form()
			.off('click.mkAcSaveBtn', '.saveButton')
			.on('click.mkAcSaveBtn', '.saveButton', function (e) {
				if (!validatePartyFields()) {
					e.preventDefault();
					e.stopImmediatePropagation();
					return false;
				}
			});

		$form()
			.off('submit.mkAcValidate')
			.on('submit.mkAcValidate', function (e) {
				if (!validatePartyFields()) {
					e.preventDefault();
					e.stopImmediatePropagation();
					return false;
				}
			});

		$(document)
			.off('keydown.mkAcCreate')
			.on('keydown.mkAcCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkAcFormHost').length) {
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
		slimCreateFields();
		applyFieldLabels();
		styleFieldBlocks();
		reflowAllBlocks();
		mountScCustomerPicker();
		applyFieldDefaults();
		bindDigitFieldLimits();
		maybeReinitDateFields();
		bindActions();
		markReady();
	}

	function init() {
		if (!isScoped()) {
			return;
		}
		runEnhancements();
		revealPage();
		setTimeout(runEnhancements, 120);
		setTimeout(runEnhancements, 500);

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

	window.__mkAcCreateBuild = MK_BUILD;
})($);
