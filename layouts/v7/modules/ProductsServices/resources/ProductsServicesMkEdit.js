/**
 * ProductsServices Create (SALES / INVENTORY) — dashboard shell + stock vtiger form.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260808_ps_group_pick1';
	var UNIT_PRESETS = [
		'Cái',
		'Bịch',
		'Bao',
		'Lon',
		'Hộp',
		'Hũ',
		'Gói',
		'Can',
		'Thùng',
		'Bình',
		'Chai',
		'Cuộn',
		'Con',
		'Kg',
		'Bộ',
		'Lít'
	];
	var UNIT_STORAGE_KEY = 'mk_ps_custom_units_v1';
	var GROUP_STORAGE_KEY = 'mk_ps_custom_groups_v1';
	/* Only fields previously agreed to remove — keep brand/model/stock/images. */
	var HIDE_FIELD_NAMES = [
		'warranty',
		'related_projects',
		'retail_price',
		'bulk_price',
		'price',
		'wholesale_price'
	];
	var FULL_WIDTH_FIELDS = {
		specification: true,
		description: true,
		comment: true,
		used_projects: true
	};

	function isScoped() {
		return (
			$('body').data('module') === 'ProductsServices' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'INVENTORY' || $('body').data('app') === 'SALES' || !$('body').data('app')) &&
			$('#mkPsCreateWorkspace').length
		);
	}

	function $form() {
		return $('#mkPsFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function hideClutterFieldsAndBlocks() {
		var $f = $form();
		if (!$f.length) return;

		HIDE_FIELD_NAMES.forEach(function (n) {
			$f.find('td.fieldValue[data-fieldname="' + n + '"]').closest('tr').addClass('mk-ps-hide-legacy');
			$f.find('[name="' + n + '"], [name="' + n + '[]"]').closest('tr').addClass('mk-ps-hide-legacy');
			$f.find('.mk-ps-compact-value[data-fieldname="' + n + '"]').closest('.mk-ps-compact-field').addClass('mk-ps-hide-legacy');
		});

		/* Keep image / project-history blocks visible for product photos. */
	}

	function packCompactTwoColumn() {
		var $f = $form();
		$f.find('.fieldBlockContainer.mk-ps-block').each(function () {
			var $block = $(this);
			if ($block.hasClass('mk-ps-hide-legacy')) {
				return;
			}
			// Re-pack if previous pack left an empty shell (AJAX re-inject or FOUC hide broken it).
			var $existingGrid = $block.find('> .mk-ps-compact-grid').first();
			var $table = $block.find('> table, table.table-borderless, table.detailview-table, table.table').first();
			if ($block.data('mkPsPacked') && $existingGrid.length && $existingGrid.find('.mk-ps-compact-field').length) {
				return;
			}
			if ($existingGrid.length && (!$table.length || !$table.find('td.fieldValue').length)) {
				return;
			}
			if ($existingGrid.length) {
				$existingGrid.remove();
				$block.removeData('mkPsPacked');
			}
			if (!$table.length) return;

			var pairs = [];
			$table.find('> tbody > tr, tbody > tr').each(function () {
				var $tr = $(this);
				// Never use :visible — create-ready CSS may hide tables during re-pack.
				if ($tr.hasClass('mk-ps-hide-legacy')) {
					return;
				}
				var $labels = $tr.children('td.fieldLabel');
				var $values = $tr.children('td.fieldValue');
				if (!$labels.length && !$values.length) {
					return;
				}
				$labels.each(function (idx) {
					var $lab = $(this);
					var $val = $values.eq(idx);
					if (!$val.length) return;
					if ($lab.hasClass('mk-ps-hide-legacy') || $val.hasClass('mk-ps-hide-legacy')) return;
					var fname = String($val.attr('data-fieldname') || $val.find('[name]').first().attr('name') || '')
						.replace(/\[\]$/, '')
						.trim();
					if (HIDE_FIELD_NAMES.indexOf(fname) >= 0) return;
					pairs.push({
						label: $lab,
						value: $val,
						full: !!FULL_WIDTH_FIELDS[fname] || !!$val.attr('colspan') || $val.hasClass('fieldValueWidth80')
					});
				});
			});

			if (!pairs.length) {
				return;
			}

			var $grid = $('<div class="mk-ps-compact-grid" role="group"></div>');
			pairs.forEach(function (pair) {
				var $cell = $('<div class="mk-ps-compact-field"></div>');
				if (pair.full) {
					$cell.addClass('mk-ps-compact-field--full');
				}
				var $labWrap = $('<div class="mk-ps-compact-label"></div>').append(pair.label.contents());
				var $valWrap = $('<div class="mk-ps-compact-value"></div>');
				var fname = pair.value.attr('data-fieldname');
				if (fname) {
					$valWrap.attr('data-fieldname', fname);
				}
				$valWrap.append(pair.value.contents());
				$cell.append($labWrap).append($valWrap);
				$grid.append($cell);
			});

			var $head = $block.find('.mk-ps-block__header, .fieldBlockHeader').first();
			if ($head.length) {
				$head.after($grid);
			} else {
				$block.prepend($grid);
			}
			$table.addClass('mk-ps-hide-legacy mk-ps-fields-packed').attr('aria-hidden', 'true').hide();
			$block.find('> hr').addClass('mk-ps-hide-legacy').hide();
			$block.data('mkPsPacked', true);
		});
	}

	function hideLegacyChrome() {
		var $host = $('#mkPsFormHost');
		$host.find('#modnavigator, .editViewModNavigator').remove();
		$host.find('.editViewHeader').addClass('mk-ps-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-ps-form-footer');
		$host.find('.main-container').first().addClass('mk-ps-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-ps-block')) {
					return;
				}
				$block.addClass('mk-ps-block');
				$block.find('.fieldBlockHeader').first().addClass('mk-ps-block__header');
				$block.find('> hr').addClass('mk-ps-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-ps-fields-table');
			});
	}

	function relabelTypeField() {
		var $typeLab = $form()
			.find('[data-fieldname="item_type"]')
			.closest('.mk-ps-compact-field, tr')
			.find('.mk-ps-compact-label, td.fieldLabel')
			.first();
		if ($typeLab.length) {
			var html = $typeLab.html() || '';
			html = html.replace(/>\s*Kiểu\s*</g, '>Loại<').replace(/>\s*Type\s*</g, '>Loại<');
			html = html.replace(/Kiểu/g, 'Loại').replace(/\bType\b/g, 'Loại');
			$typeLab.html(html);
		}
		var $unitLab = $form()
			.find('[data-fieldname="unit"]')
			.closest('.mk-ps-compact-field, tr')
			.find('.mk-ps-compact-label, td.fieldLabel')
			.first();
		if ($unitLab.length) {
			var uhtml = $unitLab.html() || '';
			uhtml = uhtml.replace(/>\s*Đơn vị\s*</g, '>Đơn vị tính<').replace(/>\s*Unit\s*</g, '>Đơn vị tính<');
			uhtml = uhtml.replace(/Đơn vị(?! tính)/g, 'Đơn vị tính').replace(/\bUnit\b/g, 'Đơn vị tính');
			$unitLab.html(uhtml);
		}
	}

	function hideNativePicklistUi($select) {
		if (!$select || !$select.length) {
			return;
		}
		var $cell = $select.closest('.mk-ps-compact-value, td.fieldValue');
		$cell.addClass('mk-ps-has-custom-control');

		try {
			if ($select.data('select2') || $select.hasClass('select2-offscreen') || $select.hasClass('select2-hidden-accessible')) {
				$select.select2('destroy');
			}
		} catch (e1) { /* ignore */ }

		$select
			.addClass('mk-ps-hide-legacy mk-ps-native-picklist')
			.hide()
			.attr('aria-hidden', 'true')
			.css({ position: 'absolute', left: '-9999px', width: '1px', height: '1px', opacity: 0 });

		$cell.find('.select2-container, [id^="s2id_"], .chzn-container, .chosen-container').each(function () {
			$(this).addClass('mk-ps-hide-legacy').hide().css('display', 'none');
		});
		var id = $select.attr('id');
		if (id) {
			$('#s2id_' + id).addClass('mk-ps-hide-legacy').hide().css('display', 'none');
		}
	}

	function loadCustomUnits() {
		try {
			var raw = window.localStorage.getItem(UNIT_STORAGE_KEY);
			var list = raw ? JSON.parse(raw) : [];
			return $.isArray(list) ? list.filter(Boolean) : [];
		} catch (e) {
			return [];
		}
	}

	function saveCustomUnits(list) {
		try {
			window.localStorage.setItem(UNIT_STORAGE_KEY, JSON.stringify(list || []));
		} catch (e) { /* ignore */ }
	}

	function canonicalUnit(raw) {
		var s = String(raw || '').trim().toLowerCase();
		var fold = s
			.replace(/[áàảãạăắằẳẵặâấầẩẫậ]/g, 'a')
			.replace(/[éèẻẽẹêếềểễệ]/g, 'e')
			.replace(/[íìỉĩị]/g, 'i')
			.replace(/[óòỏõọôốồổỗộơớờởỡợ]/g, 'o')
			.replace(/[úùủũụưứừửữự]/g, 'u')
			.replace(/[ýỳỷỹỵ]/g, 'y')
			.replace(/đ/g, 'd');
		var map = {
			pcs: 'Cái', piece: 'Cái', cai: 'Cái',
			box: 'Hộp', hop: 'Hộp',
			set: 'Bộ', bo: 'Bộ',
			kg: 'Kg',
			bich: 'Bịch', bao: 'Bao', lon: 'Lon', hu: 'Hũ', goi: 'Gói',
			can: 'Can', thung: 'Thùng', binh: 'Bình', chai: 'Chai',
			cuon: 'Cuộn', con: 'Con', lit: 'Lít', liter: 'Lít', litre: 'Lít'
		};
		if (map[fold]) return map[fold];
		for (var i = 0; i < UNIT_PRESETS.length; i++) {
			if (canonicalUnitKey(UNIT_PRESETS[i]) === fold) return UNIT_PRESETS[i];
		}
		return String(raw || '').trim();
	}

	function canonicalUnitKey(label) {
		return String(label || '').trim().toLowerCase()
			.replace(/[áàảãạăắằẳẵặâấầẩẫậ]/g, 'a')
			.replace(/[éèẻẽẹêếềểễệ]/g, 'e')
			.replace(/[íìỉĩị]/g, 'i')
			.replace(/[óòỏõọôốồổỗộơớờởỡợ]/g, 'o')
			.replace(/[úùủũụưứừửữự]/g, 'u')
			.replace(/[ýỳỷỹỵ]/g, 'y')
			.replace(/đ/g, 'd');
	}

	function allUnits() {
		return UNIT_PRESETS.slice();
	}

	function enhanceUnitField() {
		var $select = $form().find('select[name="unit"]').first();
		if (!$select.length) {
			return;
		}
		var $cell = $select.closest('.mk-ps-compact-value, td.fieldValue');
		if ($select.data('mkPsUnitReady') || $cell.find('.mk-ps-unit-box').length) {
			hideNativePicklistUi($select);
			return;
		}
		$select.data('mkPsUnitReady', true);

		var current = canonicalUnit($select.val() || '');

		var units = allUnits();
		if (current && units.indexOf(current) < 0) {
			units.push(current);
		}

		var $wrap = $(
			'<div class="mk-ps-unit-box">' +
				'<div class="mk-ps-unit-box__row">' +
					'<select class="mk-ps-unit-box__select inputElement" aria-label="Đơn vị tính"></select>' +
				'</div>' +
			'</div>'
		);
		var $uiSelect = $wrap.find('.mk-ps-unit-box__select');
		units.forEach(function (u) {
			$uiSelect.append($('<option></option>').attr('value', u).text(u));
		});
		if (current) {
			$uiSelect.val(current);
		} else if (units.length) {
			$uiSelect.val(units[0]);
			current = units[0];
		}

		function syncHidden(val) {
			val = String(val || '').trim();
			if (!val) return;
			var exists = false;
			$select.find('option').each(function () {
				if ($(this).attr('value') === val) exists = true;
			});
			if (!exists) {
				$select.append($('<option></option>').attr('value', val).text(val));
			}
			$select.val(val).trigger('change');
		}

		syncHidden($uiSelect.val());

		hideNativePicklistUi($select);
		var $host = $select.closest('.mk-ps-compact-value, td.fieldValue');
		$host.append($wrap);

		$uiSelect.on('change', function () {
			syncHidden(canonicalUnit($(this).val()));
		});
	}

	function loadCustomGroups() {
		try {
			var raw = window.localStorage.getItem(GROUP_STORAGE_KEY);
			var arr = raw ? JSON.parse(raw) : [];
			return Array.isArray(arr) ? arr.filter(Boolean) : [];
		} catch (e) {
			return [];
		}
	}

	function saveCustomGroups(list) {
		try {
			window.localStorage.setItem(GROUP_STORAGE_KEY, JSON.stringify(list || []));
		} catch (e) {
			/* ignore */
		}
	}

	function persistNewGroup(label, done) {
		var payload = {
			module: 'ProductsServices',
			action: 'AddProductGroup',
			group: label
		};
		function finish(ok) {
			if (typeof done === 'function') done(!!ok);
		}
		if (window.app && app.request && app.request.post) {
			app.request.post({ data: payload }).then(function (err, res) {
				finish(!err && res && res.success !== false);
			});
			return;
		}
		fetch('index.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: Object.keys(payload)
				.map(function (k) {
					return encodeURIComponent(k) + '=' + encodeURIComponent(payload[k]);
				})
				.join('&'),
			credentials: 'same-origin'
		})
			.then(function (r) {
				return r.json();
			})
			.then(function () {
				finish(true);
			})
			.catch(function () {
				finish(false);
			});
	}

	/**
	 * Nhóm: dropdown + nút "Thêm nhóm" (giống đơn vị tính).
	 */
	function enhanceGroupField() {
		var $field =
			$form().find('select[name="product_group"], input[name="product_group"]').first();
		if (!$field.length) {
			return;
		}
		var $cell = $field.closest('.mk-ps-compact-value, td.fieldValue');
		if ($field.data('mkPsGroupReady') || $cell.find('.mk-ps-group-box').length) {
			if ($field.is('select')) {
				hideNativePicklistUi($field);
			} else {
				$field.addClass('mk-ps-hide-legacy').hide();
			}
			return;
		}
		$field.data('mkPsGroupReady', true);

		var current = String($field.val() || '').trim();
		var groups = [];
		if ($field.is('select')) {
			$field.find('option').each(function () {
				var v = String($(this).attr('value') || '').trim();
				if (v && groups.indexOf(v) < 0) groups.push(v);
			});
		}
		loadCustomGroups().forEach(function (g) {
			if (g && groups.indexOf(g) < 0) groups.push(g);
		});
		if (current && groups.indexOf(current) < 0) {
			groups.push(current);
		}
		groups.sort(function (a, b) {
			return String(a).localeCompare(String(b), 'vi');
		});

		var $wrap = $(
			'<div class="mk-ps-unit-box mk-ps-group-box">' +
				'<div class="mk-ps-unit-box__row">' +
					'<select class="mk-ps-unit-box__select mk-ps-group-box__select inputElement" aria-label="Nhóm"></select>' +
					'<button type="button" class="mk-ps-unit-box__add mk-ps-group-box__add" title="Thêm nhóm">Thêm nhóm</button>' +
				'</div>' +
			'</div>'
		);
		var $uiSelect = $wrap.find('.mk-ps-group-box__select');
		$uiSelect.append($('<option></option>').attr('value', '').text('— Chọn nhóm —'));
		groups.forEach(function (g) {
			$uiSelect.append($('<option></option>').attr('value', g).text(g));
		});
		if (current) {
			$uiSelect.val(current);
		}

		function syncHidden(val) {
			val = String(val || '').trim();
			if ($field.is('select')) {
				if (val) {
					var exists = false;
					$field.find('option').each(function () {
						if ($(this).attr('value') === val) exists = true;
					});
					if (!exists) {
						$field.append($('<option></option>').attr('value', val).text(val));
					}
				}
				$field.val(val).trigger('change');
			} else {
				$field.val(val).trigger('change');
			}
		}

		syncHidden($uiSelect.val() || current || '');

		if ($field.is('select')) {
			hideNativePicklistUi($field);
		} else {
			$field.addClass('mk-ps-hide-legacy').css({ position: 'absolute', left: '-9999px', width: '1px', height: '1px', opacity: 0 });
		}
		var $host = $field.closest('.mk-ps-compact-value, td.fieldValue');
		$host.append($wrap);

		$uiSelect.on('change', function () {
			syncHidden($(this).val());
		});

		$wrap.find('.mk-ps-group-box__add').on('click', function (e) {
			e.preventDefault();
			var next = window.prompt('Tên nhóm mới:', '');
			if (next === null) return;
			next = String(next).trim();
			if (!next) return;
			var customs = loadCustomGroups();
			if (customs.indexOf(next) < 0) {
				customs.push(next);
				saveCustomGroups(customs);
			}
			if (!$uiSelect.find('option').filter(function () { return $(this).val() === next; }).length) {
				$uiSelect.append($('<option></option>').attr('value', next).text(next));
			}
			$uiSelect.val(next);
			syncHidden(next);
			persistNewGroup(next, function (ok) {
				if (window.app && app.helper && app.helper.showSuccessNotification) {
					app.helper.showSuccessNotification({
						message: ok ? 'Đã thêm nhóm: ' + next : 'Đã thêm nhóm trên form (có thể cần lưu SP để dùng lại).'
					});
				}
			});
		});
	}

	function enhanceTypeField() {
		var $select = $form().find('select[name="item_type"]').first();
		if (!$select.length) {
			return;
		}
		var $cell = $select.closest('.mk-ps-compact-value, td.fieldValue');
		if ($select.data('mkPsTypeReady') || $cell.find('.mk-ps-type-box').length) {
			hideNativePicklistUi($select);
			return;
		}
		$select.data('mkPsTypeReady', true);

		var current = String($select.val() || '').trim();
		var options = [];
		$select.find('option').each(function () {
			var v = String($(this).attr('value') || '').trim();
			if (!v) return;
			options.push({
				value: v,
				label: v === 'Product' ? 'Sản phẩm' : v === 'Service' ? 'Dịch vụ' : ($(this).text() || v)
			});
		});
		if (!options.length) {
			options = [
				{ value: 'Product', label: 'Sản phẩm' },
				{ value: 'Service', label: 'Dịch vụ' }
			];
		}

		var $box = $('<div class="mk-ps-type-box" role="radiogroup" aria-label="Loại"></div>');
		options.forEach(function (opt, idx) {
			var id = 'mk-ps-type-' + idx;
			var checked = (current && current === opt.value) || (!current && idx === 0);
			var $lab = $(
				'<label class="mk-ps-type-box__option' + (checked ? ' is-on' : '') + '" for="' + id + '">' +
					'<input type="radio" name="mk_ps_item_type_ui" id="' + id + '" value="' + opt.value + '"' +
					(checked ? ' checked' : '') + ' />' +
					'<span>' + opt.label + '</span>' +
				'</label>'
			);
			$box.append($lab);
		});

		hideNativePicklistUi($select);
		var $typeHost = $select.closest('.mk-ps-compact-value, td.fieldValue');
		$typeHost.append($box);

		function applyVal(val) {
			var exists = false;
			$select.find('option').each(function () {
				if ($(this).attr('value') === val) exists = true;
			});
			if (!exists) {
				$select.append($('<option></option>').attr('value', val).text(val));
			}
			$select.val(val).trigger('change');
			$box.find('.mk-ps-type-box__option').removeClass('is-on');
			$box.find('input[value="' + val + '"]').closest('.mk-ps-type-box__option').addClass('is-on');
		}

		if (!current && options[0]) {
			applyVal(options[0].value);
		}

		$box.on('change', 'input[type="radio"]', function () {
			applyVal($(this).val());
		});
	}

	function triggerSave() {
		if (typeof syncQtyRulesHidden === 'function') {
			syncQtyRulesHidden();
		}
		var $save = $form().find('.saveButton').first();
		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$form().trigger('submit');
	}

	function bindActions() {
		$('#mkPsSaveTop')
			.off('click.mkPsSave')
			.on('click.mkPsSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkPsCreate')
			.on('keydown.mkPsCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkPsFormHost').length) {
						return;
					}
					e.preventDefault();
					triggerSave();
				}
			});
	}

	function decorateCreateLayout() {
		var $host = $('#mkPsFormHost');
		if (!$host.length) {
			return;
		}
		var $blocks = $host.find('.fieldBlockContainer.mk-ps-block').filter(':visible');
		if (!$blocks.length) {
			return;
		}

		$blocks.each(function (idx) {
			var $block = $(this);
			$block.attr('data-mk-ps-block-idx', String(idx + 1));
			var $head = $block.find('.mk-ps-block__header, .fieldBlockHeader').first();
			if ($head.length && !$head.find('.mk-ps-block__num').length) {
				$head.prepend('<span class="mk-ps-block__num" aria-hidden="true">' + (idx + 1) + '</span>');
			}
			if (isImageBlock($block)) {
				$block.addClass('mk-ps-block--image');
				return;
			}
			if (idx === 0) {
				$block.addClass('mk-ps-block--primary');
			} else {
				$block.addClass('mk-ps-block--secondary');
			}
		});

		var $secondary = $blocks.filter('.mk-ps-block--secondary').not('.mk-ps-block--image');
		if ($secondary.length >= 2 && !$host.find('.mk-ps-block-mosaic').length) {
			var $mosaic = $('<div class="mk-ps-block-mosaic" role="presentation"></div>');
			$secondary.first().before($mosaic);
			$secondary.appendTo($mosaic);
		}

		$host.find('.mk-ps-compact-field').each(function () {
			var $field = $(this);
			var fname = String(
				$field.find('.mk-ps-compact-value').attr('data-fieldname') ||
					$field.find('[name]').first().attr('name') ||
					''
			)
				.replace(/\[\]$/, '')
				.trim();
			if (fname === 'productsservicesname' || fname === 'sku') {
				$field.addClass('mk-ps-compact-field--hero');
			}
			if (fname === 'price' || fname === 'wholesale_price'
				|| fname === 'price_tuibao' || fname === 'price_miutea'
				|| fname === 'price_lt_1m' || fname === 'price_gte_1m'
				|| fname === 'price_gte_3m' || fname === 'price_gte_5m'
				|| fname === 'price_gte_7m') {
				$field.addClass('mk-ps-compact-field--money');
				// Live VN grouping while typing (300000 → 300.000)
				if (window.MkCurrency && typeof window.MkCurrency.bindGroupedInput === 'function') {
					$field.find('input.inputElement, input[type="text"]').each(function () {
						window.MkCurrency.bindGroupedInput(this, { decimals: 0 });
					});
				}
			}
			if (fname === 'needs_qc') {
				$field.addClass('mk-ps-compact-field--qc');
				enhanceNeedsQcField($field);
			}
			if (fname === 'specification' || fname === 'description') {
				$field.addClass('mk-ps-compact-field--full mk-ps-compact-field--note');
			}
			if (fname === 'used_projects') {
				$field.addClass('mk-ps-compact-field--full mk-ps-compact-field--image');
			}
		});
	}

	function isImageBlock($block) {
		return (
			$block.find('[name="used_projects[]"], [name="used_projects"], [data-fieldname="used_projects"]').length >
			0
		);
	}

	function moveImageBlockToEnd() {
		var $host = $('#mkPsFormHost');
		if (!$host.length) {
			return;
		}
		var $contents = $host.find('.editViewContents').first();
		if (!$contents.length) {
			$contents = $form();
		}
		var $imgBlock = $host.find('.fieldBlockContainer').filter(function () {
			return isImageBlock($(this));
		}).last();
		if ($imgBlock.length) {
			$imgBlock.addClass('mk-ps-block mk-ps-block--image');
			$contents.append($imgBlock);
			var $head = $imgBlock.find('.fieldBlockHeader, .mk-ps-block__header').first();
			if ($head.length) {
				var $num = $head.find('.mk-ps-block__num').first();
				$head.contents().filter(function () {
					return this.nodeType === 3;
				}).remove();
				$head.find('span, strong, b').not('.mk-ps-block__num').each(function () {
					$(this).text('Hình ảnh');
				});
				if (!$head.clone().children().remove().end().text().trim() && !$head.find('span,strong,b').not('.mk-ps-block__num').length) {
					$head.append(document.createTextNode(' Hình ảnh'));
				}
				if ($num.length && !$head.find('.mk-ps-block__num').length) {
					$head.prepend($num);
				}
			}
			$imgBlock.find('.mk-ps-compact-label, td.fieldLabel').each(function () {
				var t = ($(this).text() || '').trim().toLowerCase();
				if (t.indexOf('dùng trong') >= 0 || t.indexOf('used in') >= 0 || t.indexOf('project') >= 0) {
					$(this).text('Hình ảnh');
				}
			});
			$imgBlock.find('input[type="file"]').attr(
				'accept',
				'image/jpeg,image/png,image/gif,image/webp,image/bmp,.jpg,.jpeg,.png,.gif,.webp,.bmp,.heic,.heif'
			);
			return;
		}
		var $imgField = $host.find('.mk-ps-compact-field').filter(function () {
			var n = String(
				$(this).find('.mk-ps-compact-value').attr('data-fieldname') ||
					$(this).find('[name]').first().attr('name') ||
					''
			)
				.replace(/\[\]$/, '')
				.trim();
			return n === 'used_projects';
		}).first();
		if ($imgField.length) {
			$imgField.addClass('mk-ps-compact-field--full mk-ps-compact-field--image');
			var $grid = $host.find('.mk-ps-compact-grid').last();
			if ($grid.length) {
				$grid.append($imgField);
			}
		}
	}

	function enhanceNeedsQcField($field) {
		if (!$field || !$field.length || $field.data('mkQcEnhanced')) {
			return;
		}
		var $val = $field.find('.mk-ps-compact-value').first();
		var $input = $val.find('input[type="checkbox"]').first();
		if (!$input.length) {
			$input = $field.find('input[type="checkbox"]').first();
		}
		if (!$input.length) {
			return;
		}
		$field.data('mkQcEnhanced', true);
		// Hide bare stock label clutter; keep checkbox as switch UI
		$val.find('label').not('.mk-ps-qc-switch').css('display', 'none');
		if ($input.closest('.mk-ps-qc-switch').length) {
			return;
		}
		var on = $input.is(':checked');
		var $switch = $(
			'<label class="mk-ps-qc-switch">' +
				'<span class="mk-ps-qc-switch__track" aria-hidden="true"><span class="mk-ps-qc-switch__knob"></span></span>' +
				'<span class="mk-ps-qc-switch__copy">' +
					'<span class="mk-ps-qc-switch__title">Cần QC</span>' +
					'<span class="mk-ps-qc-switch__hint">Hàng cần QC trước khi nhập tồn</span>' +
				'</span>' +
			'</label>'
		);
		$input.addClass('mk-ps-qc-switch__input').prependTo($switch);
		$val.empty().append($switch);
		$switch.toggleClass('is-on', on);
		$input.on('change.mkPsQc', function () {
			$switch.toggleClass('is-on', $input.is(':checked'));
		});
	}

	var QTY_UNIT_WORD = {
		lon: 'lon',
		bich: 'bịch',
		hop: 'hộp',
		bao: 'bao',
		thung: 'thùng',
		binh: 'bình',
		chai: 'chai',
		goi: 'gói',
		cai: 'cái',
		hu: 'hũ',
		can: 'can',
		cuon: 'cuộn',
		con: 'con',
		kg: 'kg',
		bo: 'bộ',
		set: 'set',
		lit: 'lít'
	};
	var QTY_TIERS = [
		{ value: 'gte_3m', label: 'Giá trên 3 triệu' },
		{ value: 'gte_5m', label: 'Giá trên 5 triệu' },
		{ value: 'gte_7m', label: 'Giá trên 7 triệu' }
	];

	function qtyUnitKey(raw) {
		var s = String(raw || '').toLowerCase();
		if (s.indexOf('thùng') >= 0 || s.indexOf('thung') >= 0) return 'thung';
		if (s.indexOf('bịch') >= 0 || s.indexOf('bich') >= 0) return 'bich';
		if (s.indexOf('hộp') >= 0 || s.indexOf('hop') >= 0) return 'hop';
		if (s.indexOf('lon') >= 0) return 'lon';
		if (s.indexOf('bao') >= 0) return 'bao';
		if (s.indexOf('bình') >= 0 || s.indexOf('binh') >= 0) return 'binh';
		if (s.indexOf('chai') >= 0) return 'chai';
		if (s.indexOf('gói') >= 0 || s.indexOf('goi') >= 0) return 'goi';
		if (s.indexOf('hũ') >= 0 || s.indexOf('hu') >= 0) return 'hu';
		if (s.indexOf('can') >= 0) return 'can';
		if (s.indexOf('cuộn') >= 0 || s.indexOf('cuon') >= 0) return 'cuon';
		if (s.indexOf('cái') >= 0 || s.indexOf('cai') >= 0) return 'cai';
		if (s.indexOf('bộ') >= 0 || s === 'bo') return 'bo';
		if (s.indexOf('lít') >= 0 || s.indexOf('lit') >= 0) return 'lit';
		if (s.indexOf('kg') >= 0) return 'kg';
		if (s.indexOf('set') >= 0) return 'set';
		if (s.indexOf('con') >= 0) return 'con';
		return '';
	}

	function currentSellUnit() {
		var raw = String($form().find('select[name="unit"]').first().val() || '').trim();
		var pack = window.MK_PS_QTY_RULES || {};
		var key = qtyUnitKey(raw) || qtyUnitKey(pack.sell_unit);
		var word = QTY_UNIT_WORD[key] || raw || 'đơn vị';
		return { key: key, word: word };
	}

	function stepsFromPack(pack) {
		var rules = pack && pack.rules ? pack.rules : [];
		var sell = qtyUnitKey(pack && pack.sell_unit);
		var per = Number(pack && pack.per_thung) || 0;
		var steps = [];
		rules.forEach(function (rule) {
			var min = Number(rule.min) || 0;
			if (!(min > 0)) return;
			var ruleUnit = qtyUnitKey(rule.unit);
			var qty = min;
			if (ruleUnit === 'thung' && sell !== 'thung' && per > 0) {
				qty = min * per;
			}
			steps.push({ qty: qty, tier: rule.tier || 'gte_3m' });
		});
		steps.sort(function (a, b) { return a.qty - b.qty; });
		return steps;
	}

	function payloadFromSteps(steps) {
		var origin = window.MK_PS_QTY_ORIGIN || { sell_unit: '', per_thung: 0 };
		var sell = currentSellUnit();
		var sellKey = sell.key || origin.sell_unit || 'cai';
		var per = sellKey && sellKey === origin.sell_unit ? (Number(origin.per_thung) || 0) : 0;
		var rules = [];
		steps.forEach(function (step) {
			var qty = parseInt(step.qty, 10) || 0;
			if (!(qty > 0)) return;
			if (per > 0 && sellKey !== 'thung' && qty % per === 0) {
				rules.push({ min: qty / per, unit: 'thung', tier: step.tier });
				return;
			}
			rules.push({ min: qty, unit: sellKey, tier: step.tier });
		});
		return {
			sell_unit: sellKey,
			per_thung: per,
			rules: rules
		};
	}

	function readEditorSteps($box) {
		var steps = [];
		$box.find('.mk-ps-qty-row').each(function () {
			var qty = parseInt($(this).find('.mk-ps-qty-min').val(), 10) || 0;
			var tier = String($(this).find('.mk-ps-qty-tier').val() || '');
			if (qty > 0 && tier) {
				steps.push({ qty: qty, tier: tier });
			}
		});
		steps.sort(function (a, b) { return a.qty - b.qty; });
		return steps;
	}

	function syncQtyRulesHidden() {
		var $box = $('#mkPsQtyRules');
		if (!$box.length) return;
		var payload = payloadFromSteps(readEditorSteps($box));
		window.MK_PS_QTY_RULES = payload;
		$box.find('#mkPsQtyRulesValue').val(JSON.stringify(payload));
		$box.find('.mk-ps-qty-empty').toggle(!$box.find('.mk-ps-qty-row').length);
	}

	function qtyTierSelect(selected) {
		var html = '<select class="mk-ps-qty-tier">';
		QTY_TIERS.forEach(function (item) {
			html += '<option value="' + item.value + '"' + (item.value === selected ? ' selected' : '') + '>' + item.label + '</option>';
		});
		html += '</select>';
		return html;
	}

	function qtyRowHtml(step) {
		var qty = step && step.qty ? step.qty : '';
		var tier = step && step.tier ? step.tier : 'gte_3m';
		return (
			'<div class="mk-ps-qty-row">' +
			'<span>Từ</span>' +
			'<input type="number" min="1" step="1" class="mk-ps-qty-min" value="' + qty + '" />' +
			'<span class="mk-ps-qty-unit"></span>' +
			qtyTierSelect(tier) +
			'<button type="button" class="mk-ps-qty-remove" aria-label="Xóa mốc">×</button>' +
			'</div>'
		);
	}

	function refreshQtyUnitWords() {
		var word = currentSellUnit().word;
		$('#mkPsQtyRules .mk-ps-qty-unit').text(word);
	}

	function mountQtyRulesEditor() {
		var $formEl = $form();
		if (!$formEl.length) return;
		if ($('#mkPsQtyRules').length) {
			refreshQtyUnitWords();
			return;
		}
		var pack = window.MK_PS_QTY_RULES || { rules: [] };
		if (!window.MK_PS_QTY_ORIGIN) {
			window.MK_PS_QTY_ORIGIN = {
				sell_unit: qtyUnitKey(pack.sell_unit),
				per_thung: Number(pack.per_thung) || 0
			};
		}
		var steps = stepsFromPack(pack);
		var $box = $(
			'<section class="mk-ps-qty-rules" id="mkPsQtyRules">' +
			'<h3>Chiết khấu theo số lượng</h3>' +
			'<p>Từ bao nhiêu đơn vị thì dùng cột giá nào. Để trống nếu món này không giảm theo số lượng.</p>' +
			'<div class="mk-ps-qty-rows"></div>' +
			'<p class="mk-ps-qty-empty">Món này chưa có mốc số lượng.</p>' +
			'<button type="button" class="mk-ps-qty-add">Thêm mốc</button>' +
			'<input type="hidden" name="mk_kl_qty_rules" id="mkPsQtyRulesValue" value="" />' +
			'</section>'
		);
		var $rows = $box.find('.mk-ps-qty-rows');
		steps.forEach(function (step) {
			$rows.append(qtyRowHtml(step));
		});
		var $block = $formEl.find('.mk-ps-block--primary').first();
		if (!$block.length) {
			$block = $formEl.find('.fieldBlockContainer').first();
		}
		if ($block.length) {
			$block.append($box);
		} else {
			$formEl.append($box);
		}
		$box.on('click', '.mk-ps-qty-add', function () {
			$rows.append(qtyRowHtml({ qty: '', tier: 'gte_3m' }));
			refreshQtyUnitWords();
			$box.find('.mk-ps-qty-empty').hide();
			syncQtyRulesHidden();
		});
		$box.on('click', '.mk-ps-qty-remove', function () {
			$(this).closest('.mk-ps-qty-row').remove();
			syncQtyRulesHidden();
		});
		$box.on('input change', '.mk-ps-qty-min, .mk-ps-qty-tier', function () {
			syncQtyRulesHidden();
		});
		$formEl.off('change.mkQtyUnit').on('change.mkQtyUnit', 'select[name="unit"]', function () {
			var next = currentSellUnit();
			if (window.MK_PS_QTY_RULES) {
				window.MK_PS_QTY_RULES.sell_unit = next.key || window.MK_PS_QTY_RULES.sell_unit;
			}
			refreshQtyUnitWords();
			syncQtyRulesHidden();
		});
		refreshQtyUnitWords();
		syncQtyRulesHidden();
	}

	function markPainted() {
		document.documentElement.classList.add('mk-ps-create-painted', 'mk-ps-create-ready');
		if (document.body) {
			document.body.classList.add('mk-ps-create-ready');
		}
	}

	function runEnhancements() {
		if (!isScoped()) {
			markPainted();
			return;
		}
		hideLegacyChrome();
		styleFieldBlocks();
		hideClutterFieldsAndBlocks();
		packCompactTwoColumn();
		relabelTypeField();
		enhanceUnitField();
		enhanceGroupField();
		enhanceTypeField();
		decorateCreateLayout();
		moveImageBlockToEnd();
		if (window.MkCurrency) {
			window.MkCurrency.applyToDom('#mkPsFormHost');
		}
		bindActions();
		mountQtyRulesEditor();
		markPainted();
	}

	function init() {
		if (!isScoped()) {
			markPainted();
			return;
		}
		runEnhancements();
		setTimeout(runEnhancements, 150);
		setTimeout(runEnhancements, 600);
		/* Safety: never leave workspace hidden after crash / slow JS */
		setTimeout(markPainted, 1200);

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

	window.__mkPsCreateBuild = MK_BUILD;
})($);
