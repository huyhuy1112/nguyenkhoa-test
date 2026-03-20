/* Campaigns Edit: auto-calc ROI + nicer Campaign Phases layout */

Vtiger_Edit_Js("Campaigns_Edit_Js", {}, {

	registerRoiAutoCalc: function () {

		function parseNumber(val) {
			var n = parseFloat(val);
			return isNaN(n) ? 0 : n;
		}

		function calcRoi(revenue, cost) {
			cost = parseNumber(cost);
			revenue = parseNumber(revenue);
			if (cost <= 0) {
				return '';
			}
			var roi = ((revenue - cost) / cost) * 100;
			return roi.toFixed(2);
		}

		function updateExpectedRoi() {
			var $rev  = jQuery('[name="expectedrevenue"]');
			var $cost = jQuery('[name="budgetcost"]');
			var $roi  = jQuery('[name="expectedroi"]');

			if (!$rev.length || !$cost.length || !$roi.length) return;

			var val = calcRoi($rev.val(), $cost.val());
			if (val !== '') {
				$roi.val(val);
			}
			// make readonly so user cannot edit manually
			$roi.prop('readonly', true);
		}

		function updateActualRoi() {
			var $rev  = jQuery('[name="actualrevenue"], [name="actual_revenue"]').first();
			var $cost = jQuery('[name="actualcost"]');
			var $roi  = jQuery('[name="actualroi"]');

			if (!$cost.length || !$roi.length) return;
			if (!$rev.length) return;

			var val = calcRoi($rev.val(), $cost.val());
			if (val !== '') {
				$roi.val(val);
			}
			$roi.prop('readonly', true);
		}

		jQuery(document).on('keyup change', '[name="expectedrevenue"], [name="budgetcost"]', updateExpectedRoi);
		jQuery(document).on('keyup change', '[name="actualrevenue"], [name="actual_revenue"], [name="actualcost"]', updateActualRoi);

		// initial compute
		updateExpectedRoi();
		updateActualRoi();
	},

	/**
	 * Make phase comment fields span both columns:
	 * Phase N Expected | Phase N Actual
	 * Phase N Comment  (full width)
	 */
	adjustPhaseLayout: function () {
		// Do not depend on block label / language.
		// Find all comment rows by textarea name and expand them.
		jQuery('textarea[name^="phase"][name$="_comment"]').each(function () {
			var $textarea = jQuery(this);
			var $row = $textarea.closest('tr');
			if (!$row.length) return;

			// Avoid double-processing
			if ($row.data('phaseCommentExpanded')) return;

			var $cells = $row.children('td');
			// Typical vtiger 2-column row has 4 tds: label/value + label/value
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
		// Some vtiger pages render fields slightly later; retry a few times.
		var self = this;
		self.adjustPhaseLayout();
		setTimeout(function () { self.adjustPhaseLayout(); }, 200);
		setTimeout(function () { self.adjustPhaseLayout(); }, 800);
	}
});

