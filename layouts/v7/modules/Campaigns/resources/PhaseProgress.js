/* Campaign Phase dashboard calculations for Detail view */

jQuery(function () {
	function parseNumber(val) {
		var n = parseFloat(val);
		return isNaN(n) ? 0 : n;
	}

	function clampPercent(p) {
		if (p < 0) return 0;
		if (p > 100) return 100;
		return p;
	}

	// Phase progress bars + totals for result progress
	var sumExpected = 0;
	var sumActual = 0;

	jQuery('.js-phase-card').each(function () {
		var $card = jQuery(this);
		var expected = parseNumber($card.data('expected'));
		var actual = parseNumber($card.data('actual'));

		sumExpected += expected;
		sumActual += actual;

		var percent = 0;
		if (expected > 0) {
			percent = (actual / expected) * 100.0;
		}
		percent = Math.round(clampPercent(percent));

		var $bar = $card.find('.js-phase-progress').first();
		$bar.css('width', percent + '%').text(percent + '%').attr('aria-valuenow', percent);
	});

	// Result Progress
	var resultPercent = 0;
	if (sumExpected > 0) {
		resultPercent = (sumActual / sumExpected) * 100.0;
	}
	resultPercent = Math.round(clampPercent(resultPercent));
	jQuery('.js-result-progress')
		.css('width', resultPercent + '%')
		.text(resultPercent + '%')
		.attr('aria-valuenow', resultPercent);

	// Time Progress
	var $dash = jQuery('#CampaignPhaseDashboard');
	var start = ($dash.data('start-date') || '').toString().trim();
	var end = ($dash.data('closing-date') || '').toString().trim();

	var timePercent = 0;
	if (start && end) {
		// Dates may come in user format; try best-effort parsing.
		var startDt = new Date(start);
		var endDt = new Date(end);
		var now = new Date();
		if (!isNaN(startDt.getTime()) && !isNaN(endDt.getTime()) && endDt > startDt) {
			var total = endDt.getTime() - startDt.getTime();
			var elapsed = now.getTime() - startDt.getTime();
			if (elapsed <= 0) {
				timePercent = 0;
			} else {
				timePercent = (elapsed / total) * 100.0;
			}
		}
	}
	timePercent = Math.round(clampPercent(timePercent));
	jQuery('.js-time-progress')
		.css('width', timePercent + '%')
		.text(timePercent + '%')
		.attr('aria-valuenow', timePercent);
});

