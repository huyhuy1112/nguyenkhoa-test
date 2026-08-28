/*+***********************************************************************************
 * Potentials EditView: Opportunity Name editable (date prefix YYMMDD + suffix).
 * Project Code (cf_859) stays auto-generated / read-only.
 *************************************************************************************/
(function () {
	'use strict';
	if (typeof jQuery === 'undefined') {
		return;
	}

	var HINT_EDIT = 'Có thể sửa tên — phần ngày (6 số đầu, VD: 260619) và phần sau dấu gạch.';
	var HINT_CREATE = 'Chọn Tổ chức + Ngày dự kiến kết thúc để gợi ý tên. Sửa phần ngày (6 số đầu) nếu cần trước khi lưu.';

	function lockField($el, hintText) {
		if (!$el || !$el.length) {
			return;
		}
		$el.each(function () {
			var $i = jQuery(this);
			$i.prop('readonly', true).attr('readonly', 'readonly');
			$i.addClass('misa-auto-locked');
			$i.css({
				background: '#f7f7f7',
				cursor: 'not-allowed'
			});
			if (!$i.attr('title')) {
				$i.attr('title', hintText);
			}
			setFieldHint($i, hintText);
		});
	}

	function unlockField($el) {
		if (!$el || !$el.length) {
			return;
		}
		$el.each(function () {
			var $i = jQuery(this);
			$i.prop('readonly', false).removeAttr('readonly');
			$i.removeClass('misa-auto-locked');
			$i.css({
				background: '',
				cursor: ''
			});
		});
	}

	function setFieldHint($input, text) {
		var $wrap = $input.closest('td.fieldValue, td');
		if (!$wrap.length) {
			$wrap = $input.parent();
		}
		var $hint = $wrap.find('.js-auto-generated-hint');
		if (!$hint.length) {
			$hint = jQuery('<div class="js-auto-generated-hint"></div>');
			$wrap.append($hint);
		}
		$hint.text(text).css({
			marginTop: '4px',
			fontSize: '11px',
			color: '#64748b',
			lineHeight: '1.4'
		});
	}

	function isCreateMode($form) {
		var recordId = ($form.find('[name="record"]').val() || '').toString().trim();
		return !recordId || recordId === '0';
	}

	function parseDateToYymmdd(raw) {
		var s = (raw || '').toString().trim();
		if (!s) {
			return '';
		}
		// yyyy-mm-dd
		var iso = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
		if (iso) {
			return iso[1].slice(-2) + iso[2] + iso[3];
		}
		// dd-mm-yyyy or dd/mm/yyyy
		var dmy = s.match(/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/);
		if (dmy) {
			var dd = ('0' + dmy[1]).slice(-2);
			var mm = ('0' + dmy[2]).slice(-2);
			return dmy[3].slice(-2) + mm + dd;
		}
		// mm-dd-yyyy (US)
		var mdy = s.match(/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/);
		if (mdy) {
			var mm2 = ('0' + mdy[1]).slice(-2);
			var dd2 = ('0' + mdy[2]).slice(-2);
			return mdy[3].slice(-2) + mm2 + dd2;
		}
		return '';
	}

	function todayYymmdd() {
		var d = new Date();
		var y = String(d.getFullYear()).slice(-2);
		var m = ('0' + (d.getMonth() + 1)).slice(-2);
		var day = ('0' + d.getDate()).slice(-2);
		return y + m + day;
	}

	function splitPotentialName(value) {
		var s = (value || '').toString().trim();
		var m = s.match(/^(\d{6})-(.*)$/);
		if (m) {
			return { date: m[1], rest: m[2] };
		}
		return { date: '', rest: s };
	}

	function registerCreateNameAssist($form, $name) {
		var userEdited = false;
		var lastAuto = '';

		$name.on('input.mkOppName', function () {
			userEdited = true;
		});

		function canSuggest() {
			var orgId = ($form.find('[name="related_to"]').val() || '').toString().trim();
			return orgId !== '' && orgId !== '0';
		}

		function suggestName() {
			if (!canSuggest()) {
				return;
			}
			if (userEdited && ($name.val() || '').trim() !== '') {
				return;
			}

			var datePrefix = parseDateToYymmdd($form.find('[name="closingdate"]').val());
			if (!datePrefix) {
				datePrefix = todayYymmdd();
			}

			var current = splitPotentialName($name.val());
			var rest = current.rest;
			if (!rest) {
				rest = 'project';
			}

			var suggested = datePrefix + '-' + rest;
			if (suggested === lastAuto && ($name.val() || '').trim() !== '') {
				return;
			}
			lastAuto = suggested;
			$name.val(suggested);
			setFieldHint($name, HINT_CREATE);
		}

		$form.on(
			'change.mkOppName input.mkOppName',
			'[name="related_to"], [name="closingdate"], [name="related_to_display"]',
			function () {
				suggestName();
			}
		);

		$form.on(Vtiger_Edit_Js.referenceSelectionEvent, '[name="related_to"]', function () {
			setTimeout(suggestName, 100);
		});

		setTimeout(suggestName, 300);
	}

	function init() {
		try {
			if (window.app && app.getModuleName && app.getModuleName() !== 'Potentials') {
				return;
			}
		} catch (e) {}

		var $form = jQuery('form[name="EditView"], form#EditView');
		if (!$form.length) {
			return;
		}

		var $name = $form.find('[name="potentialname"]');
		var creating = isCreateMode($form);

		if ($name.length) {
			unlockField($name);
			setFieldHint($name, creating ? HINT_CREATE : HINT_EDIT);
			if (creating) {
				registerCreateNameAssist($form, $name);
			}
		}

		lockField($form.find('[name="cf_859"]'), 'Auto-generated Project Code');
	}

	jQuery(init);
	jQuery(document).ajaxComplete(function () {
		setTimeout(init, 120);
	});
})();
