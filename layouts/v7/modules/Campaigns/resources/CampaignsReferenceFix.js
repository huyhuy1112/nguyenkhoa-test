/**
 * Campaigns — Products & Services reference popup fix (Create/Edit/Detail).
 */
(function ($) {
	'use strict';

	var REF_MODULE_BY_FIELD = {
		productsservices_id: 'ProductsServices',
		plans_id: 'Plans',
		plan_id: 'Plans',
		plan: 'Plans'
	};

	function isCampaignsScoped() {
		return $('body').data('module') === 'Campaigns' &&
			($('body').data('view') === 'Edit' || $('body').data('view') === 'Detail');
	}

	function $campaignForm() {
		var $f = $('form#EditView, form#detailView, form[name="edit"]').first();
		return $f.length ? $f : $('form').first();
	}

	function parsePopupSelection(data) {
		var parsed = data;
		if (typeof parsed === 'string') {
			try {
				parsed = JSON.parse(parsed);
			} catch (e1) {
				return [];
			}
		}
		var out = [];
		if (!parsed || typeof parsed !== 'object') {
			return out;
		}
		$.each(parsed, function (id, val) {
			if (val && val.name) {
				out.push({ id: String(id), name: val.name });
			}
		});
		return out;
	}

	function applyReferenceSelection(fieldName, id, displayName) {
		if (!fieldName || !id || !displayName) {
			return;
		}

		var $wraps = $campaignForm()
			.find('.referencefield-wrapper')
			.filter(function () {
				return $(this).find('input.sourceField[name="' + fieldName + '"]').length > 0;
			});

		if (!$wraps.length) {
			$wraps = $('.referencefield-wrapper').filter(function () {
				return $(this).find('input.sourceField[name="' + fieldName + '"]').length > 0;
			});
		}

		$wraps.each(function () {
			var $wrap = $(this);
			var $src = $wrap.find('input.sourceField[name="' + fieldName + '"]');
			var $disp = $wrap.find('input[name="' + fieldName + '_display"]');

			$src.val(String(id));
			$src.data('value', String(id));

			if ($disp.length) {
				$disp
					.val(displayName)
					.attr('readonly', 'readonly')
					.removeAttr('disabled')
					.removeClass('hide')
					.show();
			}

			$wrap.addClass('selected');
			$wrap.find('.clearReferenceSelection').removeClass('hide');
		});
	}

	function activePopupFieldName() {
		var $sf = $('#popupPageContainer #sourceField');
		return $sf.length ? String($sf.val() || '') : '';
	}

	function patchPopupDone() {
		if (typeof Vtiger_Popup_Js === 'undefined' || Vtiger_Popup_Js.prototype._mkCampxDonePatched) {
			return;
		}
		var origDone = Vtiger_Popup_Js.prototype.done;
		Vtiger_Popup_Js.prototype.done = function (result, eventToTrigger) {
			var fieldName = activePopupFieldName();
			var rows = parsePopupSelection(result);
			origDone.apply(this, arguments);
			if (!isCampaignsScoped() || !fieldName || !REF_MODULE_BY_FIELD[fieldName] || !rows.length) {
				return;
			}
			applyReferenceSelection(fieldName, rows[0].id, rows[0].name);
		};
		Vtiger_Popup_Js.prototype._mkCampxDonePatched = true;
	}

	function patchSetReferenceFieldValue() {
		if (!window.Vtiger_Index_Js || Vtiger_Index_Js.prototype._mkCampxSetRefPatched) {
			return;
		}
		var orig = Vtiger_Index_Js.prototype.setReferenceFieldValue;
		Vtiger_Index_Js.prototype.setReferenceFieldValue = function (container, params) {
			orig.call(this, container, params);
			if (!isCampaignsScoped() || !params || !params.id || !params.name) {
				return;
			}
			var sourceField = container.find('input.sourceField').attr('name');
			if (sourceField && REF_MODULE_BY_FIELD[sourceField]) {
				applyReferenceSelection(sourceField, params.id, params.name);
			}
		};
		Vtiger_Index_Js.prototype._mkCampxSetRefPatched = true;
	}

	function patchGetPopUpParams() {
		if (!window.Vtiger_Index_Js || Vtiger_Index_Js.prototype._mkCampxPopUpPatched) {
			return;
		}
		var orig = Vtiger_Index_Js.prototype.getPopUpParams;
		Vtiger_Index_Js.prototype.getPopUpParams = function (container) {
			var params = orig.call(this, container);
			if ((!params.module || params.module === 'undefined') && params.src_field && REF_MODULE_BY_FIELD[params.src_field]) {
				params.module = REF_MODULE_BY_FIELD[params.src_field];
			}
			return params;
		};
		Vtiger_Index_Js.prototype._mkCampxPopUpPatched = true;
	}

	function registerReferenceUi() {
		if (!window.Vtiger_Index_Js) {
			return;
		}
		var vtiger = Vtiger_Index_Js.getInstance();
		var $scope = $('#mkCampEnterpriseRoot').length ? $campaignForm() : $(document);
		if (vtiger.referenceModulePopupRegisterEvent) {
			vtiger.referenceModulePopupRegisterEvent($scope);
		}
		if (vtiger.registerClearReferenceSelectionEvent) {
			vtiger.registerClearReferenceSelectionEvent($scope);
		}
		if (window.Vtiger_Edit_Js && Vtiger_Edit_Js.getInstance) {
			var editJs = Vtiger_Edit_Js.getInstance();
			if (editJs && editJs.registerAutoCompleteFields) {
				editJs.registerAutoCompleteFields($scope);
			}
		}
	}

	function init() {
		if (!isCampaignsScoped()) {
			return;
		}
		patchPopupDone();
		patchSetReferenceFieldValue();
		patchGetPopUpParams();
		registerReferenceUi();
	}

	function whenVtigerReady(callback) {
		var n = 0;
		(function tick() {
			if (window.Vtiger_Index_Js && window.Vtiger_Popup_Js && window.Vtiger_Edit_Js) {
				callback();
				return;
			}
			n += 1;
			if (n < 200) {
				setTimeout(tick, 25);
			}
		}());
	}

	whenVtigerReady(init);
	$(window).on('load.mkCampxRef', function () {
		setTimeout(init, 50);
		setTimeout(init, 800);
	});
	$(document).ajaxComplete(function () {
		if (isCampaignsScoped()) {
			setTimeout(init, 80);
		}
	});
})(jQuery);
