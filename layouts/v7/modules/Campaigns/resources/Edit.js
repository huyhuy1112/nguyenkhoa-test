/* Campaigns Edit: ROI help + auto-calc (with manual override) + phase slots 2–5 */

Vtiger_Edit_Js("Campaigns_Edit_Js", {}, {

	/** @returns {jQuery} */
	_getActualRevenueField: function () {
		var $a = jQuery('[name="actualrevenue"]');
		if ($a.length) {
			return $a;
		}
		return jQuery('[name="actual_revenue"]');
	},

	_parseNumber: function (val) {
		var n = parseFloat(val);
		return isNaN(n) ? 0 : n;
	},

	_calcRoi: function (revenue, cost) {
		cost = this._parseNumber(cost);
		revenue = this._parseNumber(revenue);
		if (cost <= 0) {
			return '';
		}
		var roi = ((revenue - cost) / cost) * 100;
		return roi.toFixed(2);
	},

	_isManual: function ($roi) {
		return $roi.data('campaignsRoiManual') === true;
	},

	_setManual: function ($roi, flag) {
		$roi.data('campaignsRoiManual', flag);
	},

	registerRoiUi: function () {
		var self = this;

		function injectControls($roi, kind) {
			if (!$roi.length || $roi.closest('.campaigns-roi-wrap').length) {
				return;
			}
			var $wrap = jQuery('<div class="campaigns-roi-wrap input-group" style="max-width:280px;" />');
			$roi.before($wrap);
			$wrap.append($roi);
			var $btn = jQuery(
				'<span class="input-group-btn">' +
					'<button type="button" class="btn btn-default campaigns-roi-info" title="ROI">' +
						'<i class="fa fa-info-circle"></i>' +
					'</button>' +
				'</span>'
			);
			$wrap.append($btn);
		}

		injectControls(jQuery('[name="expectedroi"]'), 'expected');
		injectControls(jQuery('[name="actualroi"]'), 'actual');

		jQuery(document).on('click', '.campaigns-roi-info', function (e) {
			e.preventDefault();
			self._openRoiModal();
		});

		jQuery(document).on('focus', '[name="expectedroi"], [name="actualroi"]', function () {
			/* allow manual edit — no readonly */
		});

		jQuery(document).on('input change', '[name="expectedroi"], [name="actualroi"]', function () {
			self._setManual(jQuery(this), true);
		});
	},

	_openRoiModal: function () {
		var self = this;
		function tr(k) {
			return app.vtranslate(k, 'Campaigns');
		}

		var html = '' +
			'<div class="modal fade campaigns-roi-modal" tabindex="-1">' +
			'  <div class="modal-dialog">' +
			'    <div class="modal-content">' +
			'      <div class="modal-header">' +
			'        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>' +
			'        <h4 class="modal-title">' + tr('LBL_CAMPAIGN_ROI_MODAL_TITLE') + '</h4>' +
			'      </div>' +
			'      <div class="modal-body">' +
			'        <p>' + tr('LBL_CAMPAIGN_ROI_FORMULA_EXPLAIN') + '</p>' +
			'        <pre style="background:#f8fafc;padding:10px;border-radius:8px;">ROI = (Revenue - Cost) / Cost x 100 (%)</pre>' +
			'        <p class="text-muted small">' + tr('LBL_CAMPAIGN_ROI_EXPECTED_FIELDS') + '</p>' +
			'        <p class="text-muted small">' + tr('LBL_CAMPAIGN_ROI_ACTUAL_HINT') + '</p>' +
			'      </div>' +
			'      <div class="modal-footer">' +
			'        <button type="button" class="btn btn-default" data-dismiss="modal">' + app.vtranslate('LBL_CLOSE', 'Vtiger') + '</button>' +
			'        <button type="button" class="btn btn-primary campaigns-roi-recalc">' + tr('LBL_CAMPAIGN_ROI_RECALC') + '</button>' +
			'      </div>' +
			'    </div>' +
			'  </div>' +
			'</div>';

		var $m = jQuery(html).appendTo('body');
		$m.modal('show');
		$m.on('hidden.bs.modal', function () {
			$m.remove();
		});

		$m.find('.campaigns-roi-recalc').on('click', function () {
			self._setManual(jQuery('[name="expectedroi"]'), false);
			self._setManual(jQuery('[name="actualroi"]'), false);
			self._runRoiAutoCalc();
			$m.modal('hide');
		});
	},

	_runRoiAutoCalc: function () {
		var $expRoi = jQuery('[name="expectedroi"]');
		var $actRoi = jQuery('[name="actualroi"]');
		var $rev = jQuery('[name="expectedrevenue"]');
		var $bcost = jQuery('[name="budgetcost"]');
		var $arev = this._getActualRevenueField();
		var $fcost = jQuery('[name="actualcost"]');

		if (!$expRoi.length || !this._isManual($expRoi)) {
			if ($rev.length && $bcost.length && $expRoi.length) {
				var ev = this._calcRoi($rev.val(), $bcost.val());
				if (ev !== '') {
					$expRoi.val(ev);
				}
			}
		}

		if (!$actRoi.length || !this._isManual($actRoi)) {
			if (!$fcost.length || !$actRoi.length) {
				return;
			}
			var revenueVal;
			if ($arev.length && String($arev.val()).trim() !== '') {
				revenueVal = $arev.val();
			} else if ($rev.length) {
				revenueVal = $rev.val();
			} else {
				return;
			}
			var av = this._calcRoi(revenueVal, $fcost.val());
			if (av !== '') {
				$actRoi.val(av);
			}
		}
	},

	registerRoiAutoCalc: function () {
		var self = this;
		self.registerRoiUi();

		jQuery(document).on('keyup change', '[name="expectedrevenue"], [name="budgetcost"]', function () {
			self._setManual(jQuery('[name="expectedroi"]'), false);
			self._runRoiAutoCalc();
		});
		jQuery(document).on('keyup change', '[name="actualrevenue"], [name="actual_revenue"], [name="expectedrevenue"], [name="actualcost"]', function () {
			self._setManual(jQuery('[name="actualroi"]'), false);
			self._runRoiAutoCalc();
		});

		self._runRoiAutoCalc();
		setTimeout(function () { self._runRoiAutoCalc(); }, 300);
		setTimeout(function () { self._runRoiAutoCalc(); }, 900);
	},

	_getPhaseCountInput: function () {
		var $f = jQuery('#EditView, form#EditView').first();
		var $i = $f.find('[name="campaign_phase_count"]').first();
		if (!$i.length) {
			$i = jQuery('<input type="hidden" name="campaign_phase_count" value="2" />');
			$f.append($i);
		}
		return $i;
	},

	_getInitialPhaseCount: function () {
		var $h = jQuery('#campaigns-phase-count-initial');
		var v = parseInt($h.val(), 10);
		if (isNaN(v) || v < 2) {
			v = 2;
		}
		if (v > 5) {
			v = 5;
		}
		return v;
	},

	_applyPhaseRowsVisibility: function (count) {
		var self = this;
		for (var p = 1; p <= 5; p++) {
			var show = p <= count;
			jQuery('[name^="phase' + p + '_"]').each(function () {
				var $row = jQuery(this).closest('tr');
				if ($row.length) {
					if (show) {
						$row.show();
					} else {
						$row.hide();
					}
				}
			});
		}
		self._normalizeCommentLabels();
	},

	_normalizeCommentLabels: function () {
		var short = app.vtranslate('LBL_COMMENT_SHORT', 'Campaigns');
		jQuery('textarea[name^="phase"][name$="_comment"]').each(function () {
			var name = jQuery(this).attr('name') || '';
			if (!/^phase[1-5]_comment$/.test(name)) {
				return;
			}
			var $row = jQuery(this).closest('tr');
			if (!$row.length) {
				$row = jQuery(this).closest('.fieldRow');
			}
			if (!$row.length) {
				return;
			}
			var $fl = $row.find('td.fieldLabel').first();
			if ($fl.length) {
				var $star = $fl.find('.redColor');
				$fl.empty().append(document.createTextNode(short));
				if ($star.length) {
					$fl.append(document.createTextNode(' '));
					$fl.append($star);
				}
			} else {
				$row.find('label').first().text(short);
			}
		});
	},

	registerPhaseSlots: function () {
		var self = this;
		var $countField = self._getPhaseCountInput();
		var initial = self._getInitialPhaseCount();
		if (!$countField.val() || parseInt($countField.val(), 10) < 2) {
			$countField.val(String(initial));
		}
		var count = parseInt($countField.val(), 10);
		if (isNaN(count) || count < 2) {
			count = 2;
		}
		if (count > 5) {
			count = 5;
		}
		$countField.val(String(count));

		self._applyPhaseRowsVisibility(count);
		jQuery('[name="campaign_phase_count"]').closest('tr').hide();

		var $anchor = jQuery('[name="phase1_expected"]').closest('.fieldBlockContainer');
		if (!$anchor.length) {
			$anchor = jQuery('[name="phase1_expected"]').closest('table');
		}
		if ($anchor.length && !jQuery('.js-campaign-phase-toolbar').length) {
			var $tb = jQuery(
				'<div class="js-campaign-phase-toolbar clearfix" style="margin:12px 0;">' +
					'<button type="button" class="btn btn-default btn-sm pull-left js-campaign-add-phase">' +
						app.vtranslate('LBL_CAMPAIGN_ADD_PHASE', 'Campaigns') +
					'</button>' +
					'<span class="small text-muted pull-left js-campaign-phase-hint" style="margin-left:10px;line-height:30px;"></span>' +
				'</div>'
			);
			$anchor.before($tb);
		}

		function updateHint(c) {
			var t = app.vtranslate('LBL_CAMPAIGN_PHASES_HINT', 'Campaigns');
			var n = String(c);
			if (t && t.indexOf('%s') !== -1) {
				jQuery('.js-campaign-phase-hint').text(t.split('%s').join(n));
			} else {
				jQuery('.js-campaign-phase-hint').text('Active phases: ' + n + ' (max 5)');
			}
		}
		updateHint(count);

		jQuery(document).on('click', '.js-campaign-add-phase', function (e) {
			e.preventDefault();
			var c = parseInt($countField.val(), 10) || 2;
			if (c >= 5) {
				return;
			}
			c += 1;
			$countField.val(String(c));
			self._applyPhaseRowsVisibility(c);
			updateHint(c);
		});
	},

	/**
	 * Description is plain text; files (image / Office) go through Documents relation.
	 */
	registerDescriptionDocuments: function () {
		var $ta = jQuery('textarea[name="description"]').first();
		if (!$ta.length || $ta.data('campaignsDescFiles')) {
			return;
		}
		var recordId = jQuery('input[name="record"]').val();
		var appParam = (typeof app !== 'undefined' && app.getAppName) ? app.getAppName() : '';
		var appQs = appParam ? ('&app=' + encodeURIComponent(appParam)) : '';

		var help = app.vtranslate('LBL_CAMPAIGN_EDIT_DESC_FILES_HELP', 'Campaigns');
		var $box = jQuery('<div class="campaigns-edit-desc-files small" style="margin-top:10px;"/>');
		$box.append(jQuery('<p class="text-muted" style="margin-bottom:6px;"/>').text(help));

		if (recordId) {
			var addUrl = 'index.php?module=Documents&view=Edit&relationOperation=true&sourceModule=Campaigns&sourceRecord=' +
				encodeURIComponent(recordId) + appQs;
			var listUrl = 'index.php?module=Campaigns&view=Detail&record=' + encodeURIComponent(recordId) +
				'&relatedModule=Documents&mode=showRelatedList' + appQs;
			$box.append(
				jQuery('<a class="btn btn-default btn-sm" style="margin-right:6px;"/>')
					.attr('href', addUrl)
					.text(app.vtranslate('LBL_ADD_DOCUMENT', 'Campaigns'))
			);
			$box.append(
				jQuery('<a class="btn btn-default btn-sm"/>')
					.attr('href', listUrl)
					.text(app.vtranslate('LBL_VIEW_RELATED_DOCUMENTS', 'Campaigns'))
			);
		} else {
			$box.append(
				jQuery('<span class="text-muted"/>').text(
					app.vtranslate('LBL_CAMPAIGN_EDIT_DESC_FILES_SAVE_FIRST', 'Campaigns')
				)
			);
		}

		var $cell = $ta.closest('td');
		if ($cell.length) {
			$cell.append($box);
		} else {
			$ta.after($box);
		}
		$ta.data('campaignsDescFiles', true);
	},

	adjustPhaseLayout: function () {
		jQuery('textarea[name^="phase"][name$="_comment"]').each(function () {
			var $textarea = jQuery(this);
			var $row = $textarea.closest('tr');
			if (!$row.length) {
				return;
			}
			if ($row.data('phaseCommentExpanded')) {
				return;
			}
			var $cells = $row.children('td');
			if ($cells.length >= 4) {
				$cells.eq(3).remove();
				$cells.eq(2).remove();
				$cells.eq(1).attr('colspan', 3);
				$row.data('phaseCommentExpanded', true);
			}
		});
	},

	registerEvents: function () {
		this._super();
		this.registerRoiAutoCalc();
		this.registerPhaseSlots();
		var self = this;
		self.registerDescriptionDocuments();
		self.adjustPhaseLayout();
		setTimeout(function () {
			self._normalizeCommentLabels();
			self.registerDescriptionDocuments();
			self.adjustPhaseLayout();
		}, 200);
		setTimeout(function () {
			self._normalizeCommentLabels();
			self.registerDescriptionDocuments();
			self.adjustPhaseLayout();
		}, 800);
	}
});
