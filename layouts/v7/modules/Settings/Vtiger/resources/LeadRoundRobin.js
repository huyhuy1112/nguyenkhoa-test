(function ($) {
	'use strict';

	function setStatus(msg, isError) {
		var $el = $('#nk-rr-status');
		$el.text(msg || '').css('color', isError ? '#dc2626' : '#08A045');
	}

	$(document).on('click', '.js-nk-rr-reset', function (e) {
		e.preventDefault();
		if (!confirm('Đặt lại vòng xoay về người đầu tiên trong danh sách Sale?')) {
			return;
		}
		var $btn = $(this);
		$btn.prop('disabled', true);
		setStatus('Đang đặt lại…');

		app.request.post({
			data: {
				module: 'Vtiger',
				parent: 'Settings',
				action: 'LeadRoundRobinAjax',
				mode: 'resetCursor'
			}
		}).then(function (err, data) {
			$btn.prop('disabled', false);
			if (err) {
				var msg = (typeof err === 'object' && err.message) ? err.message : (err || 'Không đặt lại được');
				setStatus(msg, true);
				return;
			}
			setStatus('Đã đặt lại vòng xoay.');
			window.setTimeout(function () {
				window.location.reload();
			}, 600);
		});
	});
})(jQuery);
