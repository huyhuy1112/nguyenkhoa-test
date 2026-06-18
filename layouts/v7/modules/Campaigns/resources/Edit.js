/* Campaigns Edit: ROI + phase slots + description file attachments (no Documents module) */

// This file can be loaded before core Edit.js on some custom layouts.
// Delay module class definition until Vtiger_Edit_Js exists.
(function () {
	function defineOnce() {
		if (window.__mkCampaignsEditJsDefined) {
			return true;
		}
		if (typeof window.Vtiger_Edit_Js === 'undefined') {
			return false;
		}
		window.__mkCampaignsEditJsDefined = true;

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

	registerRoiUi: function () {
		var self = this;

		function injectControls($roi, kind) {
			if (!$roi.length || $roi.closest('.campaigns-roi-wrap').length) {
				return;
			}
			var $group = $roi.closest('.input-group');
			var $move = $group.length ? $group : $roi;
			var $wrap = jQuery('<div class="campaigns-roi-wrap input-group" />');
			$move.before($wrap);
			$wrap.append($move);
			var $btn = jQuery(
				'<span class="input-group-btn">' +
					'<button type="button" class="btn btn-default campaigns-roi-info" title="ROI">' +
						'<i class="fa fa-info-circle"></i>' +
					'</button>' +
				'</span>'
			);
			$wrap.append($btn);
			$roi.prop('readonly', true).addClass('campaigns-roi-readonly');
		}

		injectControls(jQuery('[name="expectedroi"]'), 'expected');
		injectControls(jQuery('[name="actualroi"]'), 'actual');

		jQuery(document).on('click', '.campaigns-roi-info', function (e) {
			e.preventDefault();
			self._openRoiModal();
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
			'        <p class="text-muted small">' + tr('LBL_CAMPAIGN_ROI_FIELDS_READONLY') + '</p>' +
			'      </div>' +
			'      <div class="modal-footer">' +
			'        <button type="button" class="btn btn-default" data-dismiss="modal">' + app.vtranslate('LBL_CLOSE', 'Vtiger') + '</button>' +
			'      </div>' +
			'    </div>' +
			'  </div>' +
			'</div>';

		var $m = jQuery(html).appendTo('body');
		$m.modal('show');
		$m.on('hidden.bs.modal', function () {
			$m.remove();
		});
		self._runRoiAutoCalc();
	},

	_runRoiAutoCalc: function () {
		var $expRoi = jQuery('[name="expectedroi"]');
		var $actRoi = jQuery('[name="actualroi"]');
		var $rev = jQuery('[name="expectedrevenue"]');
		var $bcost = jQuery('[name="budgetcost"]');
		var $arev = this._getActualRevenueField();
		var $fcost = jQuery('[name="actualcost"]');

		if ($rev.length && $bcost.length && $expRoi.length) {
			var ev = this._calcRoi($rev.val(), $bcost.val());
			if (ev !== '') {
				$expRoi.val(ev);
			}
		}

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
	},

	registerRoiAutoCalc: function () {
		var self = this;
		self.registerRoiUi();

		jQuery(document).on('keyup change', '[name="expectedrevenue"], [name="budgetcost"]', function () {
			self._runRoiAutoCalc();
		});
		jQuery(document).on('keyup change', '[name="actualrevenue"], [name="actual_revenue"], [name="expectedrevenue"], [name="actualcost"]', function () {
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

	_numericMeaningful: function (val) {
		if (val === undefined || val === null) {
			return false;
		}
		var s = String(val).trim();
		if (s === '') {
			return false;
		}
		var n = parseFloat(s);
		if (isNaN(n)) {
			return false;
		}
		return Math.abs(n) > 0.0000001;
	},

	_textMeaningful: function (val) {
		if (val === undefined || val === null) {
			return false;
		}
		return String(val).trim() !== '';
	},

	_dateMeaningful: function (val) {
		if (val === undefined || val === null) {
			return false;
		}
		var s = String(val).trim();
		if (s === '') {
			return false;
		}
		if (/^0000-00-00/.test(s)) {
			return false;
		}
		return true;
	},

	_phaseMeaningfulFromDom: function (p) {
		var $exp = jQuery('[name="phase' + p + '_expected"]');
		var $act = jQuery('[name="phase' + p + '_actual"]');
		if (this._numericMeaningful($exp.val()) || this._numericMeaningful($act.val())) {
			return true;
		}
		if (this._textMeaningful(jQuery('[name="phase' + p + '_comment"]').val())) {
			return true;
		}
		if (this._dateMeaningful(jQuery('[name="phase' + p + '_start_date"]').val())) {
			return true;
		}
		if (this._dateMeaningful(jQuery('[name="phase' + p + '_end_date"]').val())) {
			return true;
		}
		return false;
	},

	_highestMeaningfulPhaseFromDom: function () {
		var h = 0;
		var p;
		for (p = 1; p <= 5; p++) {
			if (this._phaseMeaningfulFromDom(p)) {
				h = p;
			}
		}
		return h;
	},

	_effectivePhaseCountFromDom: function () {
		var $cf = this._getPhaseCountInput();
		var s = parseInt($cf.val(), 10);
		if (!isNaN(s) && s >= 2 && s <= 5) {
			// Respect explicit slot count (Add/Remove phase buttons).
			return s;
		}
		if (isNaN(s) || s < 2) {
			s = this._getInitialPhaseCount();
		}
		if (s < 2) {
			s = 2;
		}
		if (s > 5) {
			s = 5;
		}
		var h = this._highestMeaningfulPhaseFromDom();
		var N = Math.max(2, s, h);
		if (N > 5) {
			N = 5;
		}
		return N;
	},

	_clearPhaseFields: function (p) {
		var suffixes = ['expected', 'actual', 'comment', 'start_date', 'end_date'];
		var i;
		for (i = 0; i < suffixes.length; i++) {
			var sel = '[name="phase' + p + '_' + suffixes[i] + '"]';
			var $el = jQuery(sel);
			if ($el.length) {
				$el.val('');
			}
		}
	},

	_updatePhaseToolbarButtons: function (count) {
		jQuery('.js-campaign-add-phase').prop('disabled', count >= 5);
		jQuery('.js-campaign-remove-phase').prop('disabled', count <= 2);
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
		var isEnterprise = jQuery('#mkCampEnterpriseRoot').length > 0;
		var $countField = self._getPhaseCountInput();
		var count = self._effectivePhaseCountFromDom();
		$countField.val(String(count));

		self._applyPhaseRowsVisibility(count);
		jQuery('[name="campaign_phase_count"]').closest('tr').hide();

		if (isEnterprise) {
			self._updatePhaseToolbarButtons(count);
			return;
		}

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
					'<button type="button" class="btn btn-default btn-sm pull-left js-campaign-remove-phase" style="margin-left:8px;">' +
						app.vtranslate('LBL_CAMPAIGN_REMOVE_LAST_PHASE', 'Campaigns') +
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
		self._updatePhaseToolbarButtons(count);

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
			self._updatePhaseToolbarButtons(c);
		});

		jQuery(document).on('click', '.js-campaign-remove-phase', function (e) {
			e.preventDefault();
			var c = parseInt($countField.val(), 10) || 2;
			if (c <= 2) {
				return;
			}
			self._clearPhaseFields(c);
			c -= 1;
			$countField.val(String(c));
			self._applyPhaseRowsVisibility(c);
			updateHint(c);
			self._updatePhaseToolbarButtons(c);
		});
	},

	_parseExistingCampaignFilesJson: function () {
		var $raw = jQuery('#campaign-files-json');
		if (!$raw.length) {
			return [];
		}
		var text = jQuery.trim($raw.text());
		if (!text) {
			return [];
		}
		try {
			return JSON.parse(text) || [];
		} catch (ignore) {
			return [];
		}
	},

	/**
	 * Plain file input (multipart save). Allowed types: jpg/png/docx/xlsx.
	 */
	registerCampaignDescriptionFiles: function () {
		var self = this;
		var $collab = jQuery('#mkCampXCollab');
		var $existingBox = jQuery('#campaign-files-edit-box');
		if ($existingBox.length) {
			if ($collab.length && !$collab.find('#campaign-files-edit-box').length) {
				$collab.append($existingBox);
			}
			return;
		}
		var $str = jQuery('#campaign-files-edit-strings');
		if (!$str.length) {
			return;
		}

		var $ta = jQuery('textarea[name="description"]').first();
		var anchorIsTextarea = $ta.length > 0;
		var $anchor = anchorIsTextarea ? $ta : $str;
		if (!$anchor.length) {
			return;
		}

		var help = $str.data('help') || '';
		var $box = jQuery('<div id="campaign-files-edit-box" class="campaign-edit-files small" style="margin-top:10px;"/>');
		if (help) {
			$box.append(jQuery('<p class="text-muted" style="margin-bottom:6px;"/>').text(help));
		}

		var existing = self._parseExistingCampaignFilesJson();
		if (existing.length) {
			var lbl = app.vtranslate('LBL_CAMPAIGN_FILES_EXISTING', 'Campaigns');
			var $ul0 = jQuery('<ul class="list-unstyled" style="margin-bottom:8px;"/>');
			var ei;
			for (ei = 0; ei < existing.length; ei++) {
				var ex = existing[ei];
				if (!ex || !ex.download_url) {
					continue;
				}
				var $a = jQuery('<a target="_blank" rel="noopener noreferrer"/>')
					.attr('href', ex.download_url)
					.text(ex.original_name || ex.download_url);
				$ul0.append(jQuery('<li/>').append($a));
			}
			$box.append(jQuery('<p class="text-muted small" style="margin-bottom:4px;"/>').text(lbl));
			$box.append($ul0);
		}

		var $input = jQuery(
			'<input type="file" name="campaign_files[]" multiple="multiple" ' +
				'accept=".jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,image/jpeg,image/png,' +
				'application/msword,application/vnd.ms-excel,' +
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document,' +
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" />'
		);
		$box.append($input);

		// Enterprise layout: always show inside the "Notes & attachments" card.
		if ($collab.length) {
			$collab.append($box);
		} else if (anchorIsTextarea) {
			var $cell = $ta.closest('td');
			if ($cell.length) {
				$cell.append($box);
			} else {
				$ta.after($box);
			}
		} else {
			$str.after($box);
		}
		$anchor.data('campaignFilesEditInit', true);
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
		self.registerCampaignDescriptionFiles();
		self.adjustPhaseLayout();
		setTimeout(function () {
			self._normalizeCommentLabels();
			self.registerCampaignDescriptionFiles();
			self.adjustPhaseLayout();
		}, 200);
		setTimeout(function () {
			self._normalizeCommentLabels();
			self.registerCampaignDescriptionFiles();
			self.adjustPhaseLayout();
		}, 800);
	}
		});

		return true;
	}

	if (!defineOnce()) {
		var tries = 0;
		var t = window.setInterval(function () {
			tries++;
			if (defineOnce() || tries > 200) {
				window.clearInterval(t);
			}
		}, 50);
	}
})();
