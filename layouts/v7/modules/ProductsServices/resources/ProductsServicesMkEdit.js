/**
 * ProductsServices Create (SALES / INVENTORY) — dashboard shell + stock vtiger form.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260715_ps_create_v5';
	var UNIT_PRESETS = ['cái', 'hộp', 'set', 'bộ'];
	var UNIT_STORAGE_KEY = 'mk_ps_custom_units_v1';
	/* Only fields previously agreed to remove — keep brand/model/stock/etc. */
	var HIDE_FIELD_NAMES = [
		'warranty',
		'related_projects',
		'used_projects',
		'retail_price',
		'bulk_price'
	];
	var FULL_WIDTH_FIELDS = {
		specification: true,
		description: true,
		comment: true
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

		/* Only hide image block (already agreed). Keep product/service/delivery/pricing. */
		$f.find('.fieldBlockContainer[data-block="LBL_PROJECT_HISTORY"]').addClass('mk-ps-hide-legacy');
		$f.find('.fieldBlockContainer').each(function () {
			var $block = $(this);
			if ($block.hasClass('mk-ps-hide-legacy')) return;
			var title = ($block.find('.fieldBlockHeader').first().text() || '').trim().toLowerCase();
			if (title.indexOf('hình ảnh') >= 0 || title.indexOf('pictures') >= 0 || title.indexOf('project history') >= 0) {
				$block.addClass('mk-ps-hide-legacy');
			}
		});
	}

	function packCompactTwoColumn() {
		var $f = $form();
		$f.find('.fieldBlockContainer.mk-ps-block').each(function () {
			var $block = $(this);
			if ($block.hasClass('mk-ps-hide-legacy') || $block.data('mkPsPacked')) {
				return;
			}
			var $table = $block.find('> table').first();
			if (!$table.length) return;

			var pairs = [];
			$table.find('> tbody > tr').each(function () {
				var $tr = $(this);
				if ($tr.hasClass('mk-ps-hide-legacy') || !$tr.is(':visible')) {
					return;
				}
				var $labels = $tr.children('td.fieldLabel');
				var $values = $tr.children('td.fieldValue');
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

			$table.addClass('mk-ps-hide-legacy').hide();
			$block.append($grid);
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

	function allUnits() {
		var out = [];
		var seen = {};
		UNIT_PRESETS.concat(loadCustomUnits()).forEach(function (u) {
			var key = String(u || '').trim();
			if (!key || seen[key]) return;
			seen[key] = true;
			out.push(key);
		});
		return out;
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

		var current = String($select.val() || '').trim();
		var mapOld = { pcs: 'cái', box: 'hộp', set: 'set', kg: 'kg', Piece: 'cái', Box: 'hộp', Set: 'set' };
		if (mapOld[current]) current = mapOld[current];

		var units = allUnits();
		if (current && units.indexOf(current) < 0) {
			units.push(current);
		}

		var $wrap = $(
			'<div class="mk-ps-unit-box">' +
				'<div class="mk-ps-unit-box__row">' +
					'<select class="mk-ps-unit-box__select inputElement" aria-label="Đơn vị tính"></select>' +
					'<button type="button" class="mk-ps-unit-box__add" title="Thêm đơn vị">+</button>' +
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
			syncHidden($(this).val());
		});

		$wrap.find('.mk-ps-unit-box__add').on('click', function (e) {
			e.preventDefault();
			var next = window.prompt('Thêm đơn vị tính mới:', '');
			if (next === null) return;
			next = String(next).trim();
			if (!next) return;
			var customs = loadCustomUnits();
			if (customs.indexOf(next) < 0 && UNIT_PRESETS.indexOf(next) < 0) {
				customs.push(next);
				saveCustomUnits(customs);
			}
			if (!$uiSelect.find('option').filter(function () { return $(this).val() === next; }).length) {
				$uiSelect.append($('<option></option>').attr('value', next).text(next));
			}
			$uiSelect.val(next);
			syncHidden(next);
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
			if (idx === 0) {
				$block.addClass('mk-ps-block--primary');
			} else {
				$block.addClass('mk-ps-block--secondary');
			}
		});

		var $secondary = $blocks.filter('.mk-ps-block--secondary');
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
			if (fname === 'price' || fname === 'wholesale_price') {
				$field.addClass('mk-ps-compact-field--money');
			}
			if (fname === 'specification' || fname === 'description') {
				$field.addClass('mk-ps-compact-field--full mk-ps-compact-field--note');
			}
		});
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
		enhanceTypeField();
		decorateCreateLayout();
		if (window.MkCurrency) {
			window.MkCurrency.applyToDom('#mkPsFormHost');
		}
		bindActions();
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
