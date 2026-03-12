/*+***********************************************************************************
 * SupportFAQ Detail view enhancements.
 * Converts Increase Occurrence button click from GET to POST (CSRF-safe).
 *************************************************************************************/

Vtiger_Detail_Js('SupportFAQ_Detail_Js', {}, {

	registerEventForIncreaseOccurrence: function () {
		var buttonId = '#' + app.getModuleName() + '_detailView_basicAction_Increase_Occurrence';
		var button = jQuery(buttonId);
		if (!button.length) {
			return;
		}

		button.on('click', function (e) {
			e.preventDefault();

			// The button's onclick is the URL (page-load link). Extract it.
			var onClickAttr = button.attr('onclick') || '';
			var match = onClickAttr.match(/window\\.location\\.href\\s*=\\s*'([^']+)'/);
			var url = match ? match[1] : null;

			// Fallback: build URL from record id.
			if (!url) {
				var recordId = jQuery('input[name=\"record_id\"]').val();
				if (!recordId) return;
				url = 'index.php?module=SupportFAQ&action=IncreaseOccurrence&record=' + encodeURIComponent(recordId);
			}

			var form = jQuery('<form/>', { method: 'post', action: url });
			form.append(jQuery('<input/>', { type: 'hidden', name: csrfMagicName, value: csrfMagicToken }));
			form.appendTo('body').submit();
		});
	},

	registerEvents: function () {
		this._super();
		this.registerEventForIncreaseOccurrence();
	}
});

